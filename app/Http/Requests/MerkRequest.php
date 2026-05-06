<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MerkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('merk')?->id;

        return [
            'kode_merk' => ['required', Rule::unique('merks', 'kode_merk')->ignore($id)],
            'nama_merk' => ['required', Rule::unique('merks', 'nama_merk')->ignore($id)],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_merk.required' => 'Kode wajib diisi',
            'kode_merk.unique'   => 'Kode sudah ada',
            'nama_merk.required' => 'Nama wajib diisi',
            'nama_merk.unique'   => 'Nama sudah ada',
        ];
    }
}
