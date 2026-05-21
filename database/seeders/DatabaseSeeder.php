<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Roles, permissions, dan user default harus dibuat lebih dulu
        $this->call(RolePermissionSeeder::class);

        // Data master logistik (kategori, merk, gudang, barang, dll)
        $this->call(LogistikSeeder::class);

        // Data supplier
        $this->call(SupplierSeeder::class);
    }
}
