<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SubCategory extends Model
{
    protected $guarded = [];
    protected $table = 'sub_categories';
    protected $fillable = ['kode_sub_category', 'nama_sub_category'];

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget('barang.masters');
        static::saved($flush);
        static::deleted($flush);
    }

    public function barangs()
    {
        return $this->hasMany(Barang::class, 'sub_category_code', 'kode_sub_category');
    }
}
