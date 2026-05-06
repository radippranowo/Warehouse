<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\Barang;
use App\Models\Category;
use App\Models\Komponen;
use App\Models\Group;
use App\Models\Merk;


class BarangImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        // Ambil semua data master (biar tidak query berulang)
        $categories = Category::all()->mapWithKeys(function ($c) {
            return [strtolower(trim($c->nama_category)) => $c->kode_category];
        })->toArray();

        $komponens = Komponen::all()->mapWithKeys(function ($k) {
            return [strtolower(trim($k->nama_komponen)) => $k->id];
        })->toArray();

        $groups = Group::all()->mapWithKeys(function ($g) {
            return [strtolower(trim($g->nama_group)) => $g->kode_group];
        })->toArray();

        $merks = Merk::all()->mapWithKeys(function ($m) {
            return [strtolower(trim($m->nama_merk)) => $m->kode_merk];
        })->toArray();

        $data = [];

        foreach ($rows as $index => $row) {

            // Skip header excel
            if ($index == 0) continue;

            // Rapikan data (hindari typo & spasi)
            $categoryName = strtolower(trim((string) ($row[3] ?? '')));
            $komponenName = strtolower(trim((string) ($row[4] ?? '')));
            $groupName = strtolower(trim((string) ($row[5] ?? '')));
            $merkName = strtolower(trim((string) ($row[6] ?? '')));

            // CATEGORY -> simpan/ambil kode_category
            if ($categoryName !== '' && !isset($categories[$categoryName])) {
                $kode = strtoupper('CAT-' . Str::slug(substr($categoryName, 0, 20)) . '-' . rand(100, 999));
                $cat = Category::create([
                    'kode_category' => $kode,
                    'nama_category' => ucwords($categoryName)
                ]);
                $categories[$categoryName] = $cat->kode_category;
            }

            // KOMPONEN (sub kategori) -> simpan nama, simpan id
            if ($komponenName !== '' && !isset($komponens[$komponenName])) {
                $komp = Komponen::create([
                    'nama_komponen' => ucwords($komponenName)
                ]);
                $komponens[$komponenName] = $komp->id;
            }

            // GROUP -> simpan/ambil kode_group
            if ($groupName !== '' && !isset($groups[$groupName])) {
                $kode = strtoupper('GRP-' . Str::slug(substr($groupName, 0, 20)) . '-' . rand(100, 999));
                $grp = Group::create([
                    'kode_group' => $kode,
                    'nama_group' => ucwords($groupName)
                ]);
                $groups[$groupName] = $grp->kode_group;
            }

            // MERK -> simpan/ambil kode_merk
            if ($merkName !== '' && !isset($merks[$merkName])) {
                $kode = strtoupper('MRK-' . Str::slug(substr($merkName, 0, 20)) . '-' . rand(100, 999));
                $merk = Merk::create([
                    'kode_merk' => $kode,
                    'nama_merk' => ucwords($merkName)
                ]);
                $merks[$merkName] = $merk->kode_merk;
            }

            $data[] = [
                'kode_barang' => trim((string) ($row[0] ?? '')),
                'part_number' => trim((string) ($row[1] ?? '')),
                'nama_barang' => trim((string) ($row[2] ?? '')),
                'category_code' => $categories[$categoryName] ?? null,
                'komponen_id' => $komponens[$komponenName] ?? null,
                'group_code' => $groups[$groupName] ?? null,
                'merk_code' => $merks[$merkName] ?? null,
                'stok' => is_numeric($row[7] ?? null) ? (int) $row[7] : 0,
                'harga' => is_numeric($row[8] ?? null) ? (float) $row[8] : 0,
                'deskripsi' => trim((string) ($row[9] ?? '')),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        // Upsert (insert atau update berdasarkan `kode_barang`)
        if (!empty($data)) {
            foreach (array_chunk($data, 500) as $chunk) {
                Barang::upsert($chunk, ['kode_barang'], [
                    'part_number', 'nama_barang', 'category_code', 'komponen_id', 'group_code', 'merk_code', 'stok', 'harga', 'deskripsi', 'updated_at'
                ]);
            }
        }
    }
}