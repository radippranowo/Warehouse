<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'kode_supplier' => 'SUP001',
                'nama_supplier' => 'PT. Maju Jaya Motor',
                'kontak' => 'Budi Santoso',
                'telepon' => '021-12345678',
                'alamat' => 'Jl. Raya Industri No. 123, Jakarta Timur',
                'keterangan' => 'Supplier sparepart motor utama',
                'is_active' => true,
            ],
            [
                'kode_supplier' => 'SUP002',
                'nama_supplier' => 'CV. Berkah Motor Parts',
                'kontak' => 'Siti Aminah',
                'telepon' => '021-87654321',
                'alamat' => 'Jl. Gatot Subroto No. 45, Jakarta Selatan',
                'keterangan' => 'Supplier oli dan filter',
                'is_active' => true,
            ],
            [
                'kode_supplier' => 'SUP003',
                'nama_supplier' => 'UD. Sumber Rejeki',
                'kontak' => 'Ahmad Yani',
                'telepon' => '022-11223344',
                'alamat' => 'Jl. Soekarno Hatta No. 78, Bandung',
                'keterangan' => 'Supplier ban dan velg',
                'is_active' => true,
            ],
            [
                'kode_supplier' => 'SUP004',
                'nama_supplier' => 'PT. Global Auto Parts',
                'kontak' => 'Dewi Lestari',
                'telepon' => '031-55667788',
                'alamat' => 'Jl. Raya Darmo No. 234, Surabaya',
                'keterangan' => 'Supplier aksesoris motor',
                'is_active' => true,
            ],
            [
                'kode_supplier' => 'SUP005',
                'nama_supplier' => 'Toko Cahaya Motor',
                'kontak' => 'Eko Prasetyo',
                'telepon' => '0274-998877',
                'alamat' => 'Jl. Malioboro No. 56, Yogyakarta',
                'keterangan' => 'Supplier lampu dan kelistrikan',
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}

