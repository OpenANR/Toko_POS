<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use App\Services\MidtransPayment;
use App\Services\ReceiptPrinter;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class PosController extends Controller
{
    public function __construct(
        private ReceiptPrinter $receiptPrinter,
        private MidtransPayment $midtransPayment,
    ) {}

    public function index()
    {
        $products = Product::where('stock', '>', 0)->get();

        return view('pos.index', compact('products'));
    }

    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'discount' => 'nullable|numeric|min:0',
            'pay_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,midtrans',
        ]);

        $paymentMethod = (string) $request->input('payment_method', 'cash');

        try {
            $transaction = DB::transaction(function () use ($request, $paymentMethod) {
                $subtotal = 0;
                $transactionItems = [];

                foreach ($request->items as $item) {
                    $product = Product::lockForUpdate()->find($item['product_id']);

                    if (! $product || $product->stock < $item['quantity']) {
                        throw new Exception('Stok '.($product ? $product->name : 'produk').' tidak mencukupi.');
                    }

                    $itemSubtotal = $product->price * $item['quantity'];
                    $subtotal += $itemSubtotal;

                    $product->decrement('stock', $item['quantity']);

                    $transactionItems[] = [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'price' => (float) $product->price,
                        'quantity' => (int) $item['quantity'],
                        'subtotal' => (float) $itemSubtotal,
                    ];
                }

                $discount = (float) $request->input('discount', 0);
                if ($discount < 0) {
                    $discount = 0;
                }
                if ($discount > $subtotal) {
                    $discount = $subtotal;
                }

                $totalPrice = $subtotal - $discount;

                if ($paymentMethod === 'midtrans') {
                    if ($totalPrice <= 0) {
                        throw new Exception('Total pembayaran Midtrans harus lebih dari Rp 0.');
                    }

                    // Pembayaran online: uang belum diterima saat checkout.
                    $payAmount = $totalPrice;
                    $changeAmount = 0;
                    $paymentStatus = 'pending';
                    $paidAt = null;
                } else {
                    $payAmount = (float) $request->input('pay_amount', 0);

                    if ($payAmount < $totalPrice) {
                        throw new Exception('Uang pembayaran tidak cukup. Total harus dibayar: Rp '.number_format($totalPrice, 0, ',', '.'));
                    }

                    $changeAmount = $payAmount - $totalPrice;
                    $paymentStatus = 'paid';
                    $paidAt = now();
                }

                return Transaction::create([
                    'invoice_number' => 'INV-'.strtoupper(Str::random(8)),
                    'payment_method' => $paymentMethod,
                    'payment_status' => $paymentStatus,
                    'paid_at' => $paidAt,
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'total_price' => $totalPrice,
                    'pay_amount' => $payAmount,
                    'change_amount' => $changeAmount,
                    'items' => $transactionItems,
                ]);
            });
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        if ($paymentMethod === 'midtrans') {
            return $this->createMidtransPayment($transaction);
        }

        // Pencetakan struk dilakukan sepenuhnya di backend memakai Mike42 ESC/POS.
        $printResult = $this->receiptPrinter->print($transaction);

        return response()->json([
            'success' => true,
            'message' => $printResult['printed']
                ? 'Transaksi berhasil & struk dicetak!'
                : 'Transaksi berhasil, tetapi struk gagal dicetak.',
            'data' => $transaction,
            'payment_method' => 'cash',
            'receipt' => $printResult['receipt'],
            'printed' => $printResult['printed'],
            'print_error' => $printResult['error'],
        ]);
    }

    /**
     * Buat Snap token setelah transaksi tersimpan.
     *
     * Pemanggilan API Midtrans sengaja dilakukan di luar DB transaction agar
     * koneksi database tidak tertahan selama menunggu response Midtrans.
     */
    private function createMidtransPayment(Transaction $transaction): JsonResponse
    {
        try {
            $snapToken = $this->midtransPayment->createSnapToken($transaction);
            $transaction->update(['snap_token' => $snapToken]);

            return response()->json([
                'success' => true,
                'message' => 'Transaksi dibuat. Silakan selesaikan pembayaran Midtrans.',
                'data' => $transaction->fresh(),
                'payment_method' => 'midtrans',
                'snap_token' => $snapToken,
                'order_id' => $transaction->invoice_number,
                'receipt' => null,
                'printed' => false,
            ]);
        } catch (Throwable $e) {
            // Token gagal dibuat berarti pembayaran tidak mungkin dilanjutkan.
            $this->cancelPendingTransaction($transaction);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat transaksi Midtrans: '.$e->getMessage(),
            ], 502);
        }
    }

    /**
     * Batalkan pembayaran Midtrans yang masih pending (mis. popup error).
     */
    public function cancelPayment(Transaction $transaction): JsonResponse
    {
        if (! $this->cancelPendingTransaction($transaction)) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak dalam status pending.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran dibatalkan dan stok dikembalikan.',
        ]);
    }

    private function cancelPendingTransaction(Transaction $transaction): bool
    {
        return DB::transaction(function () use ($transaction) {
            $locked = Transaction::whereKey($transaction->id)->lockForUpdate()->first();

            if (! $locked || $locked->payment_status !== 'pending') {
                return false;
            }

            $locked->update(['payment_status' => 'failed']);
            $locked->restoreStock();

            return true;
        });
    }

    /**
     * Cetak ulang struk transaksi melalui backend (Mike42), bukan window.print().
     */
    public function reprint(Transaction $transaction)
    {
        $printResult = $this->receiptPrinter->print($transaction);

        return response()->json([
            'success' => $printResult['printed'],
            'message' => $printResult['printed']
                ? 'Struk berhasil dicetak ulang.'
                : 'Struk gagal dicetak ulang.',
            'receipt' => $printResult['receipt'],
            'printed' => $printResult['printed'],
            'print_error' => $printResult['error'],
        ], $printResult['printed'] ? 200 : 500);
    }
}
