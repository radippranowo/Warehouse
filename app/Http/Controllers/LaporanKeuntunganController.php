<?php

namespace App\Http\Controllers;

use App\Models\StokMutasi;
use App\Models\StokMutasiItem;
use App\Models\Barang;
use App\Models\Gudang;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class LaporanKeuntunganController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $perPage = $request->input('per_page', 10);
        $gudangId = $request->input('gudang_id', '');
        $startDate = $request->input('start_date', '');
        $endDate = $request->input('end_date', '');

        // Subquery untuk mendapatkan harga beli dari transaksi masuk terakhir
        $hargaBeliSubquery = "(
            SELECT smi_in.harga_satuan
            FROM stok_mutasi_items as smi_in
            INNER JOIN stok_mutasis as sm_in ON smi_in.stok_mutasi_id = sm_in.id
            WHERE smi_in.barang_id = stok_mutasi_items.barang_id
                AND sm_in.gudang_id = stok_mutasis.gudang_id
                AND sm_in.tipe = 'in'
                AND sm_in.status = 'approved'
                AND sm_in.tanggal <= stok_mutasis.tanggal
            ORDER BY sm_in.tanggal DESC, sm_in.id DESC
            LIMIT 1
        )";

        // Query untuk barang keluar yang sudah approved
        $query = StokMutasiItem::query()
            ->select([
                'stok_mutasi_items.id',
                'stok_mutasi_items.barang_id',
                'stok_mutasi_items.qty',
                'stok_mutasi_items.harga_satuan as harga_jual_aktual',
                'barangs.kode_barang',
                'barangs.nama_barang',
                'barangs.satuan',
                DB::raw("COALESCE({$hargaBeliSubquery}, barangs.harga_beli, 0) as harga_beli"),
                'barangs.harga_jual',
                'stok_mutasis.nomor_mutasi',
                'stok_mutasis.tanggal',
                'stok_mutasis.gudang_id',
                'gudangs.nama_gudang',
                DB::raw("(stok_mutasi_items.harga_satuan - COALESCE({$hargaBeliSubquery}, barangs.harga_beli, 0)) as keuntungan_per_unit"),
                DB::raw("((stok_mutasi_items.harga_satuan - COALESCE({$hargaBeliSubquery}, barangs.harga_beli, 0)) * stok_mutasi_items.qty) as total_keuntungan"),
                DB::raw("(COALESCE({$hargaBeliSubquery}, barangs.harga_beli, 0) * stok_mutasi_items.qty) as total_modal"),
                DB::raw('(stok_mutasi_items.harga_satuan * stok_mutasi_items.qty) as total_penjualan_item')
            ])
            ->join('stok_mutasis', 'stok_mutasi_items.stok_mutasi_id', '=', 'stok_mutasis.id')
            ->join('barangs', 'stok_mutasi_items.barang_id', '=', 'barangs.id')
            ->join('gudangs', 'stok_mutasis.gudang_id', '=', 'gudangs.id')
            ->where('stok_mutasis.tipe', 'out')
            ->where('stok_mutasis.status', 'approved');

        // Filter by search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('barangs.kode_barang', 'like', "%{$search}%")
                    ->orWhere('barangs.nama_barang', 'like', "%{$search}%")
                    ->orWhere('stok_mutasis.nomor_mutasi', 'like', "%{$search}%");
            });
        }

        // Filter by gudang
        if ($gudangId) {
            $query->where('stok_mutasis.gudang_id', $gudangId);
        }

        // Filter by date range
        if ($startDate) {
            $query->whereDate('stok_mutasis.tanggal', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('stok_mutasis.tanggal', '<=', $endDate);
        }

        // Order by tanggal descending
        $query->orderBy('stok_mutasis.tanggal', 'desc')
            ->orderBy('stok_mutasis.nomor_mutasi', 'desc');

        $items = $query->paginate($perPage)->withQueryString();

        // Calculate summary dengan subquery yang sama
        $summary = StokMutasiItem::query()
            ->select([
                DB::raw('SUM(stok_mutasi_items.qty) as total_qty'),
                DB::raw("SUM(stok_mutasi_items.qty * COALESCE({$hargaBeliSubquery}, barangs.harga_beli, 0)) as total_modal"),
                DB::raw('SUM(stok_mutasi_items.qty * stok_mutasi_items.harga_satuan) as total_penjualan'),
                DB::raw("SUM((stok_mutasi_items.harga_satuan - COALESCE({$hargaBeliSubquery}, barangs.harga_beli, 0)) * stok_mutasi_items.qty) as total_keuntungan")
            ])
            ->join('stok_mutasis', 'stok_mutasi_items.stok_mutasi_id', '=', 'stok_mutasis.id')
            ->join('barangs', 'stok_mutasi_items.barang_id', '=', 'barangs.id')
            ->where('stok_mutasis.tipe', 'out')
            ->where('stok_mutasis.status', 'approved');

        // Apply same filters to summary
        if ($search) {
            $summary->where(function ($q) use ($search) {
                $q->where('barangs.kode_barang', 'like', "%{$search}%")
                    ->orWhere('barangs.nama_barang', 'like', "%{$search}%")
                    ->orWhere('stok_mutasis.nomor_mutasi', 'like', "%{$search}%");
            });
        }
        if ($gudangId) {
            $summary->where('stok_mutasis.gudang_id', $gudangId);
        }
        if ($startDate) {
            $summary->whereDate('stok_mutasis.tanggal', '>=', $startDate);
        }
        if ($endDate) {
            $summary->whereDate('stok_mutasis.tanggal', '<=', $endDate);
        }

        $summaryData = $summary->first();

        // Get gudang list for filter
        $gudangs = Gudang::select('id', 'kode_gudang', 'nama_gudang')
            ->orderBy('nama_gudang')
            ->get();

        return Inertia::render('LaporanKeuntungan/Index', [
            'items' => $items,
            'summary' => [
                'total_qty' => $summaryData->total_qty ?? 0,
                'total_modal' => $summaryData->total_modal ?? 0,
                'total_penjualan' => $summaryData->total_penjualan ?? 0,
                'total_keuntungan' => $summaryData->total_keuntungan ?? 0,
                'margin_persen' => $summaryData->total_penjualan > 0 
                    ? (($summaryData->total_keuntungan / $summaryData->total_penjualan) * 100) 
                    : 0,
            ],
            'gudangs' => $gudangs,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
                'gudang_id' => $gudangId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
}
