<?php

namespace App\Imports;

use App\Models\Barang;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

/**
 * Import stok per gudang dari Excel.
 *
 * Format kolom Excel (baris 1 = header):
 *   A: kode_barang   (wajib)
 *   B: stok          (wajib, angka)
 *
 * Strategi:
 *  - Per row: cari barang by kode_barang. Kalau tidak ada → skip + log error.
 *  - INSERT ... ON DUPLICATE KEY UPDATE stok = stok + VALUES(stok).
 *    Behavior: TAMBAH ke stok existing (mis. delivery / penerimaan barang).
 *    Konsekuensi: re-import file yg sama akan menggandakan stok. Pakai
 *    fitur "Reset Stok" dulu kalau mau idempotent.
 *  - Stok global di tampilan otomatis ke-sum via withSum di BarangController.
 */
class StokImport implements ToCollection, WithChunkReading
{
    private array $kodeMap = [];   // kode_barang_lower => id (cache lookup)

    public int $imported = 0;
    public int $skipped = 0;
    public array $errors = [];

    private int $rowCounter = 0;
    private ?array $columnMap = null;

    private const ALIASES = [
        'kode_barang' => ['kodebarang', 'kode', 'sku', 'itemcode', 'kdbarang'],
        'stok'        => ['stok', 'stock', 'qty', 'quantity', 'jumlah'],
    ];

    public function __construct(public int $gudangId)
    {
        // Pre-load semua barang code → id, biar lookup cepat tanpa query per row.
        $this->kodeMap = Barang::pluck('id', 'kode_barang')
            ->mapWithKeys(fn ($id, $kode) => [strtolower(trim($kode)) => $id])
            ->toArray();
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function collection(Collection $rows)
    {
        $upsertData = [];
        $now = now();

        foreach ($rows as $row) {
            $this->rowCounter++;
            $excelRow = $this->rowCounter;

            if ($excelRow === 1) {
                $this->buildColumnMap($row);
                continue;
            }

            $get = fn (string $field) => $this->columnMap !== null && isset($this->columnMap[$field])
                ? ($row[$this->columnMap[$field]] ?? null)
                : null;

            $kode = trim((string) ($get('kode_barang') ?? ''));
            $stokRaw = $get('stok');

            if ($kode === '' || $kode === '-') {
                $this->skip($excelRow, 'kode_barang kosong');
                continue;
            }

            // Lookup barang_id dari kode_barang.
            $barangId = $this->kodeMap[strtolower($kode)] ?? null;
            if ($barangId === null) {
                $this->skip($excelRow, "[{$kode}] kode_barang tidak ditemukan di database");
                continue;
            }

            // Validasi stok numeric non-negatif.
            if ($stokRaw === null || $stokRaw === '' || !is_numeric($stokRaw)) {
                $this->skip($excelRow, "[{$kode}] stok bukan angka: '{$stokRaw}'");
                continue;
            }
            $stok = (int) $stokRaw;
            if ($stok < 0) {
                $this->skip($excelRow, "[{$kode}] stok negatif: {$stok}");
                continue;
            }

            $upsertData[] = [
                'barang_id'  => $barangId,
                'gudang_id'  => $this->gudangId,
                'stok'       => $stok,
                'min_stok'   => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $this->imported++;
        }

        if (!empty($upsertData)) {
            $this->bulkIncrement($upsertData);
        }
    }

    /**
     * Bulk INSERT ... ON DUPLICATE KEY UPDATE stok = stok + VALUES(stok).
     * MySQL only. Unique key pada (barang_id, gudang_id) sudah ada di migration.
     */
    private function bulkIncrement(array $rows): void
    {
        foreach (array_chunk($rows, 500) as $batch) {
            $placeholders = [];
            $bindings = [];
            foreach ($batch as $r) {
                $placeholders[] = '(?, ?, ?, ?, ?, ?)';
                $bindings[] = $r['barang_id'];
                $bindings[] = $r['gudang_id'];
                $bindings[] = $r['stok'];
                $bindings[] = $r['min_stok'];
                $bindings[] = $r['created_at'];
                $bindings[] = $r['updated_at'];
            }
            $sql = 'INSERT INTO barang_stoks (barang_id, gudang_id, stok, min_stok, created_at, updated_at) VALUES '
                 . implode(', ', $placeholders)
                 . ' ON DUPLICATE KEY UPDATE stok = stok + VALUES(stok), updated_at = VALUES(updated_at)';
            DB::statement($sql, $bindings);
        }
    }

    private function buildColumnMap(mixed $headerRow): void
    {
        $map = [];
        foreach ($headerRow as $i => $cell) {
            $h = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $cell));
            if ($h === '') continue;

            foreach (self::ALIASES as $field => $aliases) {
                $fieldNorm = strtolower(preg_replace('/[^a-z0-9]/i', '', $field));
                if ($h === $fieldNorm || in_array($h, $aliases, true)) {
                    if (!isset($map[$field])) $map[$field] = $i;
                    break;
                }
            }
        }

        // Default position kalau header gak ke-detect.
        if (count($map) < 2) {
            $map = ['kode_barang' => 0, 'stok' => 1];
            $this->errors[] = 'Header tidak terdeteksi — fallback: kolom A=kode_barang, B=stok';
        }

        $missing = array_diff(['kode_barang', 'stok'], array_keys($map));
        if (!empty($missing)) {
            $this->errors[] = 'Kolom Excel tidak ditemukan: ' . implode(', ', $missing);
        }

        $this->columnMap = $map;
    }

    private function skip(int $excelRow, string $reason): void
    {
        $this->skipped++;
        $this->errors[] = "Baris #{$excelRow}: {$reason}";
    }
}
