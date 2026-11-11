<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hapus produk lama sebelum mengisi produk kopi.
        DB::table('products')->truncate();

        $products = [
            // Biji Kopi
            [
                'id' => 1,
                'category_id' => 1,
                'name' => 'Arabika Gayo Premium 250g',
                'sku' => 'KOP-001',
                'price' => 85000,
                'stock' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'category_id' => 1,
                'name' => 'Arabika Toraja 250g',
                'sku' => 'KOP-002',
                'price' => 95000,
                'stock' => 35,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'category_id' => 1,
                'name' => 'Robusta Lampung 250g',
                'sku' => 'KOP-003',
                'price' => 65000,
                'stock' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'category_id' => 1,
                'name' => 'Arabika Kintamani 250g',
                'sku' => 'KOP-004',
                'price' => 90000,
                'stock' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'category_id' => 1,
                'name' => 'House Blend Espresso 1kg',
                'sku' => 'KOP-005',
                'price' => 280000,
                'stock' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'category_id' => 1,
                'name' => 'Kopi Luwak Arabika 100g',
                'sku' => 'KOP-006',
                'price' => 250000,
                'stock' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7,
                'category_id' => 1,
                'name' => 'Arabika Flores Bajawa 250g',
                'sku' => 'KOP-007',
                'price' => 92000,
                'stock' => 25,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Kopi Bubuk
            [
                'id' => 8,
                'category_id' => 2,
                'name' => 'Kopi Bubuk Gayo Halus 200g',
                'sku' => 'KOP-008',
                'price' => 45000,
                'stock' => 60,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 9,
                'category_id' => 2,
                'name' => 'Kopi Bubuk Robusta Tubruk 200g',
                'sku' => 'KOP-009',
                'price' => 35000,
                'stock' => 70,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 10,
                'category_id' => 2,
                'name' => 'Kopi Bubuk Arabika Toraja 200g',
                'sku' => 'KOP-010',
                'price' => 55000,
                'stock' => 45,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 11,
                'category_id' => 2,
                'name' => 'Kopi Bubuk Espresso Blend 250g',
                'sku' => 'KOP-011',
                'price' => 60000,
                'stock' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 12,
                'category_id' => 2,
                'name' => 'Kopi Bubuk Decaf 200g',
                'sku' => 'KOP-012',
                'price' => 75000,
                'stock' => 15,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Kopi Siap Minum
            [
                'id' => 13,
                'category_id' => 3,
                'name' => 'Cold Brew Arabica 250ml',
                'sku' => 'KOP-013',
                'price' => 25000,
                'stock' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 14,
                'category_id' => 3,
                'name' => 'Kopi Susu Gula Aren 250ml',
                'sku' => 'KOP-014',
                'price' => 22000,
                'stock' => 60,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 15,
                'category_id' => 3,
                'name' => 'Americano Botol 250ml',
                'sku' => 'KOP-015',
                'price' => 20000,
                'stock' => 55,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 16,
                'category_id' => 3,
                'name' => 'Latte Botol 250ml',
                'sku' => 'KOP-016',
                'price' => 28000,
                'stock' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Alat Seduh
            [
                'id' => 17,
                'category_id' => 4,
                'name' => 'V60 Dripper Keramik',
                'sku' => 'KOP-017',
                'price' => 175000,
                'stock' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 18,
                'category_id' => 4,
                'name' => 'Kertas Filter V60 (100 Lembar)',
                'sku' => 'KOP-018',
                'price' => 45000,
                'stock' => 35,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 19,
                'category_id' => 4,
                'name' => 'French Press 600ml',
                'sku' => 'KOP-019',
                'price' => 195000,
                'stock' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 20,
                'category_id' => 4,
                'name' => 'Grinder Manual Coffee Mill',
                'sku' => 'KOP-020',
                'price' => 320000,
                'stock' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 21,
                'category_id' => 4,
                'name' => 'Moka Pot 3 Cup',
                'sku' => 'KOP-021',
                'price' => 210000,
                'stock' => 9,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 22,
                'category_id' => 4,
                'name' => 'Cold Drip Tower 600ml',
                'sku' => 'KOP-022',
                'price' => 450000,
                'stock' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('products')->insert($products);
    }
}
