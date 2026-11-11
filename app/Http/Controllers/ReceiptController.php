<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\ReceiptPrinter;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function __construct(private ReceiptPrinter $receiptPrinter) {}

    /**
     * Cetak struk dummy untuk pengujian library Mike42.
     */
    public function print(Request $request)
    {
        $items = [
            ['name' => 'Kopi Susu', 'quantity' => 2, 'price' => 15000, 'subtotal' => 30000],
            ['name' => 'Roti Bakar', 'quantity' => 1, 'price' => 12000, 'subtotal' => 12000],
        ];

        $subtotal = array_sum(array_column($items, 'subtotal'));
        $discount = min((float) $request->input('diskon', 2000), $subtotal);
        $totalPembayaran = $subtotal - $discount;
        $payAmount = $totalPembayaran + 10000;

        $transaction = new Transaction([
            'invoice_number' => 'PREVIEW-'.date('YmdHis'),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total_price' => $totalPembayaran,
            'pay_amount' => $payAmount,
            'change_amount' => $payAmount - $totalPembayaran,
            'items' => $items,
        ]);
        $transaction->created_at = now();
        $transaction->updated_at = now();

        $printResult = $this->receiptPrinter->print($transaction);

        return response()->json([
            'success' => $printResult['printed'],
            'message' => $printResult['printed']
                ? 'Struk berhasil dicetak'
                : 'Struk gagal dicetak',
            'receipt' => $printResult['receipt'],
            'printed' => $printResult['printed'],
            'print_error' => $printResult['error'],
            'data' => [
                'nama_toko' => config('printing.store_name'),
                'tanggal' => $transaction->created_at->format('d/m/Y H:i:s'),
                'items' => $items,
                'subtotal' => $subtotal,
                'diskon' => $discount,
                'total_pembayaran' => $totalPembayaran,
            ],
        ], $printResult['printed'] ? 200 : 500);
    }
}
