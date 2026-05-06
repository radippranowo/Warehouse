<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangStok extends Model
{
    protected $guarded = [];
    protected $fillable = [
        'barang_id',
        'gudang_id',
        'stok',
        'min_stok',
    ];

    protected $casts = [
        'stok'     => 'integer',
        'min_stok' => 'integer',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }
}
