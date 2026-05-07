<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\BarangStok;
use App\Models\Category;
use App\Models\Group;
use App\Models\Gudang;
use App\Models\Merk;
use App\Models\SubCategory;
use Illuminate\Database\Seeder;

class LogistikSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['kode_category' => 'CAT-01', 'nama_category' => 'Sparepart Mesin'],
            ['kode_category' => 'CAT-02', 'nama_category' => 'Pelumas'],
            ['kode_category' => 'CAT-03', 'nama_category' => 'Tools'],
        ];
        foreach ($categories as $c) Category::firstOrCreate(['kode_category' => $c['kode_category']], $c);

        $subCategories = [
            ['kode_sub_category' => 'SUB-01', 'nama_sub_category' => 'Filter'],
            ['kode_sub_category' => 'SUB-02', 'nama_sub_category' => 'Oli Mesin'],
            ['kode_sub_category' => 'SUB-03', 'nama_sub_category' => 'Oli Hidrolik'],
            ['kode_sub_category' => 'SUB-04', 'nama_sub_category' => 'Kunci'],
        ];
        foreach ($subCategories as $sc) SubCategory::firstOrCreate(['kode_sub_category' => $sc['kode_sub_category']], $sc);

        $merks = [
            ['kode_merk' => 'MRK-01', 'nama_merk' => 'Bosch'],
            ['kode_merk' => 'MRK-02', 'nama_merk' => 'Shell'],
            ['kode_merk' => 'MRK-03', 'nama_merk' => 'Stanley'],
        ];
        foreach ($merks as $m) Merk::firstOrCreate(['kode_merk' => $m['kode_merk']], $m);

        $groups = [
            ['kode_group' => 'GRP-01', 'nama_group' => 'Engine'],
            ['kode_group' => 'GRP-02', 'nama_group' => 'Hidrolik'],
            ['kode_group' => 'GRP-03', 'nama_group' => 'Hand Tool'],
        ];
        foreach ($groups as $g) Group::firstOrCreate(['kode_group' => $g['kode_group']], $g);

        $gudangs = [
            ['kode_gudang' => 'GDG-SAW', 'nama_gudang' => 'Sungai Awan',  'alamat' => 'Sungai Awan, Ketapang', 'penanggung_jawab' => 'Pak Andi'],
            ['kode_gudang' => 'GDG-KLP', 'nama_gudang' => 'Kelampa',      'alamat' => 'Kelampa, Ketapang',     'penanggung_jawab' => 'Pak Budi'],
            ['kode_gudang' => 'GDG-PST', 'nama_gudang' => 'Gudang Pusat', 'alamat' => 'Pontianak',             'penanggung_jawab' => 'Pak Citra'],
        ];
        foreach ($gudangs as $gd) Gudang::firstOrCreate(['kode_gudang' => $gd['kode_gudang']], $gd + ['is_active' => true]);

        $barangs = [
            ['kode_barang' => 'BRG-001', 'part_number' => 'FO-90915-YZZD2', 'nama_barang' => 'Filter Oli Mesin',     'category_code' => 'CAT-01', 'sub_category_code' => 'SUB-01', 'merk_code' => 'MRK-01', 'group_code' => 'GRP-01', 'satuan' => 'pcs',   'harga_beli' =>  50000, 'harga_jual' =>  75000, 'min_stok' => 20],
            ['kode_barang' => 'BRG-002', 'part_number' => 'FU-23390-30340', 'nama_barang' => 'Filter Solar',         'category_code' => 'CAT-01', 'sub_category_code' => 'SUB-01', 'merk_code' => 'MRK-01', 'group_code' => 'GRP-01', 'satuan' => 'pcs',   'harga_beli' =>  85000, 'harga_jual' => 120000, 'min_stok' => 15],
            ['kode_barang' => 'BRG-003', 'part_number' => null,             'nama_barang' => 'Oli Mesin Rimula 15W-40','category_code' => 'CAT-02','sub_category_code' => 'SUB-02', 'merk_code' => 'MRK-02', 'group_code' => 'GRP-01', 'satuan' => 'liter', 'harga_beli' =>  45000, 'harga_jual' =>  65000, 'min_stok' => 50],
            ['kode_barang' => 'BRG-004', 'part_number' => null,             'nama_barang' => 'Hydraulic Oil Tellus 46','category_code' => 'CAT-02','sub_category_code' => 'SUB-03', 'merk_code' => 'MRK-02', 'group_code' => 'GRP-02', 'satuan' => 'liter', 'harga_beli' =>  55000, 'harga_jual' =>  80000, 'min_stok' => 30],
            ['kode_barang' => 'BRG-005', 'part_number' => 'STN-94-248',     'nama_barang' => 'Kunci Pas 10mm',        'category_code' => 'CAT-03','sub_category_code' => 'SUB-04', 'merk_code' => 'MRK-03', 'group_code' => 'GRP-03', 'satuan' => 'pcs',   'harga_beli' =>  35000, 'harga_jual' =>  50000, 'min_stok' => 5],
        ];
        foreach ($barangs as $b) Barang::firstOrCreate(['kode_barang' => $b['kode_barang']], $b + ['is_active' => true]);

        // Stok awal — semua barang ada di semua gudang dengan jumlah variatif.
        // Ini lebih baik daripada lewat mutasi di seeder (audit trail = transaksi
        // operasional, bukan inisialisasi sistem).
        $stockMatrix = [
            // [kode_barang, GDG-SAW, GDG-KLP, GDG-PST]
            ['BRG-001', 120,  45, 300],
            ['BRG-002',  80,  30, 250],
            ['BRG-003', 200, 100, 500],
            ['BRG-004',  10,   0, 150],   // sengaja 0 di Kelampa & low di Sungai Awan
            ['BRG-005',  25,  15,  60],
        ];

        foreach ($stockMatrix as $row) {
            [$kode, $sSAW, $sKLP, $sPST] = $row;
            $barang = Barang::where('kode_barang', $kode)->first();
            if (!$barang) continue;
            foreach ([
                ['GDG-SAW', $sSAW],
                ['GDG-KLP', $sKLP],
                ['GDG-PST', $sPST],
            ] as $pair) {
                [$kg, $stok] = $pair;
                $g = Gudang::where('kode_gudang', $kg)->first();
                if (!$g) continue;
                BarangStok::updateOrCreate(
                    ['barang_id' => $barang->id, 'gudang_id' => $g->id],
                    ['stok' => $stok]
                );
            }
        }
    }
}
