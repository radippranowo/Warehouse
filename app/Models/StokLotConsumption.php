<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokLotConsumption extends Model
{
    protected $guarded = [];

    protected $fillable = [
        'stok_lot_id',
        'stok_mutasi_item_id',
        'qty',
        'harga_beli',
    ];

    protected $casts = [
        'qty'        => 'integer',
        'harga_beli' => 'decimal:2',
    ];

    public function lot()
    {
        return $this->belongsTo(StokLot::class, 'stok_lot_id');
    }

    public function mutasiItem()
    {
        return $this->belongsTo(StokMutasiItem::class, 'stok_mutasi_item_id');
    }
}
