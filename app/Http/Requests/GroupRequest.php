<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('group')?->id;

        return [
            'kode_group' => ['required', Rule::unique('groups', 'kode_group')->ignore($id)],
            'nama_group' => ['required', Rule::unique('groups', 'nama_group')->ignore($id)],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_group.required' => 'Kode wajib diisi',
            'kode_group.unique'   => 'Kode sudah ada',
            'nama_group.required' => 'Nama wajib diisi',
            'nama_group.unique'   => 'Nama sudah ada',
        ];
    }
}
