<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Permissions
        $permissions = [
            // Dashboard
            ['name' => 'dashboard.view', 'display_name' => 'Lihat Dashboard', 'module' => 'dashboard'],
            
            // Master Data - Barang
            ['name' => 'barang.view', 'display_name' => 'Lihat Barang', 'module' => 'barang'],
            ['name' => 'barang.create', 'display_name' => 'Tambah Barang', 'module' => 'barang'],
            ['name' => 'barang.edit', 'display_name' => 'Edit Barang', 'module' => 'barang'],
            ['name' => 'barang.delete', 'display_name' => 'Hapus Barang', 'module' => 'barang'],
            ['name' => 'barang.import', 'display_name' => 'Import Barang', 'module' => 'barang'],
            ['name' => 'barang.export', 'display_name' => 'Export Barang', 'module' => 'barang'],
            
            // Master Data - Category
            ['name' => 'category.view', 'display_name' => 'Lihat Kategori', 'module' => 'category'],
            ['name' => 'category.create', 'display_name' => 'Tambah Kategori', 'module' => 'category'],
            ['name' => 'category.edit', 'display_name' => 'Edit Kategori', 'module' => 'category'],
            ['name' => 'category.delete', 'display_name' => 'Hapus Kategori', 'module' => 'category'],
            
            // Master Data - Merk
            ['name' => 'merk.view', 'display_name' => 'Lihat Merk', 'module' => 'merk'],
            ['name' => 'merk.create', 'display_name' => 'Tambah Merk', 'module' => 'merk'],
            ['name' => 'merk.edit', 'display_name' => 'Edit Merk', 'module' => 'merk'],
            ['name' => 'merk.delete', 'display_name' => 'Hapus Merk', 'module' => 'merk'],
            
            // Master Data - Group
            ['name' => 'group.view', 'display_name' => 'Lihat Group', 'module' => 'group'],
            ['name' => 'group.create', 'display_name' => 'Tambah Group', 'module' => 'group'],
            ['name' => 'group.edit', 'display_name' => 'Edit Group', 'module' => 'group'],
            ['name' => 'group.delete', 'display_name' => 'Hapus Group', 'module' => 'group'],
            
            // Master Data - Gudang
            ['name' => 'gudang.view', 'display_name' => 'Lihat Gudang', 'module' => 'gudang'],
            ['name' => 'gudang.create', 'display_name' => 'Tambah Gudang', 'module' => 'gudang'],
            ['name' => 'gudang.edit', 'display_name' => 'Edit Gudang', 'module' => 'gudang'],
            ['name' => 'gudang.delete', 'display_name' => 'Hapus Gudang', 'module' => 'gudang'],
            
            // Master Data - Supplier
            ['name' => 'supplier.view', 'display_name' => 'Lihat Supplier', 'module' => 'supplier'],
            ['name' => 'supplier.create', 'display_name' => 'Tambah Supplier', 'module' => 'supplier'],
            ['name' => 'supplier.edit', 'display_name' => 'Edit Supplier', 'module' => 'supplier'],
            ['name' => 'supplier.delete', 'display_name' => 'Hapus Supplier', 'module' => 'supplier'],
            
            // Stok
            ['name' => 'stok.view', 'display_name' => 'Lihat Stok', 'module' => 'stok'],
            ['name' => 'stok.import', 'display_name' => 'Import Stok', 'module' => 'stok'],
            ['name' => 'stok.export', 'display_name' => 'Export Stok', 'module' => 'stok'],
            ['name' => 'stok.clear', 'display_name' => 'Clear Stok', 'module' => 'stok'],
            
            // Transaksi - Barang Masuk
            ['name' => 'mutasi.masuk.view', 'display_name' => 'Lihat Barang Masuk', 'module' => 'mutasi'],
            ['name' => 'mutasi.masuk.create', 'display_name' => 'Tambah Barang Masuk', 'module' => 'mutasi'],
            ['name' => 'mutasi.masuk.edit', 'display_name' => 'Edit Barang Masuk', 'module' => 'mutasi'],
            ['name' => 'mutasi.masuk.delete', 'display_name' => 'Hapus Barang Masuk', 'module' => 'mutasi'],
            ['name' => 'mutasi.masuk.approve', 'display_name' => 'Approve Barang Masuk', 'module' => 'mutasi'],
            
            // Transaksi - Barang Keluar
            ['name' => 'mutasi.keluar.view', 'display_name' => 'Lihat Barang Keluar', 'module' => 'mutasi'],
            ['name' => 'mutasi.keluar.create', 'display_name' => 'Tambah Barang Keluar', 'module' => 'mutasi'],
            ['name' => 'mutasi.keluar.edit', 'display_name' => 'Edit Barang Keluar', 'module' => 'mutasi'],
            ['name' => 'mutasi.keluar.delete', 'display_name' => 'Hapus Barang Keluar', 'module' => 'mutasi'],
            ['name' => 'mutasi.keluar.approve', 'display_name' => 'Approve Barang Keluar', 'module' => 'mutasi'],
            
            // Transaksi - Transfer
            ['name' => 'mutasi.transfer.view', 'display_name' => 'Lihat Transfer', 'module' => 'mutasi'],
            ['name' => 'mutasi.transfer.create', 'display_name' => 'Tambah Transfer', 'module' => 'mutasi'],
            ['name' => 'mutasi.transfer.edit', 'display_name' => 'Edit Transfer', 'module' => 'mutasi'],
            ['name' => 'mutasi.transfer.delete', 'display_name' => 'Hapus Transfer', 'module' => 'mutasi'],
            ['name' => 'mutasi.transfer.approve', 'display_name' => 'Approve Transfer', 'module' => 'mutasi'],
            
            // Transaksi - Penyesuaian
            ['name' => 'mutasi.adjust.view', 'display_name' => 'Lihat Penyesuaian', 'module' => 'mutasi'],
            ['name' => 'mutasi.adjust.create', 'display_name' => 'Tambah Penyesuaian', 'module' => 'mutasi'],
            ['name' => 'mutasi.adjust.edit', 'display_name' => 'Edit Penyesuaian', 'module' => 'mutasi'],
            ['name' => 'mutasi.adjust.delete', 'display_name' => 'Hapus Penyesuaian', 'module' => 'mutasi'],
            ['name' => 'mutasi.adjust.approve', 'display_name' => 'Approve Penyesuaian', 'module' => 'mutasi'],
            
            // Laporan
            ['name' => 'laporan.stok', 'display_name' => 'Laporan Stok', 'module' => 'laporan'],
            ['name' => 'laporan.mutasi', 'display_name' => 'Laporan Mutasi', 'module' => 'laporan'],
            ['name' => 'laporan.keuntungan', 'display_name' => 'Laporan Keuntungan', 'module' => 'laporan'],
            ['name' => 'laporan.export', 'display_name' => 'Export Laporan', 'module' => 'laporan'],
            
            // User Management
            ['name' => 'user.view', 'display_name' => 'Lihat User', 'module' => 'user'],
            ['name' => 'user.create', 'display_name' => 'Tambah User', 'module' => 'user'],
            ['name' => 'user.edit', 'display_name' => 'Edit User', 'module' => 'user'],
            ['name' => 'user.delete', 'display_name' => 'Hapus User', 'module' => 'user'],
            
            // Role Management
            ['name' => 'role.view', 'display_name' => 'Lihat Role', 'module' => 'role'],
            ['name' => 'role.create', 'display_name' => 'Tambah Role', 'module' => 'role'],
            ['name' => 'role.edit', 'display_name' => 'Edit Role', 'module' => 'role'],
            ['name' => 'role.delete', 'display_name' => 'Hapus Role', 'module' => 'role'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // Create Roles
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => 'Administrator',
                'description' => 'Full access ke semua fitur sistem',
                'is_active' => true,
            ]
        );

        $managerRole = Role::firstOrCreate(
            ['name' => 'manager'],
            [
                'display_name' => 'Manager',
                'description' => 'Akses ke semua fitur kecuali user management',
                'is_active' => true,
            ]
        );

        $staffRole = Role::firstOrCreate(
            ['name' => 'staff'],
            [
                'display_name' => 'Staff Gudang',
                'description' => 'Akses ke transaksi dan stok',
                'is_active' => true,
            ]
        );

        $viewerRole = Role::firstOrCreate(
            ['name' => 'viewer'],
            [
                'display_name' => 'Viewer',
                'description' => 'Hanya bisa melihat data, tidak bisa edit/hapus',
                'is_active' => true,
            ]
        );

        // Assign all permissions to admin
        $adminRole->permissions()->sync(Permission::all()->pluck('id'));

        // Assign permissions to manager (all except user/role management)
        $managerPermissions = Permission::whereNotIn('module', ['user', 'role'])->pluck('id');
        $managerRole->permissions()->sync($managerPermissions);

        // Assign permissions to staff (transaksi, stok, view master data)
        $staffPermissions = Permission::where(function ($query) {
            $query->where('module', 'dashboard')
                ->orWhere('module', 'stok')
                ->orWhere('module', 'mutasi')
                ->orWhere(function ($q) {
                    $q->whereIn('module', ['barang', 'category', 'merk', 'group', 'gudang', 'supplier'])
                      ->where('name', 'like', '%.view');
                });
        })->pluck('id');
        $staffRole->permissions()->sync($staffPermissions);

        // Assign permissions to viewer (only view permissions)
        $viewerPermissions = Permission::where('name', 'like', '%.view')
            ->orWhere('module', 'dashboard')
            ->pluck('id');
        $viewerRole->permissions()->sync($viewerPermissions);

        // Create default admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@warehouse.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('admin123'),
                'role_id' => $adminRole->id,
                'is_active' => true,
            ]
        );

        // Create manager user
        $managerUser = User::firstOrCreate(
            ['email' => 'manager@warehouse.com'],
            [
                'name' => 'Manager Gudang',
                'password' => Hash::make('manager123'),
                'role_id' => $managerRole->id,
                'is_active' => true,
            ]
        );

        // Create staff user
        $staffUser = User::firstOrCreate(
            ['email' => 'staff@warehouse.com'],
            [
                'name' => 'Staff Gudang',
                'password' => Hash::make('staff123'),
                'role_id' => $staffRole->id,
                'is_active' => true,
            ]
        );

        // Create viewer user
        $viewerUser = User::firstOrCreate(
            ['email' => 'viewer@warehouse.com'],
            [
                'name' => 'Viewer',
                'password' => Hash::make('viewer123'),
                'role_id' => $viewerRole->id,
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Roles and Permissions seeded successfully!');
        $this->command->info('');
        $this->command->info('Default Users:');
        $this->command->info('1. Admin: admin@warehouse.com / admin123');
        $this->command->info('2. Manager: manager@warehouse.com / manager123');
        $this->command->info('3. Staff: staff@warehouse.com / staff123');
        $this->command->info('4. Viewer: viewer@warehouse.com / viewer123');
    }
}
