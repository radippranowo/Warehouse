<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Merk extends Model
{
    protected $guarded = [];
    protected $table = 'merks';
    protected $fillable = ['kode_merk', 'nama_merk'];

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget('barang.masters');
        static::saved($flush);
        static::deleted($flush);
    }
    public function barangs()
    {
        return $this->hasMany(Barang::class, 'merk_code', 'kode_merk');
    }
}
