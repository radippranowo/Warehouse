<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GudangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('gudang')?->id;

        return [
            'kode_gudang'      => ['required', 'string', 'max:50', Rule::unique('gudangs', 'kode_gudang')->ignore($id)],
            'nama_gudang'      => ['required', 'string', 'max:255', Rule::unique('gudangs', 'nama_gudang')->ignore($id)],
            'alamat'           => ['nullable', 'string'],
            'penanggung_jawab' => ['nullable', 'string', 'max:255'],
            'is_active'        => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_gudang.required' => 'Kode wajib diisi',
            'kode_gudang.unique'   => 'Kode sudah ada',
            'nama_gudang.required' => 'Nama wajib diisi',
            'nama_gudang.unique'   => 'Nama sudah ada',
        ];
    }
}
