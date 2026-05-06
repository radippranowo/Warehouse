<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Cache 60 detik — counts jarang berubah, no point query setiap visit.
        $stats = Cache::remember('dashboard.stats', 60, function () {
            return [
                'barang'   => DB::table('barangs')->count(),
                'category' => DB::table('categorys')->count(),
                'merk'     => DB::table('merks')->count(),
                'group'    => DB::table('groups')->count(),
            ];
        });

        return Inertia::render('Dashboard/Index', ['stats' => $stats]);
    }
}
