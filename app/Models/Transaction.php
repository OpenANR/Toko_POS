<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'invoice_number',
        'payment_method',
        'payment_status',
        'snap_token',
        'paid_at',
        'subtotal',
        'discount',
        'total_price',
        'pay_amount',
        'change_amount',
        'items',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'discount' => 'float',
        'total_price' => 'float',
        'pay_amount' => 'float',
        'change_amount' => 'float',
        'items' => 'array',
        'paid_at' => 'datetime',
    ];

    /**
     * Kembalikan stok produk untuk transaksi yang batal/kedaluwarsa.
     *
     * Stok sudah dikurangi saat checkout, jadi pengembalian hanya boleh
     * dilakukan sekali ketika status pembayaran berubah dari pending.
     */
    public function restoreStock(): void
    {
        foreach ((array) $this->items as $item) {
            $productId = $item['product_id'] ?? null;
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($productId && $quantity > 0) {
                Product::whereKey($productId)->increment('stock', $quantity);
            }
        }
    }
}
