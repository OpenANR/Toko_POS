<?php

namespace App\Services;

use App\Models\Transaction;
use Midtrans\Config;
use Midtrans\Snap;

class MidtransPayment
{
    public function __construct()
    {
        $this->configure();
    }

    /**
     * Terapkan konfigurasi Midtrans dari config/midtrans.php.
     *
     * Ini adalah langkah "Konfigurasi Midtrans di Laravel" pada acara 37.
     * Diletakkan di service agar tidak ditulis berulang di setiap controller.
     */
    public function configure(): void
    {
        Config::$serverKey = (string) config('midtrans.serverKey');
        Config::$isProduction = (bool) config('midtrans.isProduction');
        Config::$isSanitized = (bool) config('midtrans.isSanitized');
        Config::$is3ds = (bool) config('midtrans.is3ds');
    }

    /**
     * Buat Snap token untuk transaksi POS.
     *
     * order_id memakai invoice_number agar unik dan mudah dicocokkan
     * saat menerima notifikasi (webhook) dari Midtrans.
     */
    public function createSnapToken(Transaction $transaction): string
    {
        $this->configure();

        $params = [
            'transaction_details' => [
                'order_id' => $transaction->invoice_number,
                'gross_amount' => (int) round((float) $transaction->total_price),
            ],
            'customer_details' => [
                'first_name' => 'Pelanggan Toko',
                'email' => 'pelanggan@example.com',
            ],
            'callbacks' => [
                'finish' => route('pos.index'),
            ],
        ];

        return (string) Snap::getSnapToken($params);
    }
}
