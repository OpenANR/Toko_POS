<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hapus data lama agar kategori kopi tidak bercampur dengan data sebelumnya.
        Schema::disableForeignKeyConstraints();
        DB::table('products')->truncate();
        DB::table('categories')->truncate();
        Schema::enableForeignKeyConstraints();

        $categories = [
            ['id' => 1, 'name' => 'Biji Kopi', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Kopi Bubuk', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Kopi Siap Minum', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Alat Seduh', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('categories')->insert($categories);
    }
}
