# QUICK REFERENCE - USER ROLE & PERMISSION

## 🚀 LOGIN CREDENTIALS

```
Admin:
Email: admin@warehouse.com
Password: admin123
Access: FULL ACCESS

Manager:
Email: manager@warehouse.com
Password: manager123
Access: All except User/Role Management

Staff:
Email: staff@warehouse.com
Password: staff123
Access: Transaksi, Stok, View Master Data

Viewer:
Email: viewer@warehouse.com
Password: viewer123
Access: Read Only
```

---

## 🔐 PERMISSION NAMING CONVENTION

Format: `{module}.{action}`

**Actions:**
- `view` - Lihat/Read
- `create` - Tambah/Create
- `edit` - Edit/Update
- `delete` - Hapus/Delete
- `import` - Import Data
- `export` - Export Data
- `approve` - Approve Transaksi
- `clear` - Clear Data

**Examples:**
- `barang.view` - Lihat Barang
- `barang.create` - Tambah Barang
- `mutasi.masuk.approve` - Approve Barang Masuk

---

## 💻 CODE EXAMPLES

### Check Permission in Controller
```php
// Method 1: Using middleware
Route::get('/barang', [BarangController::class, 'index'])
    ->middleware('permission:barang.view');

// Method 2: In controller
public function index()
{
    if (!auth()->user()->hasPermission('barang.view')) {
        abort(403);
    }
}

// Method 3: Check multiple permissions
if (auth()->user()->hasAnyPermission(['barang.view', 'barang.create'])) {
    // User has at least one permission
}

if (auth()->user()->hasAllPermissions(['barang.view', 'barang.edit'])) {
    // User has all permissions
}
```

### Check Role
```php
// Check specific role
if (auth()->user()->hasRole('admin')) {
    // User is admin
}

// Check if admin (shortcut)
if (auth()->user()->isAdmin()) {
    // User is admin
}
```

### Get User Permissions
```php
// Get all user permissions
$permissions = auth()->user()->getAllPermissions();

// Get role
$role = auth()->user()->role;
```

### In Vue/Blade
```vue
<!-- Vue (Inertia) -->
<button v-if="$page.props.auth.permissions.includes('barang.create')">
    Tambah Barang
</button>

<!-- Blade -->
@can('barang.create')
    <button>Tambah Barang</button>
@endcan
```

---

## 🛠️ ROLE MANAGEMENT

### Give Permission to Role
```php
$role = Role::find(1);
$role->givePermission('barang.view');
// or
$role->givePermission(Permission::find(1));
```

### Revoke Permission from Role
```php
$role->revokePermission('barang.view');
```

### Check if Role has Permission
```php
if ($role->hasPermission('barang.view')) {
    // Role has permission
}
```

### Sync Permissions (Replace all)
```php
$role->permissions()->sync([1, 2, 3]); // Permission IDs
```

---

## 📋 PERMISSION MODULES

| Module | Permissions | Description |
|--------|-------------|-------------|
| `dashboard` | view | Dashboard access |
| `barang` | view, create, edit, delete, import, export | Master Barang |
| `category` | view, create, edit, delete | Master Category |
| `merk` | view, create, edit, delete | Master Merk |
| `group` | view, create, edit, delete | Master Group |
| `gudang` | view, create, edit, delete | Master Gudang |
| `supplier` | view, create, edit, delete | Master Supplier |
| `stok` | view, import, export, clear | Stok Management |
| `mutasi` | masuk.*, keluar.*, transfer.*, adjust.* | Transaksi |
| `laporan` | stok, mutasi, keuntungan, export | Laporan |
| `user` | view, create, edit, delete | User Management |
| `role` | view, create, edit, delete | Role Management |

---

## 🎯 PERMISSION BY ROLE

### Administrator (73 permissions)
- ✅ ALL PERMISSIONS

### Manager (65 permissions)
- ✅ Dashboard
- ✅ All Master Data (CRUD)
- ✅ Stok (All)
- ✅ All Transaksi (CRUD + Approve)
- ✅ All Laporan
- ❌ User Management
- ❌ Role Management

### Staff Gudang (35 permissions)
- ✅ Dashboard
- ✅ Master Data (View Only)
- ✅ Stok (View, Import, Export)
- ✅ All Transaksi (CRUD + Approve)
- ✅ All Laporan
- ❌ Master Data (Create, Edit, Delete)
- ❌ Stok Clear
- ❌ User Management
- ❌ Role Management

### Viewer (20 permissions)
- ✅ Dashboard
- ✅ All View Permissions
- ❌ Create, Edit, Delete
- ❌ Import, Export
- ❌ Approve
- ❌ User Management
- ❌ Role Management

---

## 🔄 COMMON TASKS

### Create New User
```php
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => Hash::make('password'),
    'role_id' => 3, // Staff role
    'is_active' => true,
]);
```

### Change User Role
```php
$user = User::find(1);
$user->role_id = 2; // Manager role
$user->save();
```

### Deactivate User
```php
$user = User::find(1);
$user->is_active = false;
$user->save();
```

### Create New Role
```php
$role = Role::create([
    'name' => 'supervisor',
    'display_name' => 'Supervisor',
    'description' => 'Supervisor gudang',
    'is_active' => true,
]);

// Assign permissions
$permissions = Permission::whereIn('module', ['stok', 'mutasi'])->pluck('id');
$role->permissions()->sync($permissions);
```

---

## 🚨 TROUBLESHOOTING

### User can't access page
1. Check if user is active: `$user->is_active`
2. Check if user has role: `$user->role`
3. Check if role has permission: `$user->role->hasPermission('permission.name')`
4. Check middleware is applied to route

### Permission not working
1. Clear cache: `php artisan optimize:clear`
2. Check permission name spelling
3. Check middleware alias in bootstrap/app.php
4. Check if admin bypass is working

### Can't login
1. Check user is active
2. Check password is correct
3. Check email is correct
4. Check database connection

---

## 📞 SUPPORT

For issues or questions:
1. Check documentation: `docs/USER_ROLE_PERMISSION_SYSTEM.md`
2. Check this quick reference
3. Check Laravel logs: `storage/logs/laravel.log`

---

**Last Updated:** 10 Mei 2026
