<?php

namespace App\Livewire\Barang;

use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\BarangImport;

class Import extends Component
{
    use WithFileUploads;

    public $file;

    protected $rules = [
        'file' => 'required|mimes:xlsx,xls,csv|max:10240'
    ];

    public function import()
    {
        $this->validate();

        $path = $this->file->store('imports');

        Excel::import(new BarangImport(), storage_path('app/' . $path));

        session()->flash('success', 'Import selesai. Data barang dan master dibuat/di-update.');

        $this->reset('file');
    }

    public function render()
    {
        return view('livewire.barang.import');
    }
}
