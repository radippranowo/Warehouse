<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    protected $guarded = [];
    protected $table = 'categorys';
    protected $fillable = ['kode_category', 'nama_category'];

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget('barang.masters');
        static::saved($flush);
        static::deleted($flush);
    }
    public function barangs() {
        
        // return $this->hasMany(Barang::class);
        return $this->hasMany(Barang::class, 'category_code', 'kode_category');
        
    }
    
    
}
