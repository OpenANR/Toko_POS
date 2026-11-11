<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\MidtransPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Midtrans\Notification;
use Throwable;

class MidtransController extends Controller
{
    public function __construct(private MidtransPayment $midtrans) {}

    /**
     * Endpoint webhook/callback yang dipanggil Midtrans setelah pembayaran.
     *
     * Route ini dikecualikan dari CSRF di bootstrap/app.php karena
     * request-nya berasal dari server Midtrans, bukan dari form browser.
     */
    public function notification(Request $request): JsonResponse
    {
        $payload = $request->all();

        if (! $this->isValidSignature($payload)) {
            Log::warning('Notifikasi Midtrans ditolak: signature tidak valid.', [
                'order_id' => $payload['order_id'] ?? null,
            ]);

            return response()->json(['message' => 'Invalid signature'], 403);
        }

        try {
            // Pastikan Config::$serverKey dkk. sudah terpasang sebelum
            // Notification memanggil API Midtrans.
            $this->midtrans->configure();

            // Notification mengambil status terbaru langsung dari API Midtrans,
            // sehingga status yang diproses bukan sekadar kiriman request.
            $notif = new Notification;
        } catch (Throwable $e) {
            Log::error('Gagal membaca notifikasi Midtrans: '.$e->getMessage());

            return response()->json(['message' => 'Notification could not be read'], 500);
        }

        $orderId = $notif->order_id ?? ($payload['order_id'] ?? null);
        $transactionStatus = (string) ($notif->transaction_status ?? ($payload['transaction_status'] ?? ''));
        $fraudStatus = (string) ($notif->fraud_status ?? ($payload['fraud_status'] ?? ''));

        $transaction = Transaction::where('invoice_number', $orderId)->first();

        if (! $transaction) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $newStatus = $this->mapStatus($transactionStatus, $fraudStatus);

        DB::transaction(function () use ($transaction, $newStatus) {
            $locked = Transaction::whereKey($transaction->id)->lockForUpdate()->first();
            $oldStatus = $locked->payment_status;

            $attributes = ['payment_status' => $newStatus];

            if ($newStatus === 'paid' && $locked->paid_at === null) {
                $attributes['paid_at'] = now();
            }

            $locked->update($attributes);

            // Stok sudah dikurangi saat checkout. Jika pembayaran gagal/kedaluwarsa,
            // kembalikan stok hanya sekali (dari status pending).
            if (in_array($newStatus, ['failed', 'expired'], true) && $oldStatus === 'pending') {
                $locked->restoreStock();
            }
        });

        return response()->json(['message' => 'Notification handled']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isValidSignature(array $payload): bool
    {
        $signatureKey = (string) ($payload['signature_key'] ?? '');

        if ($signatureKey === '') {
            return false;
        }

        $expected = hash('sha512',
            ($payload['order_id'] ?? '')
            .($payload['status_code'] ?? '')
            .($payload['gross_amount'] ?? '')
            .(string) config('midtrans.serverKey')
        );

        return hash_equals($expected, $signatureKey);
    }

    private function mapStatus(string $transactionStatus, string $fraudStatus): string
    {
        return match ($transactionStatus) {
            'capture' => $fraudStatus === 'challenge' ? 'pending' : 'paid',
            'settlement' => 'paid',
            'pending' => 'pending',
            'deny', 'cancel', 'failure' => 'failed',
            'expire' => 'expired',
            default => 'pending',
        };
    }
}
