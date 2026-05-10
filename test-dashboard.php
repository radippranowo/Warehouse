<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Barang;
use App\Models\StokMutasi;
use App\Models\BarangStok;
use Illuminate\Support\Facades\DB;

echo "Testing Dashboard Stats...\n\n";

// Stats dasar
$basicStats = [
    'barang'   => DB::table('barangs')->where('is_active', true)->count(),
    'category' => DB::table('categories')->count(),
    'merk'     => DB::table('merks')->count(),
    'group'    => DB::table('groups')->count(),
    'gudang'   => DB::table('gudangs')->where('is_active', true)->count(),
    'supplier' => DB::table('suppliers')->count(),
];

echo "Basic Stats:\n";
print_r($basicStats);

// Stok stats
$stokStats = DB::table('barang_stoks')
    ->select([
        DB::raw('COUNT(DISTINCT barang_id) as total_sku'),
        DB::raw('SUM(stok) as total_stok'),
        DB::raw('SUM(CASE WHEN stok = 0 THEN 1 ELSE 0 END) as stok_kosong'),
        DB::raw('SUM(CASE WHEN stok > 0 AND stok <= COALESCE(min_stok, 0) THEN 1 ELSE 0 END) as stok_rendah'),
    ])
    ->first();

echo "\nStok Stats:\n";
print_r($stokStats);

// Transaksi bulan ini
$startOfMonth = now()->startOfMonth();
$transaksiStats = DB::table('stok_mutasis')
    ->where('tanggal', '>=', $startOfMonth)
    ->where('status', 'approved')
    ->whereNull('cancelled_at')
    ->select([
        DB::raw('COUNT(CASE WHEN tipe = "in" THEN 1 END) as transaksi_masuk'),
        DB::raw('COUNT(CASE WHEN tipe = "out" THEN 1 END) as transaksi_keluar'),
        DB::raw('COUNT(CASE WHEN tipe = "transfer" THEN 1 END) as transaksi_transfer'),
        DB::raw('SUM(CASE WHEN tipe = "in" THEN total_value ELSE 0 END) as nilai_masuk'),
        DB::raw('SUM(CASE WHEN tipe = "out" THEN total_value ELSE 0 END) as nilai_keluar'),
    ])
    ->first();

echo "\nTransaksi Stats:\n";
print_r($transaksiStats);

// Transaksi hari ini
$today = now()->startOfDay();
$transaksiHariIni = DB::table('stok_mutasis')
    ->where('tanggal', '>=', $today)
    ->where('status', 'approved')
    ->whereNull('cancelled_at')
    ->count();

echo "\nTransaksi Hari Ini: $transaksiHariIni\n";

// Barang paling banyak keluar (7 hari terakhir)
$topBarangKeluar = DB::table('stok_mutasi_items')
    ->join('stok_mutasis', 'stok_mutasi_items.stok_mutasi_id', '=', 'stok_mutasis.id')
    ->join('barangs', 'stok_mutasi_items.barang_id', '=', 'barangs.id')
    ->where('stok_mutasis.tipe', 'out')
    ->where('stok_mutasis.status', 'approved')
    ->where('stok_mutasis.tanggal', '>=', now()->subDays(7))
    ->whereNull('stok_mutasis.cancelled_at')
    ->select([
        'barangs.kode_barang',
        'barangs.nama_barang',
        DB::raw('SUM(stok_mutasi_items.qty) as total_qty'),
    ])
    ->groupBy('barangs.id', 'barangs.kode_barang', 'barangs.nama_barang')
    ->orderByDesc('total_qty')
    ->limit(5)
    ->get();

echo "\nTop Barang Keluar:\n";
print_r($topBarangKeluar->toArray());

// Aktivitas terakhir
$aktivitasTerakhir = DB::table('stok_mutasis')
    ->join('users', 'stok_mutasis.user_id', '=', 'users.id')
    ->join('gudangs', 'stok_mutasis.gudang_id', '=', 'gudangs.id')
    ->select([
        'stok_mutasis.id',
        'stok_mutasis.nomor_mutasi',
        'stok_mutasis.tipe',
        'stok_mutasis.tanggal',
        'stok_mutasis.total_qty',
        'stok_mutasis.total_value',
        'users.name as user_name',
        'gudangs.nama_gudang',
    ])
    ->where('stok_mutasis.status', 'approved')
    ->whereNull('stok_mutasis.cancelled_at')
    ->orderByDesc('stok_mutasis.created_at')
    ->limit(10)
    ->get();

echo "\nAktivitas Terakhir (count): " . $aktivitasTerakhir->count() . "\n";

echo "\n✅ All queries executed successfully!\n";
