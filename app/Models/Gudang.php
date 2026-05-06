<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gudang extends Model
{
    protected $guarded = [];
    protected $fillable = [
        'kode_gudang',
        'nama_gudang',
        'alamat',
        'penanggung_jawab',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function stoks()
    {
        return $this->hasMany(BarangStok::class);
    }

    public function barangs()
    {
        return $this->belongsToMany(Barang::class, 'barang_stoks')
            ->withPivot(['stok', 'min_stok'])
            ->withTimestamps();
    }
}
