<?php

namespace App\Imports;

use App\Models\Barang;
use App\Models\Category;
use App\Models\Group;
use App\Models\Merk;
use App\Models\SubCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;


class BarangImport implements ToCollection, WithChunkReading
{
    private array $subCategories = [];
    private array $categories = [];
    private array $groups = [];
    private array $merks = [];

    /** Track kode/part yg sudah pernah ketemu lintas chunk. */
    private array $seenKode = [];     // kode_barang_lower => row_number
    private array $seenPart = [];     // part_number_lower => row_number

    public int $imported = 0;
    public int $skipped = 0;
    public array $errors = [];

    /** Row counter global lintas chunk (1-indexed = matching Excel row number). */
    private int $rowCounter = 0;

    /**
     * Mapping column index → field name. Diisi dari baris header (row 1).
     * Fallback ke default position kalau header gak ke-detect.
     */
    private ?array $columnMap = null;

    /**
     * Sinonim header → field name. Lowercased + non-alnum stripped.
     * Dukung variasi nama yg umum: 'kode', 'kode barang', 'item code', dll.
     */
    private const HEADER_ALIASES = [
        'kode_barang'       => ['kodebarang', 'kode', 'sku', 'itemcode', 'kdbarang'],
        'part_number'       => ['partnumber', 'part', 'partno', 'pn'],
        'nama_barang'       => ['namabarang', 'nama', 'item', 'itemname', 'description', 'deskripsi'],
        'category_code'     => ['kategori', 'kategory', 'category', 'kategoribarang'],
        'sub_category_code' => ['subkategori', 'subkategory', 'subcategory', 'sub'],
        'merk_code'         => ['merk', 'merek', 'brand'],
        'group_code'        => ['group', 'grup', 'kelompok'],
        'satuan'            => ['satuan', 'unit', 'uom'],
        'harga_beli'        => ['hargabeli', 'beli', 'buyprice', 'cost', 'hargacost', 'hpp'],
        'harga_jual'        => ['hargajual', 'jual', 'sellprice', 'price', 'harga'],
        'min_stok'          => ['minstok', 'minstock', 'minimum', 'minqty', 'min'],
        'deskripsi_field'   => ['deskripsi', 'description', 'keterangan', 'note', 'notes'],
    ];

    /** Default position (kalau header gak match apa-apa). */
    private const DEFAULT_COLUMNS = [
        'kode_barang'       => 0,
        'part_number'       => 1,
        'nama_barang'       => 2,
        'category_code'     => 3,
        'sub_category_code' => 4,
        'merk_code'         => 5,
        'group_code'        => 6,
        'satuan'            => 7,
        'harga_beli'        => 8,
        'harga_jual'        => 9,
        'min_stok'          => 10,
        'deskripsi_field'   => 11,
    ];

    public function __construct()
    {
        $this->subCategories = SubCategory::pluck('kode_sub_category', 'nama_sub_category')
            ->mapWithKeys(fn($code, $nama) => [strtolower(trim($nama)) => $code])->toArray();
        $this->categories = Category::pluck('kode_category', 'nama_category')
            ->mapWithKeys(fn($code, $nama) => [strtolower(trim($nama)) => $code])->toArray();
        $this->groups = Group::pluck('kode_group', 'nama_group')
            ->mapWithKeys(fn($code, $nama) => [strtolower(trim($nama)) => $code])->toArray();
        $this->merks = Merk::pluck('kode_merk', 'nama_merk')
            ->mapWithKeys(fn($code, $nama) => [strtolower(trim($nama)) => $code])->toArray();
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function collection(Collection $rows)
    {
        $data = [];
        $rowMap = [];

        foreach ($rows as $row) {
            $this->rowCounter++;
            $excelRow = $this->rowCounter;

            // Baris 1 = header — bangun column map dari sini, lalu skip.
            if ($excelRow === 1) {
                $this->buildColumnMap($row);
                continue;
            }

            // Helper: ambil cell by field name pakai column map.
            $get = fn (string $field) => $this->columnMap !== null && isset($this->columnMap[$field])
                ? ($row[$this->columnMap[$field]] ?? null)
                : null;

            $kode = $this->cleanString($get('kode_barang'));
            $part = $this->cleanString($get('part_number'));
            $nama = $this->cleanString($get('nama_barang'));

            if ($kode === '') {
                $this->skip($excelRow, 'kode_barang kosong');
                continue;
            }
            if ($nama === '') {
                $this->skip($excelRow, "[{$kode}] nama_barang kosong");
                continue;
            }

            $kodeKey = strtolower($kode);
            if (isset($this->seenKode[$kodeKey])) {
                $this->skip($excelRow,
                    "[{$kode}] kode_barang duplikat di Excel (sudah ada di baris #{$this->seenKode[$kodeKey]})");
                continue;
            }
            $this->seenKode[$kodeKey] = $excelRow;

            if ($part !== '') {
                $partKey = strtolower($part);
                if (isset($this->seenPart[$partKey])) {
                    $this->skip($excelRow,
                        "[{$kode}] part_number '{$part}' duplikat di Excel (sudah ada di baris #{$this->seenPart[$partKey]})");
                    continue;
                }
                $this->seenPart[$partKey] = $excelRow;
            }

            $catCode  = $this->resolveCategory($this->cleanString($get('category_code')));
            $subCode  = $this->resolveSubCategory($this->cleanString($get('sub_category_code')));
            $merkCode = $this->resolveMerk($this->cleanString($get('merk_code')));
            $grpCode  = $this->resolveGroup($this->cleanString($get('group_code')));

            $satuan = $this->cleanString($get('satuan')) ?: 'pcs';

            $hargaBeli = $this->cleanNumber($get('harga_beli'));
            $hargaJual = $this->cleanNumber($get('harga_jual'));
            $minStok   = (int) $this->cleanNumber($get('min_stok'));

            $rowData = [
                'kode_barang'       => $kode,
                'part_number'       => $part ?: null,
                'nama_barang'       => $nama,
                'sub_category_code' => $subCode,
                'category_code'     => $catCode,
                'merk_code'         => $merkCode,
                'group_code'        => $grpCode,
                'satuan'            => $satuan,
                'harga_beli'        => $hargaBeli,
                'harga_jual'        => $hargaJual,
                'min_stok'          => $minStok,
                'deskripsi'         => $this->cleanString($get('deskripsi_field')) ?: '-',
                'is_active'         => true,
                'created_at'        => now(),
                'updated_at'        => now(),
            ];

            $rowMap[count($data)] = $excelRow;
            $data[] = $rowData;
        }

        if (!empty($data)) {
            $this->upsertWithFallback($data, $rowMap);
        }

        Cache::forget('barang.masters');
    }

    /**
     * Bangun column map dari header row. Cocokkan nama header ke field via
     * alias list. Kalau ada header yg gak match field manapun → diabaikan.
     * Kalau row 1 bukan header (langsung data), pakai DEFAULT_COLUMNS.
     */
    private function buildColumnMap(mixed $headerRow): void
    {
        $map = [];
        foreach ($headerRow as $i => $cell) {
            $h = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $cell));
            if ($h === '') continue;

            foreach (self::HEADER_ALIASES as $field => $aliases) {
                $fieldNorm = strtolower(preg_replace('/[^a-z0-9]/i', '', $field));
                if ($h === $fieldNorm || in_array($h, $aliases, true)) {
                    if (!isset($map[$field])) $map[$field] = $i;
                    break;
                }
            }
        }

        // Kalau ada field penting yg gak ke-detect, kasih tau user. Bukan
        // error — sekedar info supaya user tau kolom mana yg pakai default.
        $missing = array_diff(array_keys(self::HEADER_ALIASES), array_keys($map));
        if (!empty($missing)) {
            $this->errors[] = 'Kolom Excel tidak ditemukan untuk: '
                . implode(', ', $missing) . ' — pakai default / NULL untuk row baru.';
        }

        if (count($map) < 3) {
            $this->columnMap = self::DEFAULT_COLUMNS;
            $this->errors[] = 'Header row tidak terdeteksi — fallback ke posisi default kolom A-L.';
            return;
        }

        $this->columnMap = $map;
    }

    /**
     * Coba batch upsert. Kalau gagal, retry per-row untuk identifikasi baris
     * spesifik yg bermasalah. Trade-off: kalau gagal massal, jadi lambat —
     * tapi user dapat info per-row, gak buta lagi.
     */
    private function upsertWithFallback(array $data, array $rowMap): void
    {
        // Mapping field DB → key column map. Untuk setiap field yg HADIR di Excel
        // (terdeteksi di columnMap), kita update di DB. Kalau gak ada di Excel
        // → SKIP dari update list, biar nilai existing di DB tetap utuh.
        // (Re-import partial Excel tidak overwrite kolom yg gak diisi.)
        $fieldToMapKey = [
            'part_number'       => 'part_number',
            'nama_barang'       => 'nama_barang',
            'sub_category_code' => 'sub_category_code',
            'category_code'     => 'category_code',
            'merk_code'         => 'merk_code',
            'group_code'        => 'group_code',
            'satuan'            => 'satuan',
            'harga_beli'        => 'harga_beli',
            'harga_jual'        => 'harga_jual',
            'min_stok'          => 'min_stok',
            'deskripsi'         => 'deskripsi_field',
        ];

        $columns = ['updated_at']; // selalu refresh timestamp
        foreach ($fieldToMapKey as $dbCol => $mapKey) {
            if (isset($this->columnMap[$mapKey])) {
                $columns[] = $dbCol;
            }
        }
        // is_active kita selalu update (jaga-jaga, jarang berubah).
        $columns[] = 'is_active';

        foreach (array_chunk($data, 500, true) as $batch) {
            try {
                Barang::upsert(array_values($batch), ['kode_barang'], $columns);
                $this->imported += count($batch);
            } catch (\Throwable) {
                // Batch gagal → fallback per-row supaya tau persis baris mana.
                foreach ($batch as $idx => $row) {
                    try {
                        Barang::upsert([$row], ['kode_barang'], $columns);
                        $this->imported++;
                    } catch (\Throwable $rowErr) {
                        $excelRow = $rowMap[$idx] ?? '?';
                        $kode = $row['kode_barang'];
                        $msg = $this->humanizeDbError($rowErr->getMessage());
                        $this->skip($excelRow, "[{$kode}] {$msg}");
                    }
                }
            }
        }
    }

    /**
     * Terjemahkan pesan SQL error jadi pesan user-friendly.
     */
    private function humanizeDbError(string $sqlMsg): string
    {
        // MySQL duplicate entry: "Duplicate entry 'XXX' for key 'barangs.barangs_part_number_unique'"
        if (preg_match("/Duplicate entry '([^']*)' for key '[^']*?(part_number|kode_barang)/i", $sqlMsg, $m)) {
            $val = $m[1];
            $field = strtolower($m[2]);
            return "{$field} '{$val}' sudah ada di database";
        }
        if (str_contains($sqlMsg, 'Duplicate entry')) {
            return "Duplikat di database — " . Str::limit($sqlMsg, 120);
        }
        if (str_contains(strtolower($sqlMsg), 'data too long')) {
            return "Ada nilai terlalu panjang (cek nama/deskripsi)";
        }
        if (str_contains(strtolower($sqlMsg), 'cannot be null')) {
            return "Ada kolom NULL yg seharusnya wajib";
        }
        // Fallback — tampilkan 100 char pertama dari SQL error.
        return Str::limit($sqlMsg, 120);
    }

    private function skip(int $excelRow, string $reason): void
    {
        $this->skipped++;
        $this->errors[] = "Baris #{$excelRow}: {$reason}";
    }

    /**
     * Bersihkan cell jadi string. Treat '-' sama dgn empty (placeholder umum
     * di Excel untuk "tidak ada data"). Return '' kalau kosong/dash.
     */
    private function cleanString(mixed $value): string
    {
        $s = trim((string) ($value ?? ''));
        if ($s === '' || $s === '-') return '';
        return $s;
    }

    /**
     * Bersihkan cell jadi angka. Treat '-', kosong, atau non-numeric sebagai 0
     * (sesuai keinginan: import jangan gagal cuma karena angka kosong).
     * Support format Excel: "1.000" / "1,000.50" / "Rp 50000".
     */
    private function cleanNumber(mixed $value): float
    {
        if ($value === null || $value === '' || $value === '-') return 0.0;
        // Untuk int/float native (dari Excel cell numeric), pakai langsung.
        // Hindari is_numeric karena "50.000" juga dianggap numeric oleh PHP
        // (= 50.0) — itu yg bikin bug "harga jadi kecil banget".
        if (is_int($value) || is_float($value)) return (float) $value;

        $s = preg_replace('/[^\d.,\-]/', '', (string) $value) ?? '';
        if ($s === '') return 0.0;

        $hasDot   = str_contains($s, '.');
        $hasComma = str_contains($s, ',');

        if ($hasDot && $hasComma) {
            // "1.250,50" (ID) atau "1,250.50" (US). Yg paling kanan = desimal.
            if (strrpos($s, ',') > strrpos($s, '.')) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        } elseif ($hasComma) {
            // "1,250" → ribuan, "1,5" → desimal.
            if (preg_match('/^-?\d{1,3}(,\d{3})+$/', $s)) {
                $s = str_replace(',', '', $s);
            } else {
                $s = str_replace(',', '.', $s);
            }
        } elseif ($hasDot) {
            // KEY FIX: "50.000" (ID ribuan) vs "50.5" (desimal).
            // Pola "X.YYY" atau "X.YYY.ZZZ" dgn semua segment 3-digit → ribuan.
            if (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $s)) {
                $s = str_replace('.', '', $s);
            }
            // Selain itu (mis. "50.5") biarkan sbg desimal.
        }

        return is_numeric($s) ? (float) $s : 0.0;
    }

    private function resolveCategory(string $nama): ?string
    {
        if ($nama === '') return null;
        $key = strtolower($nama);
        if (isset($this->categories[$key])) return $this->categories[$key];

        $kode = $this->generateMasterCode('CAT', $nama, 'kode_category', Category::class);
        Category::create(['kode_category' => $kode, 'nama_category' => ucwords($nama)]);
        return $this->categories[$key] = $kode;
    }

    private function resolveSubCategory(string $nama): ?string
    {
        if ($nama === '') return null;
        $key = strtolower($nama);
        if (isset($this->subCategories[$key])) return $this->subCategories[$key];

        $kode = $this->generateMasterCode('SUB', $nama, 'kode_sub_category', SubCategory::class);
        SubCategory::create(['kode_sub_category' => $kode, 'nama_sub_category' => ucwords($nama)]);
        return $this->subCategories[$key] = $kode;
    }

    private function resolveGroup(string $nama): ?string
    {
        if ($nama === '') return null;
        $key = strtolower($nama);
        if (isset($this->groups[$key])) return $this->groups[$key];

        $kode = $this->generateMasterCode('GRP', $nama, 'kode_group', Group::class);
        Group::create(['kode_group' => $kode, 'nama_group' => ucwords($nama)]);
        return $this->groups[$key] = $kode;
    }

    private function resolveMerk(string $nama): ?string
    {
        if ($nama === '') return null;
        $key = strtolower($nama);
        if (isset($this->merks[$key])) return $this->merks[$key];

        $kode = $this->generateMasterCode('MRK', $nama, 'kode_merk', Merk::class);
        Merk::create(['kode_merk' => $kode, 'nama_merk' => ucwords($nama)]);
        return $this->merks[$key] = $kode;
    }

    private function generateMasterCode(string $prefix, string $nama, string $column, string $modelClass): string
    {
        $slug = strtoupper(Str::slug(substr($nama, 0, 12), '-'));
        $base = "{$prefix}-{$slug}";

        if (!$modelClass::where($column, $base)->exists()) return $base;

        do {
            $kode = $base . '-' . rand(100, 9999);
        } while ($modelClass::where($column, $kode)->exists());

        return $kode;
    }
}
