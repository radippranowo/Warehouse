<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('sub_category')?->id;

        return [
            'kode_sub_category' => ['required', Rule::unique('sub_categories', 'kode_sub_category')->ignore($id)],
            'nama_sub_category' => ['required', Rule::unique('sub_categories', 'nama_sub_category')->ignore($id)],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_sub_category.required' => 'Kode wajib diisi',
            'kode_sub_category.unique'   => 'Kode sudah ada',
            'nama_sub_category.required' => 'Nama wajib diisi',
            'nama_sub_category.unique'   => 'Nama sudah ada',
        ];
    }
}
