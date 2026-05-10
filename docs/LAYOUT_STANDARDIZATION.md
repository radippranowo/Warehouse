# Standardisasi Layout Filter - Grid Pattern

## 📋 Perubahan
Menerapkan **grid layout dengan label** di atas setiap input filter ke semua halaman data master untuk konsistensi UI yang lebih baik.

## 🎯 Pattern yang Diterapkan

### Layout Grid dengan Label:
```html
<div class="row g-2 mb-3">
    <div class="col-lg-3 col-md-4">
        <label class="form-label mb-1 small fw-medium">Pencarian</label>
        <div class="search-box">
            <div class="position-relative">
                <input class="form-control" style="padding-left: 36px; height: 38px;">
                <i class="bx bx-search-alt search-icon" style="left: 12px; font-size: 18px;"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-3">
        <label class="form-label mb-1 small fw-medium">Per Halaman</label>
        <select class="form-select" style="height: 38px;">
            <option>10</option>
            <option>25</option>
            <option>50</option>
            <option>100</option>
        </select>
    </div>
    <div class="col-lg-auto ms-auto">
        <label class="form-label mb-1 small fw-medium d-block">&nbsp;</label>
        <small class="text-muted">Total: <strong>1,234</strong></small>
    </div>
</div>
```

## 🔧 File yang Diubah

### 1. **Components/MasterCrud.vue** ✅
**Halaman yang terpengaruh:**
- Category/Index.vue
- Merk/Index.vue
- Group/Index.vue
- SubCategory/Index.vue

**Perubahan:**
- Layout horizontal → Grid layout dengan label
- Dropdown button → Select dropdown
- `table-check` → `table-hover`
- Icon: `left: 13px` → `left: 12px; font-size: 18px`
- Padding: `40px` → `36px`
- Height: auto → `38px`

### 2. **Gudang/Index.vue** ✅
**Perubahan:**
- Layout horizontal → Grid layout dengan label
- Dropdown button → Select dropdown
- `table align-middle table-nowrap` → `table align-middle table-nowrap table-hover`
- Search icon: `left: 13px` → `left: 12px; font-size: 18px`
- Input height: auto → `38px`

### 3. **Supplier/Index.vue** ✅
**Perubahan:**
- Layout horizontal → Grid layout dengan label
- Dropdown button → Select dropdown
- `table align-middle table-nowrap` → `table align-middle table-nowrap table-hover`
- Search icon: `left: 13px` → `left: 12px; font-size: 18px`
- Input height: auto → `38px`

### 4. **Stok/Index.vue** ✅
**Perubahan:**
- Layout horizontal → Grid layout dengan label
- Input tanpa label → Input dengan label
- `table align-middle table-nowrap` → `table align-middle table-nowrap table-hover`
- Tambah search icon dengan positioning
- Input height: auto → `38px`
- Checkbox "Hanya stok ≤ min" pindah ke kanan dengan label kosong

### 5. **Barang/Index.vue** ✅
**Status:** Tetap menggunakan layout horizontal (desain awal)
- Tidak diubah karena user minta kembali ke desain awal

## 📊 Perbandingan

### Sebelum (Layout Horizontal):
```
[Search Input_______] [25 ▼] Total: 1,234
```

### Sesudah (Grid Layout dengan Label):
```
Pencarian           Per Halaman         Total: 1,234
[Search Input____]  [Select ▼]
```

## ✨ Manfaat

### 1. **Konsistensi UI**
- Semua halaman data master menggunakan pattern yang sama
- Label di atas setiap input untuk clarity
- Spacing konsisten dengan `g-2` dan `mb-3`

### 2. **Responsive Design**
- `col-lg-3 col-md-4` untuk search (lebih lebar di mobile)
- `col-lg-2 col-md-3` untuk select
- `col-lg-auto ms-auto` untuk total (align right)

### 3. **Better UX**
- Label jelas untuk setiap input
- Height konsisten (38px) untuk semua input
- Icon size konsisten (18px)
- Hover effect pada table rows

### 4. **Modern Look**
- Grid layout lebih modern
- Select dropdown lebih clean dari button dropdown
- Table hover effect untuk interaktivitas

## 🎨 Design System

### Input Specifications:
| Element | Height | Padding Left | Icon Position | Icon Size |
|---------|--------|--------------|---------------|-----------|
| **Search Input** | 38px | 36px | left: 12px | 18px |
| **Select** | 38px | - | - | - |
| **Label** | auto | - | - | - |

### Grid Columns:
| Element | Desktop (lg) | Tablet (md) | Mobile (sm) |
|---------|--------------|-------------|-------------|
| **Search** | col-lg-3 | col-md-4 | col-12 |
| **Select** | col-lg-2 | col-md-3 | col-12 |
| **Total** | col-lg-auto | col-md-auto | col-12 |

### Table Classes:
```css
.table.align-middle.table-nowrap.table-hover
```
- `align-middle`: Vertical center alignment
- `table-nowrap`: No text wrapping
- `table-hover`: Hover effect on rows

## 📝 Pattern Consistency

### Halaman dengan Grid Layout:
1. ✅ **Riwayat Semua** - Grid layout
2. ✅ **Riwayat Barang Masuk** - Grid layout
3. ✅ **Riwayat Barang Keluar** - Grid layout
4. ✅ **Riwayat Penyesuaian** - Grid layout
5. ✅ **Riwayat Transfer** - Grid layout
6. ✅ **Category** - Grid layout (via MasterCrud)
7. ✅ **Merk** - Grid layout (via MasterCrud)
8. ✅ **Group** - Grid layout (via MasterCrud)
9. ✅ **SubCategory** - Grid layout (via MasterCrud)
10. ✅ **Gudang** - Grid layout
11. ✅ **Supplier** - Grid layout
12. ✅ **Stok** - Grid layout

### Halaman dengan Layout Horizontal:
1. ✅ **Barang** - Horizontal layout (desain awal)

## 🎯 Hasil Akhir

```
┌─────────────────────────────────────────────────────┐
│ DATA MASTER (12 HALAMAN) │
├─────────────────────────────────────────────────────┤
│ │
│ Pencarian Per Halaman Total: 1,234 │
│ ┌──────────────┐ ┌────────┐ │
│ │ Search Input │ │ Select │ │
│ └──────────────┘ └────────┘ │
│ │
│ ┌─────────────────────────────────────────────────┐ │
│ │ Table with Hover Effect │ │
│ └─────────────────────────────────────────────────┘ │
│ │
└─────────────────────────────────────────────────────┘
```

### Konsistensi:
- ✅ Label di atas setiap input
- ✅ Height 38px untuk semua input
- ✅ Icon size 18px
- ✅ Grid spacing g-2
- ✅ Table hover effect
- ✅ Select dropdown (bukan button)
- ✅ Responsive columns

---
**Tanggal:** 10 Mei 2026  
**Status:** ✅ Selesai  
**Pattern:** Grid Layout dengan Label untuk semua Data Master
