<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptAndDiscountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create([
            'name' => 'Elektronik',
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Mouse Logitech M331',
            'sku' => 'PRD-002',
            'price' => 175000,
            'stock' => 80,
        ]);
    }

    public function test_pos_page_is_accessible()
    {
        $response = $this->get('/pos');
        $response->assertStatus(200);
        $response->assertSee('Diskon (Rp)');
    }

    public function test_print_receipt_route_prints_with_discount()
    {
        $response = $this->get('/print-receipt');
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Struk berhasil dicetak',
        ]);

        $filePath = storage_path('logs/struk.txt');
        $this->assertFileExists($filePath);
        $content = file_get_contents($filePath);
        $this->assertStringContainsString('Kopi Kita', $content);
        $this->assertStringContainsString('Diskon', $content);
        $this->assertStringContainsString('Total Bayar', $content);
    }

    public function test_pos_checkout_with_discount_and_struk_printing()
    {
        $product = Product::first();
        $this->assertNotNull($product);

        $initialStock = $product->stock;
        $qty = 2;
        $subtotal = $product->price * $qty;
        $discount = 50000;
        $totalPrice = $subtotal - $discount;
        $payAmount = $totalPrice + 20000;

        $response = $this->postJson('/pos/checkout', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => $qty,
                ],
            ],
            'discount' => $discount,
            'pay_amount' => $payAmount,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total_price' => $totalPrice,
                'pay_amount' => $payAmount,
                'change_amount' => 20000,
            ],
        ]);
        $response->assertJsonPath('printed', true);
        $response->assertJsonPath('receipt', fn ($receipt) => str_contains($receipt, 'Kembalian'));

        // Pastikan stok berkurang
        $product->refresh();
        $this->assertEquals($initialStock - $qty, $product->stock);

        // Pastikan isi file struk memiliki semua komponen yang diminta dosen
        $filePath = storage_path('logs/struk.txt');
        $this->assertFileExists($filePath);
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('Kopi Kita', $content);
        $this->assertStringContainsString('Tgl:', $content);
        $this->assertStringContainsString($product->name, $content);
        $this->assertStringContainsString('Subtotal', $content);
        $this->assertStringContainsString('Diskon', $content);
        $this->assertStringContainsString('Total Bayar', $content);
        $this->assertStringContainsString('Kembalian', $content);
    }

    public function test_pos_page_does_not_use_browser_printing()
    {
        $response = $this->get('/pos');

        $response->assertStatus(200);
        $response->assertDontSee('window.print');
        $response->assertSee('Cetak Ulang ke Printer');
    }

    public function test_reprint_route_prints_receipt_from_backend()
    {
        $transaction = Transaction::create([
            'invoice_number' => 'INV-REPRINT01',
            'subtotal' => 20000,
            'discount' => 2000,
            'total_price' => 18000,
            'pay_amount' => 20000,
            'change_amount' => 2000,
            'items' => [
                [
                    'product_id' => 1,
                    'name' => 'Kopi Susu',
                    'price' => 10000,
                    'quantity' => 2,
                    'subtotal' => 20000,
                ],
            ],
        ]);

        $response = $this->postJson('/pos/receipt/'.$transaction->id.'/print');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'printed' => true,
            'message' => 'Struk berhasil dicetak ulang.',
        ]);

        $content = file_get_contents(storage_path('logs/struk.txt'));
        $this->assertStringContainsString('INV-REPRINT01', $content);
        $this->assertStringContainsString('Kopi Susu', $content);
    }
}
