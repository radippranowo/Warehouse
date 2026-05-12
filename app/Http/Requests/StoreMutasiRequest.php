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
        $tipe = $this->input('tipe');
        
        return [
            'tanggal'             => ['required', 'date'],
            'tipe'                => ['required', Rule::in(['in', 'out', 'transfer', 'adjust'])],
            'gudang_id'           => ['required', 'exists:gudangs,id'],
            'gudang_tujuan_id'    => ['nullable', 'required_if:tipe,transfer', 'different:gudang_id', 'exists:gudangs,id'],
            'supplier_id'         => ['nullable', 'exists:suppliers,id'],
            'referensi'           => ['nullable', 'string', 'max:255'],
            'keterangan'          => ['nullable', 'string'],

            'items'               => ['required', 'array', 'min:1'],
            'items.*.barang_id'   => ['required', 'distinct', 'exists:barangs,id'],
            'items.*.qty'         => [
                'required',
                'numeric',
                // Adjust = qty bisa 0 (set stok jadi 0). Tipe lain harus > 0.
                $tipe === 'adjust' ? 'min:0' : 'min:1',
            ],
            'items.*.harga_satuan'=> ['nullable', 'numeric', 'min:0'],
            'items.*.keterangan'  => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal.required'             => 'Tanggal wajib diisi',
            'tanggal.date'                 => 'Format tanggal tidak valid',
            'gudang_id.required'           => 'Gudang wajib dipilih',
            'gudang_id.exists'             => 'Gudang tidak ditemukan',
            'gudang_tujuan_id.required_if' => 'Gudang tujuan wajib untuk transfer',
            'gudang_tujuan_id.different'   => 'Gudang tujuan harus berbeda dengan asal',
            'gudang_tujuan_id.exists'      => 'Gudang tujuan tidak ditemukan',
            'items.required'               => 'Minimal 1 baris barang harus diisi',
            'items.min'                    => 'Minimal 1 baris barang harus diisi',
            'items.*.barang_id.required'   => 'Barang wajib dipilih',
            'items.*.barang_id.distinct'   => 'Barang tidak boleh duplikat',
            'items.*.barang_id.exists'     => 'Barang tidak ditemukan',
            'items.*.qty.required'         => 'Qty/Stok wajib diisi',
            'items.*.qty.numeric'          => 'Qty/Stok harus berupa angka',
            'items.*.qty.min'              => $this->input('tipe') === 'adjust' 
                ? 'Stok tidak boleh negatif' 
                : 'Qty harus lebih dari 0',
            'items.*.harga_satuan.numeric' => 'Harga satuan harus berupa angka',
            'items.*.harga_satuan.min'     => 'Harga satuan tidak boleh negatif',
            'items.*.keterangan.max'       => 'Keterangan maksimal 500 karakter',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $tipe = $this->input('tipe');
            $items = $this->input('items', []);

            // Validasi khusus untuk penyesuaian stok
            if ($tipe === 'adjust') {
                foreach ($items as $index => $item) {
                    $qty = $item['qty'] ?? null;
                    
                    // Pastikan qty adalah angka valid
                    if ($qty !== null && !is_numeric($qty)) {
                        $validator->errors()->add(
                            "items.{$index}.qty",
                            'Stok baru harus berupa angka yang valid'
                        );
                    }
                    
                    // Validasi range stok (opsional, sesuaikan dengan kebutuhan)
                    if (is_numeric($qty) && $qty > 999999) {
                        $validator->errors()->add(
                            "items.{$index}.qty",
                            'Stok baru terlalu besar (maksimal 999,999)'
                        );
                    }

                    // Validasi barang_id tidak kosong
                    if (empty($item['barang_id'])) {
                        $validator->errors()->add(
                            "items.{$index}.barang_id",
                            'Barang harus dipilih'
                        );
                    }
                }

                // Validasi minimal ada 1 item yang valid
                $validItems = array_filter($items, function($item) {
                    return !empty($item['barang_id']) && isset($item['qty']);
                });

                if (count($validItems) === 0) {
                    $validator->errors()->add(
                        'items',
                        'Minimal harus ada 1 barang dengan stok yang valid'
                    );
                }
            }
        });
    }
}
