# 🧪 Testing Quick Reference

## 🔐 Test Credentials

| Role | Email | Password | Access Level |
|------|-------|----------|--------------|
| **Admin** | admin@warehouse.com | admin123 | Full Access (73 permissions) |
| **Manager** | manager@warehouse.com | manager123 | Almost Full (65 permissions) |
| **Staff** | staff@warehouse.com | staff123 | Operational (35 permissions) |
| **Viewer** | viewer@warehouse.com | viewer123 | Read-Only (20 permissions) |

---

## ✅ Test Scenarios

### 1. Login & Authentication ✅
```
✓ Login as admin -> Success
✓ Login as inactive user -> Error: "Akun tidak aktif"
✓ Login with wrong password -> Error: "auth.failed"
✓ Login 6 times wrong -> Rate limited
✓ Check last_login_at updated in database
```

### 2. Admin Access ✅
```
✓ See "User Management" menu
✓ Access /users -> Success
✓ Access /roles -> Success
✓ Create user -> Success
✓ Edit user -> Success
✓ Delete user -> Success
✓ Toggle user status -> Success
✓ Create role -> Success
✓ Edit role -> Success
✓ Delete role -> Success
```

### 3. Manager Access ✅
```
✓ NOT see "User Management" menu
✓ Access /users -> Redirect to dashboard
✓ Access /roles -> Redirect to dashboard
✓ Access /barang -> Success
✓ Create barang -> Success
✓ Edit barang -> Success
✓ Delete barang -> Success
✓ Export barang -> Success
```

### 4. Staff Access ✅
```
✓ NOT see "User Management" menu
✓ Access /barang -> Success
✓ Create barang -> Success
✓ Edit barang -> Success
✓ Delete barang -> Redirect (no permission)
✓ Export barang -> Redirect (no permission)
✓ Access /barang-masuk -> Success
✓ Create barang masuk -> Success
```

### 5. Viewer Access ✅
```
✓ NOT see "User Management" menu
✓ Access /barang -> Success (read-only)
✓ Create barang -> Redirect (no permission)
✓ Edit barang -> Redirect (no permission)
✓ Delete barang -> Redirect (no permission)
✓ Access /stok -> Success (read-only)
✓ Access /laporan -> Success (read-only)
```

---

## 🔍 Permission Testing Matrix

| Action | Admin | Manager | Staff | Viewer |
|--------|-------|---------|-------|--------|
| View Dashboard | ✅ | ✅ | ✅ | ✅ |
| View Barang | ✅ | ✅ | ✅ | ✅ |
| Create Barang | ✅ | ✅ | ✅ | ❌ |
| Edit Barang | ✅ | ✅ | ✅ | ❌ |
| Delete Barang | ✅ | ✅ | ❌ | ❌ |
| Export Barang | ✅ | ✅ | ❌ | ❌ |
| Import Barang | ✅ | ✅ | ❌ | ❌ |
| View Users | ✅ | ❌ | ❌ | ❌ |
| Create Users | ✅ | ❌ | ❌ | ❌ |
| Edit Users | ✅ | ❌ | ❌ | ❌ |
| Delete Users | ✅ | ❌ | ❌ | ❌ |
| View Roles | ✅ | ❌ | ❌ | ❌ |
| Create Roles | ✅ | ❌ | ❌ | ❌ |
| Edit Roles | ✅ | ❌ | ❌ | ❌ |
| Delete Roles | ✅ | ❌ | ❌ | ❌ |

---

## 🚀 Quick Test Commands

### 1. Check Database
```bash
# Check users
php artisan tinker
>>> User::with('role')->get(['id', 'name', 'email', 'role_id', 'is_active', 'last_login_at'])

# Check roles
>>> Role::with('permissions')->get()

# Check permissions
>>> Permission::count()
```

### 2. Clear Cache
```bash
php artisan optimize:clear
```

### 3. Rebuild Frontend
```bash
npm run build
```

### 4. Check Logs
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Web server logs (Laragon)
tail -f C:\laragon\www\warehouse\storage\logs\laravel.log
```

---

## 🐛 Common Issues & Solutions

### Issue: Cannot login
```
✓ Check user is_active = 1
✓ Check password is correct
✓ Clear browser cache
✓ Check storage/logs/laravel.log
```

### Issue: Permission denied
```
✓ Check user role_id
✓ Check role has permission
✓ Run: php artisan optimize:clear
✓ Check middleware on route
```

### Issue: Menu not showing
```
✓ Check $page.props.auth.isAdmin
✓ Clear browser cache
✓ Run: npm run build
✓ Check HandleInertiaRequests.php
```

### Issue: 500 Error
```
✓ Check storage/logs/laravel.log
✓ Check database connection
✓ Run: php artisan optimize:clear
✓ Check .env file
```

---

## 📊 Test Results Template

```
Date: _______________
Tester: _______________

[ ] 1. Login & Authentication
    [ ] Admin login
    [ ] Manager login
    [ ] Staff login
    [ ] Viewer login
    [ ] Inactive user login (should fail)
    [ ] Wrong password (should fail)
    [ ] Rate limiting (should fail after 5 attempts)

[ ] 2. User Management (Admin only)
    [ ] View users list
    [ ] Create new user
    [ ] Edit user
    [ ] Delete user
    [ ] Toggle user status
    [ ] Search users
    [ ] Filter users

[ ] 3. Role Management (Admin only)
    [ ] View roles list
    [ ] Create new role
    [ ] Edit role
    [ ] Delete role
    [ ] Assign permissions
    [ ] View permissions

[ ] 4. Permission System
    [ ] Admin has all permissions
    [ ] Manager has correct permissions
    [ ] Staff has correct permissions
    [ ] Viewer has correct permissions
    [ ] Permission middleware works
    [ ] Menu visibility based on permissions

[ ] 5. Security
    [ ] CSRF protection works
    [ ] Rate limiting works
    [ ] Active status check works
    [ ] Cannot delete own account
    [ ] Cannot access without login

[ ] 6. UI/UX
    [ ] All forms work correctly
    [ ] Validation messages show
    [ ] Success messages show
    [ ] Error messages show
    [ ] Responsive design works
    [ ] Icons display correctly

Notes:
_________________________________
_________________________________
_________________________________
```

---

## 🎯 Performance Checklist

```
[ ] Page load time < 2 seconds
[ ] Database queries optimized
[ ] Cache working correctly
[ ] No N+1 queries
[ ] Frontend assets minified
[ ] Images optimized
[ ] No console errors
[ ] No PHP errors
```

---

## ✅ Production Readiness Checklist

```
[ ] All tests passed
[ ] Documentation complete
[ ] Default users created
[ ] Default roles created
[ ] Permissions seeded
[ ] Frontend built
[ ] Cache cleared
[ ] Logs checked
[ ] Security tested
[ ] Performance tested
[ ] Backup created
[ ] .env configured
[ ] Database migrated
[ ] Seeder run
```

---

## 📞 Emergency Contacts

```
Developer: _______________
Phone: _______________
Email: _______________

Server Admin: _______________
Phone: _______________
Email: _______________
```

---

**Last Updated:** 2024
**Version:** 1.0.0
**Status:** ✅ Ready for Testing
