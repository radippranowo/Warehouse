<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokLot extends Model
{
    protected $guarded = [];

    protected $fillable = [
        'barang_id',
        'gudang_id',
        'supplier_id',
        'stok_mutasi_id',
        'stok_mutasi_item_id',
        'tanggal',
        'qty_in',
        'qty_sisa',
        'harga_beli',
    ];

    protected $casts = [
        'tanggal'    => 'datetime',
        'qty_in'     => 'integer',
        'qty_sisa'   => 'integer',
        'harga_beli' => 'decimal:2',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function mutasi()
    {
        return $this->belongsTo(StokMutasi::class, 'stok_mutasi_id');
    }

    public function mutasiItem()
    {
        return $this->belongsTo(StokMutasiItem::class, 'stok_mutasi_item_id');
    }

    public function consumptions()
    {
        return $this->hasMany(StokLotConsumption::class);
    }
}
