<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('barang');

        return [
            'kode_barang'   => ['required', Rule::unique('barangs', 'kode_barang')->ignore($id)],
            'part_number'   => ['nullable', Rule::unique('barangs', 'part_number')->ignore($id)],
            'nama_barang'   => 'required|min:3',
            'category_code' => 'required',
            'merk_code'     => 'required',
            'group_code'    => 'required',
            'satuan'        => 'nullable|string|max:20',
            'harga_beli'    => 'nullable|numeric|min:0',
            'harga_jual'    => 'nullable|numeric|min:0',
            'min_stok'      => 'nullable|integer|min:0',
            'is_active'     => 'nullable|boolean',
            'deskripsi'     => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'kode_barang.required'   => 'Kode wajib diisi',
            'kode_barang.unique'     => 'Kode sudah ada',
            'part_number.unique'     => 'Part number sudah ada',
            'nama_barang.required'   => 'Nama wajib diisi',
            'nama_barang.min'        => 'Nama minimal 3 karakter',
            'category_code.required' => 'Kategori wajib',
            'merk_code.required'     => 'Merk wajib',
            'group_code.required'    => 'Group wajib',
        ];
    }
}
