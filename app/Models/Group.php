<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Group extends Model
{
    protected $guarded = [];
    protected $table = 'groups';
    protected $fillable = ['kode_group', 'nama_group'];

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget('barang.masters');
        static::saved($flush);
        static::deleted($flush);
    }
    public function barangs()
    {
        return $this->hasMany(Barang::class, 'group_code', 'kode_group');
    }
}
