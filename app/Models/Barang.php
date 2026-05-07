<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $guarded = [];
    protected $fillable = [
        'kode_barang',
        'part_number',
        'nama_barang',
        'category_code',
        'sub_category_code',
        'merk_code',
        'group_code',
        'satuan',
        'harga_beli',
        'harga_jual',
        'min_stok',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'harga_beli' => 'decimal:2',
        'harga_jual' => 'decimal:2',
        'min_stok'   => 'integer',
        'is_active'  => 'boolean',
    ];

    public function kategori()
    {
        return $this->belongsTo(Category::class, 'category_code', 'kode_category');
    }

    public function subKategori()
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_code', 'kode_sub_category');
    }

    public function merk()
    {
        return $this->belongsTo(Merk::class, 'merk_code', 'kode_merk');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_code', 'kode_group');
    }

    public function stoks()
    {
        return $this->hasMany(BarangStok::class);
    }

    public function gudangs()
    {
        return $this->belongsToMany(Gudang::class, 'barang_stoks')
            ->withPivot(['stok', 'min_stok'])
            ->withTimestamps();
    }

    public function mutasiItems()
    {
        return $this->hasMany(StokMutasiItem::class);
    }
}
