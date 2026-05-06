<?php

namespace App\Livewire\Barang;

use App\Models\Barang;
use App\Models\Category;
use App\Models\Group;
use App\Models\Merk;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $perPage = 10;

    public $barang_id, $kode_barang, $part_number, $nama_barang,
           $category_code, $merk_code, $group_code, $stok, $harga, $deskripsi;

    protected $listeners = ['setCategory', 'setMerk', 'setGroup'];

    // ✅ RESET PAGE
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    // ✅ RULES
    public function rules()
    {
        return [
            'kode_barang' => 'required|unique:barangs,kode_barang,' . $this->barang_id,
            'part_number' => 'nullable|unique:barangs,part_number,' . $this->barang_id,
            'nama_barang' => 'required|min:3',
            'category_code' => 'required',
            'merk_code' => 'required',
            'group_code' => 'required',
            'stok' => 'nullable|numeric',
            'harga' => 'nullable|numeric',
        ];
    }

    public function messages()
    {
        return [
            'kode_barang.required' => 'Kode wajib diisi',
            'kode_barang.unique'   => 'Kode sudah ada',
            'part_number.unique'   => 'Part number sudah ada',
            'nama_barang.required' => 'Nama wajib diisi',
            'category_code.required' => 'Kategori wajib',
            'merk_code.required' => 'Merk wajib',
            'group_code.required' => 'Group wajib',
        ];
    }

    // ✅ VALIDASI REALTIME
    public function updated($field)
    {
        $this->validateOnly($field, $this->rules(), $this->messages());
    }

    // ✅ SIMPAN / UPDATE
    public function store()
    {
        $this->validate();

        Barang::updateOrCreate(['id' => $this->barang_id], [
            'kode_barang'   => $this->kode_barang,
            'part_number'   => $this->part_number,
            'nama_barang'   => $this->nama_barang,
            'category_code' => $this->category_code,
            'merk_code'     => $this->merk_code,
            'group_code'    => $this->group_code,
            'stok'          => $this->stok,
            'harga'         => $this->harga,
            'deskripsi'     => $this->deskripsi,
        ]);

        $this->dispatch('swal:success', message: 'Data berhasil diubah');
        $this->dispatch('close-modal');

        $this->resetInput();
    }

    // ✅ EDIT TANPA DELAY
    public function edit($id)
    {

        $this->resetErrorBag();
        $this->resetValidation();

        $barang = Barang::findOrFail($id);

        $this->barang_id     = $id;
        $this->kode_barang   = $barang->kode_barang;
        $this->part_number   = $barang->part_number;
        $this->nama_barang   = $barang->nama_barang;
        $this->category_code = $barang->category_code;
        $this->merk_code     = $barang->merk_code;
        $this->group_code    = $barang->group_code;
        $this->stok          = $barang->stok;
        $this->harga         = $barang->harga;
        $this->deskripsi     = $barang->deskripsi;

        $this->dispatch('open-tambah-modal');
    }

    public function delete($id)
    {
        Barang::find($id)->delete();
        $this->dispatch('swal:success', message: 'Data dihapus');
    }

    public function resetInput()
    {
        $this->reset([
            'barang_id','kode_barang','part_number','nama_barang',
            'category_code','merk_code','group_code','stok','harga','deskripsi'
        ]);
    }

    // ✅ FIX SELECT2 (NO ERROR LAGI)
    public function setCategory($value)
    {
        $this->category_code = $value;
    }

    public function setMerk($value)
    {
        $this->merk_code = $value;
    }

    public function setGroup($value)
    {
        $this->group_code = $value;
    }

    public function render()
    {
        $query = Barang::with(['Kategori','Merk','Group']);

        if (trim($this->search) !== '') {
            $query->where('nama_barang', 'like', '%' . $this->search . '%');
        }

        return view('livewire.barang.index', [
            'barangs' => $query->latest()->paginate($this->perPage),
            'categories' => Category::all(),
            'merks' => Merk::all(),
            'groups' => Group::all()
        ]);
    }
}