<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokMutasiItem extends Model
{
    protected $guarded = [];
    protected $fillable = [
        'stok_mutasi_id',
        'barang_id',
        'qty',
        'harga_satuan',
        'stok_sebelum',
        'stok_sesudah',
        'keterangan',
    ];

    protected $casts = [
        'qty'          => 'integer',
        'harga_satuan' => 'decimal:2',
        'stok_sebelum' => 'integer',
        'stok_sesudah' => 'integer',
    ];

    // Accessor untuk subtotal (computed)
    public function getSubtotalAttribute()
    {
        return $this->qty * $this->harga_satuan;
    }

    public function mutasi()
    {
        return $this->belongsTo(StokMutasi::class, 'stok_mutasi_id');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }
}
