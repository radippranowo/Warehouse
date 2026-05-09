<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokMutasi extends Model
{
    protected $guarded = [];
    protected $fillable = [
        'nomor_mutasi',
        'tanggal',
        'tipe',
        'gudang_id',
        'gudang_tujuan_id',
        'supplier_id',
        'referensi',
        'keterangan',
        'user_id',
        'total_qty',
        'total_value',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected $casts = [
        'tanggal'     => 'datetime',
        'total_qty'   => 'integer',
        'total_value' => 'decimal:2',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items()
    {
        return $this->hasMany(StokMutasiItem::class);
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function gudangTujuan()
    {
        return $this->belongsTo(Gudang::class, 'gudang_tujuan_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
