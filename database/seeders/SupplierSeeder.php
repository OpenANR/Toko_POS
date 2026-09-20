<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Supplier::insert([
            [
                'supplier_id' => 'K-0989',
                'name'        => 'PT Pertamina (Persero)',
                'phone'       => '135',
                'address'     => 'Grha Pertamina - Jl. Medan Merdeka Timur No.11-13, Gambir, Jakarta Pusat 10110',
            ],
            [
                'supplier_id' => 'K-0650',
                'name'        => 'PT Perusahaan Listrik Negara (Persero)',
                'phone'       => '123',
                'address'     => 'Jl. Trunojoyo Blok M-I No.135, Kebayoran Baru, Jakarta Selatan 12160',
            ],
            [
                'supplier_id' => 'K-634',
                'name'        => 'PT Astra International Tbk',
                'phone'       => '021-6522555',
                'address'     => 'Menara Astra, Jl. Jend. Sudirman KAV. 5-6, Tanah Abang, Jakarta Pusat 10220',
            ],
            [
                'supplier_id' => 'K-412',
                'name'        => 'PT Bank Rakyat Indonesia (Persero) Tbk',
                'phone'       => '1500017',
                'address'     => 'Gedung BRI 1, Jl. Jend. Sudirman No. 44-46, Jakarta Pusat 10210',
            ],
            [
                'supplier_id' => 'K-978',
                'name'        => 'PT Telkom Indonesia (Persero) Tbk',
                'phone'       => '021-80863535',
                'address'     => 'Telkom Landmark Tower, Jl. Jend. Gatot Subroto Kav. 52, Kuningan Barat, Mampang Prapatan, Jakarta Selatan 12710',
            ],
        ]);
    }
}
