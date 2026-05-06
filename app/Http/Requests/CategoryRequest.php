<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('category')?->id;

        return [
            'kode_category' => ['required', Rule::unique('categories', 'kode_category')->ignore($id)],
            'nama_category' => ['required', Rule::unique('categories', 'nama_category')->ignore($id)],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_category.required' => 'Kode wajib diisi',
            'kode_category.unique'   => 'Kode sudah ada',
            'nama_category.required' => 'Nama wajib diisi',
            'nama_category.unique'   => 'Nama sudah ada',
        ];
    }
}
