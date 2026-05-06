<?php

namespace App\Http\Requests;

use App\Models\Barang;
use Illuminate\Foundation\Http\FormRequest;

class StoreBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::rulesArray();
    }

    public static function rulesArray(): array
    {
        return [
            'items'                   => 'required|array|min:1',
            'items.*.kode_barang'     => ['required', 'distinct'],
            'items.*.part_number'     => ['nullable', 'distinct'],
            'items.*.nama_barang'     => 'required|min:3',
            'items.*.category_code'   => 'required',
            'items.*.merk_code'       => 'required',
            'items.*.group_code'      => 'required',
            'items.*.satuan'          => 'nullable|string|max:20',
            'items.*.harga_beli'      => 'nullable|numeric|min:0',
            'items.*.harga_jual'      => 'nullable|numeric|min:0',
            'items.*.min_stok'        => 'nullable|integer|min:0',
            'items.*.deskripsi'       => 'nullable|string',
        ];
    }

    public static function messagesArray(): array
    {
        return [
            'items.*.kode_barang.required'   => 'Kode wajib diisi',
            'items.*.kode_barang.distinct'   => 'Kode duplikat di form',
            'items.*.part_number.distinct'   => 'Part number duplikat di form',
            'items.*.nama_barang.required'   => 'Nama wajib diisi',
            'items.*.nama_barang.min'        => 'Nama minimal 3 karakter',
            'items.*.category_code.required' => 'Kategori wajib',
            'items.*.merk_code.required'     => 'Merk wajib',
            'items.*.group_code.required'    => 'Group wajib',
        ];
    }

    public static function checkExistingDb(array $items, $validator): void
    {
        $kodes = array_filter(array_column($items, 'kode_barang'), fn ($x) => $x !== null && $x !== '');
        $parts = array_filter(array_column($items, 'part_number'), fn ($x) => $x !== null && $x !== '');

        $existKodes = $kodes
            ? Barang::whereIn('kode_barang', $kodes)->pluck('kode_barang')->all()
            : [];
        $existParts = $parts
            ? Barang::whereIn('part_number', $parts)->pluck('part_number')->all()
            : [];

        foreach ($items as $i => $row) {
            if (in_array($row['kode_barang'] ?? null, $existKodes, true)) {
                $validator->errors()->add("items.$i.kode_barang", 'Kode sudah ada di database');
            }
            if (!empty($row['part_number']) && in_array($row['part_number'], $existParts, true)) {
                $validator->errors()->add("items.$i.part_number", 'Part number sudah ada');
            }
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            self::checkExistingDb((array) $this->input('items', []), $v);
        });
    }

    public function messages(): array
    {
        return self::messagesArray();
    }
}
