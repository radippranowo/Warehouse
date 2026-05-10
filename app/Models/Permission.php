<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'module',
        'description',
    ];

    /**
     * Permission belongs to many roles
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission')
            ->withTimestamps();
    }

    /**
     * Get permissions grouped by module
     */
    public static function groupedByModule()
    {
        return static::orderBy('module')->orderBy('name')->get()->groupBy('module');
    }

    /**
     * Convert permission name to human-readable format
     */
    public static function humanReadableName($name)
    {
        // Mapping untuk action ke bahasa Indonesia
        $actionMap = [
            'view' => 'Lihat',
            'create' => 'Tambah',
            'edit' => 'Edit',
            'update' => 'Update',
            'delete' => 'Hapus',
            'restore' => 'Pulihkan',
            'force-delete' => 'Hapus Permanen',
            'export' => 'Export',
            'import' => 'Import',
            'print' => 'Cetak',
            'approve' => 'Setujui',
            'reject' => 'Tolak',
            'manage' => 'Kelola',
            'access' => 'Akses',
        ];

        // Mapping untuk module ke bahasa Indonesia
        $moduleMap = [
            'dashboard' => 'Dashboard',
            'barang' => 'Barang',
            'category' => 'Kategori',
            'sub_category' => 'Sub Kategori',
            'merk' => 'Merk',
            'group' => 'Group',
            'gudang' => 'Gudang',
            'supplier' => 'Supplier',
            'mutasi' => 'Mutasi',
            'stok' => 'Stok',
            'laporan' => 'Laporan',
            'user' => 'User',
            'role' => 'Role',
            'permission' => 'Permission',
            'barang-masuk' => 'Barang Masuk',
            'barang-keluar' => 'Barang Keluar',
            'penyesuaian-stok' => 'Penyesuaian Stok',
        ];

        // Parse permission name (format: module.action atau action)
        $parts = explode('.', $name);

        if (count($parts) === 2) {
            [$module, $action] = $parts;
            $moduleText = $moduleMap[$module] ?? ucfirst(str_replace(['-', '_'], ' ', $module));
            $actionText = $actionMap[$action] ?? ucfirst(str_replace(['-', '_'], ' ', $action));
            return "{$actionText} {$moduleText}";
        } elseif (count($parts) === 1) {
            $action = $parts[0];
            return $actionMap[$action] ?? ucfirst(str_replace(['-', '_'], ' ', $action));
        }

        return $name;
    }
}
