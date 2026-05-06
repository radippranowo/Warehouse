<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMutasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tanggal'             => ['required', 'date'],
            'tipe'                => ['required', Rule::in(['in', 'out', 'transfer', 'adjust'])],
            'gudang_id'           => ['required', 'exists:gudangs,id'],
            'gudang_tujuan_id'    => ['nullable', 'required_if:tipe,transfer', 'different:gudang_id', 'exists:gudangs,id'],
            'referensi'           => ['nullable', 'string', 'max:255'],
            'keterangan'          => ['nullable', 'string'],

            'items'               => ['required', 'array', 'min:1'],
            'items.*.barang_id'   => ['required', 'distinct', 'exists:barangs,id'],
            'items.*.qty'         => ['required', 'integer', 'min:0'],
            'items.*.harga_satuan'=> ['nullable', 'numeric', 'min:0'],
            'items.*.keterangan'  => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'gudang_tujuan_id.required_if' => 'Gudang tujuan wajib untuk transfer',
            'gudang_tujuan_id.different'   => 'Gudang tujuan harus berbeda dengan asal',
            'items.required'               => 'Minimal 1 baris barang',
            'items.*.barang_id.required'   => 'Barang wajib',
            'items.*.barang_id.distinct'   => 'Barang duplikat di form',
            'items.*.qty.required'         => 'Qty wajib',
        ];
    }
}
