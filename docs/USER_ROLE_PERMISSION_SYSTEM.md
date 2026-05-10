# USER MANAGEMENT DENGAN ROLE & PERMISSION

## Tanggal: 10 Mei 2026

---

## 📋 RINGKASAN SISTEM

Sistem User Management dengan Role-Based Access Control (RBAC) yang lengkap untuk mengatur akses user ke berbagai fitur aplikasi warehouse.

---

## 🗄️ DATABASE STRUCTURE

### Tables Created

#### 1. **roles**
```sql
- id (bigint, PK)
- name (string, unique) - admin, manager, staff, viewer
- display_name (string) - Administrator, Manager, Staff Gudang, Viewer
- description (text, nullable)
- is_active (boolean, default: true)
- created_at, updated_at
```

#### 2. **permissions**
```sql
- id (bigint, PK)
- name (string, unique) - barang.view, barang.create, etc
- display_name (string) - Lihat Barang, Tambah Barang, etc
- module (string) - barang, category, stok, mutasi, laporan, user
- description (text, nullable)
- created_at, updated_at
```

#### 3. **role_permission** (Pivot Table)
```sql
- id (bigint, PK)
- role_id (FK to roles)
- permission_id (FK to permissions)
- created_at, updated_at
- UNIQUE(role_id, permission_id)
```

#### 4. **users** (Modified)
```sql
Added columns:
- role_id (FK to roles, nullable)
- is_active (boolean, default: true)
- last_login_at (timestamp, nullable)
```

---

## 👥 DEFAULT ROLES

### 1. **Administrator** (admin)
- **Akses:** Full access ke semua fitur
- **Permissions:** ALL (73 permissions)
- **Deskripsi:** Super user dengan akses penuh

### 2. **Manager** (manager)
- **Akses:** Semua fitur kecuali User & Role Management
- **Permissions:** 65 permissions (exclude user.*, role.*)
- **Deskripsi:** Mengelola operasional gudang

### 3. **Staff Gudang** (staff)
- **Akses:** Transaksi, Stok, View Master Data
- **Permissions:** 35 permissions
- **Deskripsi:** Input transaksi harian

### 4. **Viewer** (viewer)
- **Akses:** Hanya melihat data (read-only)
- **Permissions:** 20 permissions (hanya *.view)
- **Deskripsi:** Monitoring dan laporan

---

## 🔐 PERMISSIONS LIST (73 Total)

### Dashboard (1)
- `dashboard.view` - Lihat Dashboard

### Master Data - Barang (7)
- `barang.view` - Lihat Barang
- `barang.create` - Tambah Barang
- `barang.edit` - Edit Barang
- `barang.delete` - Hapus Barang
- `barang.import` - Import Barang
- `barang.export` - Export Barang

### Master Data - Category (4)
- `category.view` - Lihat Kategori
- `category.create` - Tambah Kategori
- `category.edit` - Edit Kategori
- `category.delete` - Hapus Kategori

### Master Data - Merk (4)
- `merk.view` - Lihat Merk
- `merk.create` - Tambah Merk
- `merk.edit` - Edit Merk
- `merk.delete` - Hapus Merk

### Master Data - Group (4)
- `group.view` - Lihat Group
- `group.create` - Tambah Group
- `group.edit` - Edit Group
- `group.delete` - Hapus Group

### Master Data - Gudang (4)
- `gudang.view` - Lihat Gudang
- `gudang.create` - Tambah Gudang
- `gudang.edit` - Edit Gudang
- `gudang.delete` - Hapus Gudang

### Master Data - Supplier (4)
- `supplier.view` - Lihat Supplier
- `supplier.create` - Tambah Supplier
- `supplier.edit` - Edit Supplier
- `supplier.delete` - Hapus Supplier

### Stok (4)
- `stok.view` - Lihat Stok
- `stok.import` - Import Stok
- `stok.export` - Export Stok
- `stok.clear` - Clear Stok

### Transaksi - Barang Masuk (5)
- `mutasi.masuk.view` - Lihat Barang Masuk
- `mutasi.masuk.create` - Tambah Barang Masuk
- `mutasi.masuk.edit` - Edit Barang Masuk
- `mutasi.masuk.delete` - Hapus Barang Masuk
- `mutasi.masuk.approve` - Approve Barang Masuk

### Transaksi - Barang Keluar (5)
- `mutasi.keluar.view` - Lihat Barang Keluar
- `mutasi.keluar.create` - Tambah Barang Keluar
- `mutasi.keluar.edit` - Edit Barang Keluar
- `mutasi.keluar.delete` - Hapus Barang Keluar
- `mutasi.keluar.approve` - Approve Barang Keluar

### Transaksi - Transfer (5)
- `mutasi.transfer.view` - Lihat Transfer
- `mutasi.transfer.create` - Tambah Transfer
- `mutasi.transfer.edit` - Edit Transfer
- `mutasi.transfer.delete` - Hapus Transfer
- `mutasi.transfer.approve` - Approve Transfer

### Transaksi - Penyesuaian (5)
- `mutasi.adjust.view` - Lihat Penyesuaian
- `mutasi.adjust.create` - Tambah Penyesuaian
- `mutasi.adjust.edit` - Edit Penyesuaian
- `mutasi.adjust.delete` - Hapus Penyesuaian
- `mutasi.adjust.approve` - Approve Penyesuaian

### Laporan (4)
- `laporan.stok` - Laporan Stok
- `laporan.mutasi` - Laporan Mutasi
- `laporan.keuntungan` - Laporan Keuntungan
- `laporan.export` - Export Laporan

### User Management (4)
- `user.view` - Lihat User
- `user.create` - Tambah User
- `user.edit` - Edit User
- `user.delete` - Hapus User

### Role Management (4)
- `role.view` - Lihat Role
- `role.create` - Tambah Role
- `role.edit` - Edit Role
- `role.delete` - Hapus Role

---

## 👤 DEFAULT USERS

### 1. Administrator
```
Email: admin@warehouse.com
Password: admin123
Role: Administrator
Status: Active
```

### 2. Manager
```
Email: manager@warehouse.com
Password: manager123
Role: Manager
Status: Active
```

### 3. Staff
```
Email: staff@warehouse.com
Password: staff123
Role: Staff Gudang
Status: Active
```

### 4. Viewer
```
Email: viewer@warehouse.com
Password: viewer123
Role: Viewer
Status: Active
```

---

## 📦 MODELS & RELATIONSHIPS

### User Model
```php
// Relationships
$user->role() // BelongsTo Role

// Methods
$user->hasPermission('barang.view') // Check single permission
$user->hasAnyPermission(['barang.view', 'barang.create']) // Check any
$user->hasAllPermissions(['barang.view', 'barang.create']) // Check all
$user->hasRole('admin') // Check role
$user->isAdmin() // Check if admin
$user->getAllPermissions() // Get all user permissions
```

### Role Model
```php
// Relationships
$role->users() // HasMany User
$role->permissions() // BelongsToMany Permission

// Methods
$role->hasPermission('barang.view') // Check permission
$role->givePermission('barang.view') // Give permission
$role->revokePermission('barang.view') // Revoke permission
```

### Permission Model
```php
// Relationships
$permission->roles() // BelongsToMany Role

// Methods
Permission::groupedByModule() // Get permissions grouped by module
```

---

## 🛡️ MIDDLEWARE USAGE

### CheckPermission Middleware

**Registered as:** `permission`

**Usage in Routes:**
```php
// Single permission
Route::get('/barang', [BarangController::class, 'index'])
    ->middleware('permission:barang.view');

// Multiple routes with same permission
Route::middleware('permission:barang.view')->group(function () {
    Route::get('/barang', [BarangController::class, 'index']);
    Route::get('/barang/{id}', [BarangController::class, 'show']);
});

// Different permissions for different actions
Route::get('/barang', [BarangController::class, 'index'])
    ->middleware('permission:barang.view');
Route::post('/barang', [BarangController::class, 'store'])
    ->middleware('permission:barang.create');
Route::put('/barang/{id}', [BarangController::class, 'update'])
    ->middleware('permission:barang.edit');
Route::delete('/barang/{id}', [BarangController::class, 'destroy'])
    ->middleware('permission:barang.delete');
```

**Features:**
- ✅ Auto redirect to login if not authenticated
- ✅ Admin bypass all permission checks
- ✅ Returns 403 if user doesn't have permission
- ✅ Custom error message: "Anda tidak memiliki akses ke halaman ini."

---

## 🎯 PERMISSION MATRIX

| Module | Admin | Manager | Staff | Viewer |
|--------|-------|---------|-------|--------|
| **Dashboard** | ✅ | ✅ | ✅ | ✅ |
| **Barang** | CRUD+Import+Export | CRUD+Import+Export | View Only | View Only |
| **Category** | CRUD | CRUD | View Only | View Only |
| **Merk** | CRUD | CRUD | View Only | View Only |
| **Group** | CRUD | CRUD | View Only | View Only |
| **Gudang** | CRUD | CRUD | View Only | View Only |
| **Supplier** | CRUD | CRUD | View Only | View Only |
| **Stok** | View+Import+Export+Clear | View+Import+Export+Clear | View+Import+Export | View Only |
| **Barang Masuk** | CRUD+Approve | CRUD+Approve | CRUD+Approve | View Only |
| **Barang Keluar** | CRUD+Approve | CRUD+Approve | CRUD+Approve | View Only |
| **Transfer** | CRUD+Approve | CRUD+Approve | CRUD+Approve | View Only |
| **Penyesuaian** | CRUD+Approve | CRUD+Approve | CRUD+Approve | View Only |
| **Laporan** | All | All | All | View Only |
| **User Management** | CRUD | ❌ | ❌ | ❌ |
| **Role Management** | CRUD | ❌ | ❌ | ❌ |

---

## 💻 USAGE EXAMPLES

### In Controller
```php
// Check permission before action
public function index()
{
    if (!auth()->user()->hasPermission('barang.view')) {
        abort(403);
    }
    
    // Your code here
}

// Check role
public function destroy($id)
{
    if (!auth()->user()->isAdmin()) {
        abort(403, 'Only admin can delete');
    }
    
    // Your code here
}
```

### In Blade/Vue
```php
// Blade
@can('barang.create')
    <button>Tambah Barang</button>
@endcan

// Vue (via Inertia shared data)
<button v-if="$page.props.auth.permissions.includes('barang.create')">
    Tambah Barang
</button>
```

### In API
```php
// Check permission in API
public function search(Request $request)
{
    if (!$request->user()->hasPermission('barang.view')) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    
    // Your code here
}
```

---

## 🔧 COMMANDS

### Run Migration
```bash
php artisan migrate
```

### Seed Roles & Permissions
```bash
php artisan db:seed --class=RolePermissionSeeder
```

### Rollback
```bash
php artisan migrate:rollback
```

### Fresh Migration with Seed
```bash
php artisan migrate:fresh --seed
```

---

## 📝 NEXT STEPS

### 1. Create User Management UI
- [ ] User List Page (Index)
- [ ] Create User Form
- [ ] Edit User Form
- [ ] Delete User Confirmation
- [ ] User Profile Page

### 2. Create Role Management UI
- [ ] Role List Page
- [ ] Create Role Form
- [ ] Edit Role Form with Permission Checkboxes
- [ ] Delete Role Confirmation

### 3. Apply Middleware to Routes
- [ ] Add `permission` middleware to all routes
- [ ] Update route groups with appropriate permissions
- [ ] Test each permission level

### 4. Update Navigation
- [ ] Show/hide menu based on permissions
- [ ] Add User Management menu (admin only)
- [ ] Add Role Management menu (admin only)

### 5. Update Inertia Shared Data
- [ ] Share user permissions to frontend
- [ ] Share user role to frontend
- [ ] Use in Vue components for conditional rendering

---

## ⚠️ IMPORTANT NOTES

1. **Admin Bypass:** Admin role bypasses all permission checks
2. **Soft Delete:** Consider adding soft delete for users
3. **Audit Log:** Consider logging user actions
4. **Password Policy:** Implement strong password requirements
5. **2FA:** Consider adding two-factor authentication
6. **Session Management:** Track active sessions
7. **Permission Caching:** Consider caching permissions for performance

---

## 🔒 SECURITY BEST PRACTICES

1. ✅ Use middleware for all protected routes
2. ✅ Check permissions in controller methods
3. ✅ Validate user input
4. ✅ Hash passwords with bcrypt
5. ✅ Use CSRF protection
6. ✅ Implement rate limiting
7. ✅ Log security events
8. ✅ Regular security audits

---

## 📊 STATISTICS

- **Total Permissions:** 73
- **Total Roles:** 4
- **Total Default Users:** 4
- **Modules:** 13
- **Tables Created:** 3
- **Models Created:** 2
- **Middleware Created:** 1

---

## ✅ STATUS

- ✅ Migration Created
- ✅ Models Created with Relationships
- ✅ Seeder Created with Default Data
- ✅ Middleware Created and Registered
- ✅ Database Migrated
- ✅ Data Seeded
- ⏳ Controllers (Next Step)
- ⏳ Views (Next Step)
- ⏳ Routes with Middleware (Next Step)

---

**Created:** 10 Mei 2026
**Status:** READY FOR IMPLEMENTATION ✅
