<?php

use Illuminate\Support\Facades\Route;
use App\Models\Barang;

Route::get('/barangs', function () {
    return Barang::select('id', 'kode_barang', 'nama_barang', 'satuan')
        ->orderBy('kode_barang')
        ->get();
});
