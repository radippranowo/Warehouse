<?php

namespace App\Livewire\Category;

use App\Models\Category;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $category_id, $kode_category, $nama_category;
    public $search = '';
    public $isEdit = false;

    public $perPage = 5; // Default menampilkan 10 data

    // Pastikan saat jumlah perPage diubah, halaman balik ke nomor 1
   
   
    public function updatingPerPage()
    {
        $this->resetPage();
    }

     public function updatingSearch()
    {
        $this->resetPage();
    }
    
    // ✅ RULES
 public function rules()
{
    return [
          'kode_category' => 'required|unique:categorys,kode_category,' . $this->category_id,
          'nama_category' => 'required|unique:categorys,nama_category,' . $this->category_id,
    ];
}

    public function messages()
    {
        return [
            'kode_category.required' => 'Kode wajib diisi',
            'kode_category.unique'   => 'Kode sudah ada',
            'nama_category.required'   => 'Kode wajib diisi',
            'nama_category.unique'   => 'Nama sudah ada',
        ];
    }



    // Fungsi Simpan (Tambah & Update)
    public function store()
    {
        $this->validate();

        Category::updateOrCreate(['id' => $this->category_id], [
            'kode_category'   => $this->kode_category,
            'nama_category'   => $this->nama_category,
           
        ]);

        $this->dispatch('swal:success', message: 'Data berhasil diubah');
        $this->dispatch('close-modal');

        $this->resetInput();
    }

        public function updated($field)
    {
        $this->validateOnly($field, $this->rules(), $this->messages());
    }

    public function edit($id)
    {

        $this->resetErrorBag();
        $this->resetValidation();

        $cat = Category::findOrFail($id);

        $this->category_id = $id;
        $this->kode_category = $cat->kode_category;
        $this->nama_category = $cat->nama_category;
       
        $this->dispatch('open-tambah-modal');
    }

    

 public function delete($id)
{
    $category = Category::findOrFail($id);

    if ($category->barangs()->exists()) {
        $this->dispatch('swal:error', [
            'title' => 'Gagal Hapus!',
            'text'  => "Kategori '{$category->nama_category}' tidak bisa dihapus karena masih digunakan oleh data barang."
        ]);
        return;
    }

    $category->delete();
     $this->dispatch('swal:success', message: 'Categori berhasil dihapus!');
}
    public function resetInput()
    {
        $this->reset(['category_id', 'kode_category', 'nama_category', 'isEdit']);
        
    }

    public function render()
    {
        return view('livewire.category.index', [
            'categories' => Category::where('nama_category', 'like', '%' . $this->search . '%')
                ->orWhere('kode_category', 'like', '%' . $this->search . '%')
                ->latest()
                 ->paginate($this->perPage),
        ]);
    }
}
