<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\MidtransPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MidtransPaymentTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create([
            'name' => 'Minuman',
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kopi Susu',
            'sku' => 'PRD-MIDTRANS',
            'price' => 25000,
            'stock' => 10,
        ]);
    }

    public function test_midtrans_checkout_creates_pending_transaction_and_snap_token()
    {
        $this->mock(MidtransPayment::class, function ($mock) {
            $mock->shouldReceive('createSnapToken')->once()->andReturn('snap-test-token');
        });

        $response = $this->postJson('/pos/checkout', [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                ],
            ],
            'discount' => 5000,
            'payment_method' => 'midtrans',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('payment_method', 'midtrans')
            ->assertJsonPath('snap_token', 'snap-test-token')
            ->assertJsonPath('data.payment_status', 'pending')
            ->assertJsonPath('data.total_price', 45000)
            ->assertJsonPath('data.pay_amount', 45000)
            ->assertJsonPath('data.change_amount', 0);

        $this->assertDatabaseHas('transactions', [
            'payment_method' => 'midtrans',
            'payment_status' => 'pending',
            'snap_token' => 'snap-test-token',
        ]);

        // Stok dikurangi saat checkout dan baru dikembalikan jika gagal/expire.
        $this->assertSame(8, $this->product->fresh()->stock);
    }

    public function test_cancel_pending_midtrans_payment_restores_stock()
    {
        $this->product->decrement('stock', 2);

        $transaction = Transaction::create([
            'invoice_number' => 'INV-MIDTRANS01',
            'payment_method' => 'midtrans',
            'payment_status' => 'pending',
            'subtotal' => 50000,
            'discount' => 0,
            'total_price' => 50000,
            'pay_amount' => 50000,
            'change_amount' => 0,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'name' => $this->product->name,
                    'price' => 25000,
                    'quantity' => 2,
                    'subtotal' => 50000,
                ],
            ],
        ]);

        $response = $this->postJson('/pos/payment/'.$transaction->id.'/cancel');

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertSame('failed', $transaction->fresh()->payment_status);
        $this->assertSame(10, $this->product->fresh()->stock);
    }

    public function test_notification_with_invalid_signature_is_rejected()
    {
        $response = $this->postJson('/midtrans/notification', [
            'order_id' => 'INV-TEST',
            'status_code' => '200',
            'gross_amount' => '10000.00',
            'signature_key' => 'invalid',
        ]);

        $response->assertStatus(403);
    }
}
