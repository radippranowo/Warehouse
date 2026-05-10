# Standardisasi Input Form - Kotak Default

## 📋 Perubahan
Menghapus class `btn-rounded` dari semua input search dan filter di seluruh aplikasi agar menggunakan bentuk **kotak standar** (bukan bulat) untuk konsistensi UI yang lebih baik.

## 🎯 Alasan

### Masalah Sebelumnya:
1. **Inkonsistensi UI**
   - Beberapa input bulat (`btn-rounded`)
   - Beberapa input kotak (standar)
   - Membingungkan user

2. **Tidak Sesuai Standar**
   - Form input standar Bootstrap = kotak
   - Input bulat lebih cocok untuk button, bukan input field
   - Best practice: input field = kotak

3. **Visual Tidak Seragam**
   - Di halaman index: bulat
   - Di form transaksi: kotak
   - Di modal: kotak

## ✅ Solusi

### Standardisasi: Semua Input = Kotak
```html
<!-- ❌ Sebelum (Bulat) -->
<input class="form-control btn-rounded" />

<!-- ✅ Sesudah (Kotak) -->
<input class="form-control" />
```

## 🔧 File yang Diubah

### 1. **Barang/Index.vue** ✅
```html
<!-- Search Input -->
<input 
    id="search_barang" 
    class="form-control"  <!-- Dulu: form-control btn-rounded -->
    placeholder="Cari kode / nama / part number..."
/>
```

### 2. **Gudang/Index.vue** ✅
```html
<!-- Search Input -->
<input 
    id="search_gudang" 
    class="form-control"  <!-- Dulu: form-control btn-rounded -->
/>
```

### 3. **Supplier/Index.vue** ✅
```html
<!-- Search Input -->
<input 
    id="search_supplier" 
    class="form-control"  <!-- Dulu: form-control btn-rounded -->
/>
```

### 4. **Stok/Index.vue** ✅
```html
<!-- Search Input -->
<input 
    id="search_stok" 
    class="form-control"  <!-- Dulu: form-control btn-rounded -->
/>
```

### 5. **Riwayat/Transfer.vue** ✅
```html
<!-- Search Input -->
<input 
    id="search_transfer" 
    class="form-control"  <!-- Dulu: form-control btn-rounded -->
/>

<!-- Date Filter -->
<input v-model="dateFrom" type="date" class="form-control">  <!-- Dulu: btn-rounded -->
<input v-model="dateTo" type="date" class="form-control">    <!-- Dulu: btn-rounded -->
```

### 6. **Components/MasterCrud.vue** ✅
```html
<!-- Search Input (digunakan oleh Category, Merk, Group, SubCategory) -->
<input 
    v-model="search" 
    type="text" 
    class="form-control"  <!-- Dulu: form-control btn-rounded -->
    placeholder="Cari..."
/>
```

**Halaman yang menggunakan MasterCrud:**
- ✅ Category/Index.vue
- ✅ Merk/Index.vue
- ✅ Group/Index.vue
- ✅ SubCategory/Index.vue

### 7. **Riwayat Lainnya** (Sudah Benar)
- ✅ Pemasukan.vue - Sudah kotak
- ✅ Pengeluaran.vue - Sudah kotak
- ✅ Penyesuaian.vue - Sudah kotak
- ✅ Semua.vue - Sudah kotak

### 7. **Transaksi Forms** (Sudah Benar)
- ✅ BarangMasuk.vue - SearchInput (kotak)
- ✅ BarangKeluar.vue - SearchInput (kotak)
- ✅ Transfer.vue - Form inputs (kotak)
- ✅ Penyesuaian.vue - Form inputs (kotak)

## 📊 Perbandingan Visual

### Sebelum (Inkonsisten):
```
┌─────────────────────────────────┐
│ Halaman Index                   │
├─────────────────────────────────┤
│ Search: ╭─────────────────╮     │  ← Bulat (btn-rounded)
│         ╰─────────────────╯     │
│                                 │
│ Filter: ┌─────────────────┐     │  ← Kotak (form-control)
│         └─────────────────┘     │
└─────────────────────────────────┘
```

### Sesudah (Konsisten):
```
┌─────────────────────────────────┐
│ Halaman Index                   │
├─────────────────────────────────┤
│ Search: ┌─────────────────┐     │  ← Kotak (form-control)
│         └─────────────────┘     │
│                                 │
│ Filter: ┌─────────────────┐     │  ← Kotak (form-control)
│         └─────────────────┘     │
└─────────────────────────────────┘
```

## ✨ Manfaat

### 1. **Konsistensi UI**
- Semua input field berbentuk kotak
- Tidak ada perbedaan visual yang membingungkan
- User experience lebih baik

### 2. **Sesuai Standar Bootstrap**
- Form input standar = kotak (border-radius: 0.25rem)
- Button = bisa bulat (btn-rounded)
- Mengikuti best practice

### 3. **Visual Lebih Professional**
- Clean dan modern
- Tidak over-styled
- Fokus pada fungsi, bukan dekorasi

### 4. **Maintenance Lebih Mudah**
- Satu pattern untuk semua input
- Tidak perlu ingat kapan pakai bulat/kotak
- Code lebih clean

## 🎨 Design System

### Input Field Guidelines:
| Element | Class | Border Radius | Use Case |
|---------|-------|---------------|----------|
| **Text Input** | `form-control` | 0.25rem (kotak) | Search, filter, form field |
| **Select** | `form-select` | 0.25rem (kotak) | Dropdown selection |
| **Date Input** | `form-control` | 0.25rem (kotak) | Date picker |
| **Button** | `btn btn-rounded` | 30px (bulat) | Action button |
| **Dropdown Button** | `btn btn-rounded` | 30px (bulat) | Dropdown trigger |

### Kapan Pakai `btn-rounded`:
✅ Button action (Tambah, Hapus, Edit)
✅ Dropdown button (perPage, filter)
✅ Badge/pill

### Kapan TIDAK Pakai `btn-rounded`:
❌ Input text
❌ Input date
❌ Select dropdown
❌ Textarea

## 📝 CSS Reference

### Bootstrap Default:
```css
.form-control {
    border-radius: 0.25rem; /* Kotak standar */
}

.btn-rounded {
    border-radius: 30px; /* Bulat penuh */
}
```

### SearchInput Component:
```css
/* Override di SearchInput.vue */
.search-input-wrapper input.form-control {
    border-radius: 0.25rem !important; /* Kotak */
}
```

## 🚀 Impact

### User Experience:
- ✨ **Konsisten** di seluruh aplikasi
- 🎯 **Predictable** - semua input sama
- 👁️ **Clean** - tidak over-styled
- 📱 **Professional** - sesuai standar

### Developer Experience:
- 🧹 **Clean code** - satu pattern
- 📖 **Easy to maintain** - tidak perlu ingat exception
- 🔄 **Reusable** - copy-paste pattern yang sama
- ✅ **Best practice** - ikuti standar Bootstrap

## 📋 Checklist

- ✅ Barang/Index.vue - Search input
- ✅ Gudang/Index.vue - Search input
- ✅ Supplier/Index.vue - Search input
- ✅ Stok/Index.vue - Search input
- ✅ Riwayat/Transfer.vue - Search & date inputs
- ✅ Riwayat/Pemasukan.vue - Already correct
- ✅ Riwayat/Pengeluaran.vue - Already correct
- ✅ Riwayat/Penyesuaian.vue - Already correct
- ✅ Riwayat/Semua.vue - Already correct
- ✅ Components/MasterCrud.vue - Search input (Category, Merk, Group, SubCategory)
- ✅ Transaksi forms - Already correct (SearchInput)
- ✅ SearchInput component - Already correct (CSS override)

## 🎉 Hasil

Sekarang **semua input field** di aplikasi menggunakan bentuk **kotak standar** yang konsisten, professional, dan sesuai dengan best practice Bootstrap!

### 🔒 CSS Override Ditambahkan:

Untuk memastikan konsistensi di semua halaman, CSS override ditambahkan:

```css
/* Pastikan semua input berbentuk kotak (tidak bulat) */
.form-control,
.form-select {
    border-radius: 0.25rem !important;
}
```

**File yang mendapat CSS override:**
1. ✅ Barang/Index.vue
2. ✅ Gudang/Index.vue
3. ✅ Supplier/Index.vue
4. ✅ Stok/Index.vue
5. ✅ Components/MasterCrud.vue (Category, Merk, Group, SubCategory)
6. ✅ Riwayat/Semua.vue
7. ✅ Riwayat/Pemasukan.vue
8. ✅ Riwayat/Pengeluaran.vue
9. ✅ Riwayat/Penyesuaian.vue
10. ✅ Riwayat/Transfer.vue

---
**Tanggal:** 10 Mei 2026  
**Status:** ✅ Selesai  
**Pattern:** Kotak (form-control) untuk semua input field dengan CSS override untuk konsistensi maksimal
