<?php

namespace App\Exports;

use App\Models\Gudang;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StokGudangExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected int $gudangId,
        protected ?string $search = null,
        protected bool $lowOnly = false,
    ) {}

    public function collection()
    {
        $query = DB::table('barangs')
            ->select([
                'barangs.kode_barang',
                'barangs.part_number',
                'barangs.nama_barang',
                'categories.nama_category',
                'merks.nama_merk',
                'groups.nama_group',
                'barangs.satuan',
                'barangs.harga_jual',
                DB::raw('COALESCE(barang_stoks.stok, 0) as stok'),
                DB::raw('COALESCE(barang_stoks.min_stok, barangs.min_stok, 0) as min_stok'),
            ])
            ->leftJoin('barang_stoks', function ($j) {
                $j->on('barang_stoks.barang_id', '=', 'barangs.id')
                  ->where('barang_stoks.gudang_id', '=', $this->gudangId);
            })
            ->leftJoin('categories', 'barangs.category_code', '=', 'categories.kode_category')
            ->leftJoin('merks', 'barangs.merk_code', '=', 'merks.kode_merk')
            ->leftJoin('groups', 'barangs.group_code', '=', 'groups.kode_group')
            ->where('barangs.is_active', true);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('barangs.kode_barang', 'like', $this->search . '%')
                  ->orWhere('barangs.part_number', 'like', $this->search . '%')
                  ->orWhere('barangs.nama_barang', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->lowOnly) {
            $query->havingRaw('stok <= min_stok');
        }

        return $query->orderBy('barangs.nama_barang')->get();
    }

    public function headings(): array
    {
        return ['Kode', 'Part Number', 'Nama Barang', 'Kategori', 'Merk', 'Group',
            'Satuan', 'Stok', 'Min Stok', 'Harga Jual', 'Nilai Stok', 'Status'];
    }

    public function map($r): array
    {
        $stok = (int) $r->stok;
        $min  = (int) $r->min_stok;
        $status = $stok === 0 ? 'Kosong' : ($stok <= $min ? 'Di bawah min' : 'Aman');
        return [
            $r->kode_barang,
            $r->part_number,
            $r->nama_barang,
            $r->nama_category,
            $r->nama_merk,
            $r->nama_group,
            $r->satuan,
            $stok,
            $min,
            (float) $r->harga_jual,
            $stok * (float) $r->harga_jual,
            $status,
        ];
    }

    public function title(): string
    {
        $g = Gudang::find($this->gudangId);
        return $g ? substr('Stok ' . $g->nama_gudang, 0, 31) : 'Stok';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E9ECEF']]],
        ];
    }
}
