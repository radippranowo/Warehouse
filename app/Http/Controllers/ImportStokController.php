<?php

namespace App\Http\Controllers;

use App\Imports\StokImport;
use App\Models\Gudang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ImportStokController extends Controller
{
    public function processImport(Request $request)
    {
        $data = $request->validate([
            'gudang_id' => ['required', 'integer', 'exists:gudangs,id'],
            'file'      => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:20480'],
        ], [
            'gudang_id.required' => 'Pilih gudang tujuan dulu',
            'gudang_id.exists'   => 'Gudang tidak valid',
            'file.required'      => 'File Excel wajib dipilih',
            'file.mimes'         => 'Format harus .xlsx / .xls / .csv',
            'file.max'           => 'Ukuran file maksimal 20MB',
        ]);

        $importer = new StokImport((int) $data['gudang_id']);
        $gudang = Gudang::find($data['gudang_id']);

        try {
            Excel::import($importer, $request->file('file'));
        } catch (\Throwable $e) {
            return back()->with('import_result', [
                'status'   => 'error',
                'message'  => 'Import gagal: ' . $e->getMessage(),
                'imported' => $importer->imported,
                'skipped'  => $importer->skipped,
                'errors'   => $importer->errors,
                'gudang'   => $gudang->nama_gudang ?? '?',
            ]);
        }

        // Stok berubah → invalidate summary cache untuk gudang itu.
        Cache::forget("stok.summary.{$data['gudang_id']}");

        $hasIssue = count($importer->errors) > 0 || $importer->skipped > 0;
        return back()->with('import_result', [
            'status'   => $hasIssue ? 'partial' : 'success',
            'message'  => $hasIssue
                ? "Import ke {$gudang->nama_gudang} selesai dgn catatan — {$importer->imported} berhasil, {$importer->skipped} dilewati"
                : "{$importer->imported} stok ter-import ke {$gudang->nama_gudang}",
            'imported' => $importer->imported,
            'skipped'  => $importer->skipped,
            'errors'   => $importer->errors,
            'gudang'   => $gudang->nama_gudang,
        ]);
    }

    public function clearStok(Request $request)
    {
        $data = $request->validate([
            'confirm'   => ['required', 'string', 'in:HAPUS'],
            'gudang_id' => ['nullable', 'integer', 'exists:gudangs,id'],
        ], [
            'confirm.in' => "Ketik 'HAPUS' untuk konfirmasi",
        ]);

        $gudangId = $data['gudang_id'] ?? null;

        $count = DB::transaction(function () use ($gudangId) {
            $q = DB::table('barang_stoks');
            if ($gudangId) $q->where('gudang_id', $gudangId);
            return $q->delete(); // returns affected row count, hemat 1 query
        });

        // Invalidate summary cache.
        if ($gudangId) {
            Cache::forget("stok.summary.{$gudangId}");
        } else {
            DB::table('gudangs')->pluck('id')->each(fn ($id) => Cache::forget("stok.summary.{$id}"));
        }

        $gudangName = $gudangId
            ? (Gudang::where('id', $gudangId)->value('nama_gudang') ?? '?')
            : 'SEMUA gudang';

        return back()->with('import_result', [
            'status'   => 'success',
            'message'  => "Stok di {$gudangName} direset — {$count} record dihapus",
            'imported' => 0,
            'skipped'  => 0,
            'errors'   => [],
            'gudang'   => $gudangName,
        ]);
    }
}
