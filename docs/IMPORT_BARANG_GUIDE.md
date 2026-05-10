# Import & Reset Barang - User Guide

## 📥 **Import Excel**

### Akses:
1. **Via Sidebar:** Barang → Klik tombol "Import Excel"
2. **Via URL:** Navigate ke `/barang/import`

---

## 📋 **Format Excel**

### Kolom yang Diperlukan:

| Kolom | Nama Header | Wajib | Keterangan |
|-------|-------------|-------|------------|
| A | `kode_barang` | ✅ | Kode unik. Re-import = update data existing |
| B | `part_number` | ❌ | Part number (opsional) |
| C | `nama_barang` | ✅ | Nama barang |
| D | `kategori` | ❌ | Nama kategori (auto-create jika belum ada) |
| E | `sub_kategori` | ❌ | Nama sub kategori (auto-create) |
| F | `merk` | ❌ | Nama merk (auto-create) |
| G | `group` | ❌ | Nama group (auto-create) |
| H | `satuan` | ❌ | Satuan (default: 'pcs') |
| I | `harga_beli` | ❌ | Harga beli (default: 0) |
| J | `harga_jual` | ❌ | Harga jual (default: 0) |
| K | `min_stok` | ❌ | Minimum stok (default: 0) |
| L | `deskripsi` | ❌ | Deskripsi barang (opsional) |

### Contoh Excel:

```
| kode_barang | part_number | nama_barang    | kategori    | sub_kategori | merk  | group      | satuan | harga_beli | harga_jual | min_stok | deskripsi |
|-------------|-------------|----------------|-------------|--------------|-------|------------|--------|------------|------------|----------|-----------|
| BRG001      | PN-12345    | Laptop ASUS    | Elektronik  | Komputer     | ASUS  | IT         | unit   | 10000000   | 12500000   | 5        | ROG Series|
| BRG002      | PN-67890    | Mouse Logitech | Elektronik  | Aksesoris    | Logitech | IT      | pcs    | 150000     | 200000     | 10       | Wireless  |
```

---

## 🚀 **Cara Import**

### Step 1: Persiapkan File Excel
1. Buat file Excel dengan format di atas
2. Baris 1 = Header (nama kolom)
3. Baris 2 dst = Data barang
4. Save as `.xlsx`, `.xls`, atau `.csv`

### Step 2: Upload File
1. Klik tombol **"Import Excel"** di halaman Barang
2. Klik **"Choose File"** atau drag & drop
3. Pilih file Excel (max 20MB)
4. Klik **"Mulai Import"**

### Step 3: Tunggu Proses
- ⏱️ **10-100 baris:** ~2-5 detik
- ⏱️ **1000 baris:** ~10-15 detik
- ⏱️ **9000+ baris:** ~30-60 detik
- ⚠️ **JANGAN TUTUP TAB** saat proses import!

### Step 4: Lihat Hasil
Setelah selesai, akan muncul summary:
- ✅ **Berhasil:** Jumlah baris yang ter-import
- ⚠️ **Dilewati:** Baris yang dilewati (duplikat/invalid)
- ❌ **Error:** Detail baris yang bermasalah

---

## 🔄 **Re-Import (Update Data)**

### Cara Kerja:
- Import menggunakan `kode_barang` sebagai **unique key**
- Jika `kode_barang` sudah ada → **UPDATE** data existing
- Jika `kode_barang` belum ada → **INSERT** data baru

### Example:
```
Import 1:
BRG001 | Laptop ASUS | 10000000 | 12500000

Import 2 (update harga):
BRG001 | Laptop ASUS | 11000000 | 13000000
→ Data BRG001 akan di-update dengan harga baru
```

---

## 🎯 **Auto-Create Master Data**

### Fitur Otomatis:
Saat import, sistem akan **otomatis membuat** master data jika belum ada:

1. **Kategori** - Dari kolom `kategori`
2. **Sub Kategori** - Dari kolom `sub_kategori`
3. **Merk** - Dari kolom `merk`
4. **Group** - Dari kolom `group`

### Contoh:
```excel
kategori: "Elektronik"
→ Jika belum ada, sistem buat kategori baru dengan:
  - kode_category: "ELEKTRONIK" (uppercase)
  - nama_category: "Elektronik"
```

### Keuntungan:
- ✅ Tidak perlu input master data manual dulu
- ✅ Langsung import barang + master sekaligus
- ✅ Konsisten dengan data Excel

---

## ⚠️ **Error Handling**

### Jenis Error:

#### 1. **Validation Error**
```
Baris 5: kode_barang wajib diisi
Baris 10: nama_barang wajib diisi
```
**Solusi:** Perbaiki baris yang error di Excel, upload ulang

#### 2. **Format Error**
```
File format tidak valid. Gunakan .xlsx / .xls / .csv
```
**Solusi:** Save file sebagai Excel format yang benar

#### 3. **Size Error**
```
Ukuran file maksimal 20MB
```
**Solusi:** Split file menjadi beberapa bagian, import satu per satu

#### 4. **Duplicate Error**
```
Baris 15: kode_barang 'BRG001' sudah ada (dilewati)
```
**Catatan:** Ini bukan error, hanya info bahwa data sudah ada

---

## 🗑️ **Reset Data**

### Akses:
Di halaman Import, klik tombol **"Reset Data"** (merah)

### Pilihan Reset:

#### 1. **Barang** ⚠️
- Hapus semua data barang
- **Otomatis ikut hapus:**
  - Stok per gudang
  - Riwayat mutasi (in/out/transfer/adjust)

#### 2. **Riwayat Mutasi**
- Hapus semua transaksi (in/out/transfer/adjust)
- **Tidak hapus:** Barang & stok

#### 3. **Master Kategori**
- Hapus semua kategori
- **Warning:** Barang yang pakai kategori ini akan kehilangan referensi

#### 4. **Master Sub Kategori**
- Hapus semua sub kategori

#### 5. **Master Merk**
- Hapus semua merk

#### 6. **Master Group**
- Hapus semua group

#### 7. **Master Gudang** ⚠️
- Hapus semua gudang
- **Otomatis ikut hapus:** Stok per gudang

### Cara Reset:

1. Klik **"Reset Data"**
2. **Pilih data** yang mau dihapus (bisa multiple)
3. Ketik **"HAPUS"** untuk konfirmasi
4. Klik **"Reset"**

### Konfirmasi:
```
Ketik: HAPUS
→ Harus UPPERCASE, tidak boleh "hapus" atau "Hapus"
```

### Hasil:
```
Dihapus: 1500 barang, 3000 mutasi, 50 kategori, 30 merk
```

---

## 💡 **Tips & Best Practices**

### 1. **Backup Dulu!**
Sebelum import/reset, **backup database** dulu:
```bash
php artisan backup:run
```

### 2. **Test dengan Sample Kecil**
- Import 10-20 baris dulu untuk test format
- Kalau sukses, baru import full data

### 3. **Gunakan Template**
- Download template Excel dari sistem (future feature)
- Atau copy format dari contoh di atas

### 4. **Perhatikan Encoding**
- Gunakan UTF-8 encoding
- Hindari special characters yang aneh

### 5. **Split File Besar**
Jika file > 5000 baris:
- Split jadi beberapa file (1000-2000 baris per file)
- Import satu per satu
- Lebih stabil dan mudah track error

### 6. **Cek Hasil Import**
Setelah import:
- Cek jumlah barang di halaman Barang
- Cek master data (kategori, merk, dll)
- Cek apakah harga sudah benar

---

## 🔧 **Technical Details**

### Import Process:

```
1. Upload file → Validation (format, size)
2. Read Excel → Parse rows
3. Validate each row → Check required fields
4. Auto-create masters → Kategori, Merk, Group, Sub Kategori
5. Insert/Update barang → Based on kode_barang
6. Return result → Success/Partial/Error
```

### Performance:

| Rows | Time | Memory |
|------|------|--------|
| 100 | ~3s | ~10MB |
| 1000 | ~15s | ~50MB |
| 5000 | ~45s | ~150MB |
| 10000 | ~90s | ~300MB |

### Database Impact:

**Insert:**
- `barangs` table
- `categories` table (auto-create)
- `sub_categories` table (auto-create)
- `merks` table (auto-create)
- `groups` table (auto-create)

**Update:**
- Existing barang (by kode_barang)

**Cache:**
- Clear `barang.masters` cache
- Clear `mutasi.masters` cache

---

## 🐛 **Troubleshooting**

### Problem 1: Import Stuck
**Symptom:** Progress bar tidak bergerak
**Solution:**
- Jangan refresh! Tunggu sampai timeout (max 5 menit)
- Cek file size (max 20MB)
- Split file jadi lebih kecil

### Problem 2: Banyak Error
**Symptom:** Banyak baris dilewati
**Solution:**
- Download error log
- Perbaiki baris yang error
- Upload ulang (tidak akan duplikat)

### Problem 3: Master Tidak Terbuat
**Symptom:** Kategori/Merk tidak muncul
**Solution:**
- Cek kolom header Excel (harus exact match)
- Cek apakah ada typo di nama kolom
- Pastikan tidak ada spasi extra

### Problem 4: Reset Tidak Bisa
**Symptom:** Tombol Reset disabled
**Solution:**
- Pastikan sudah pilih minimal 1 data
- Pastikan ketik "HAPUS" (uppercase)
- Cek apakah ada barang dengan stok (tidak bisa dihapus)

---

## 📊 **Use Cases**

### Use Case 1: Initial Setup
```
Scenario: Setup sistem baru dengan 1000+ barang
Steps:
1. Persiapkan Excel dengan semua data barang
2. Import sekali jalan
3. Sistem auto-create semua master data
4. Langsung bisa transaksi
```

### Use Case 2: Update Harga Massal
```
Scenario: Update harga 500 barang sekaligus
Steps:
1. Export data barang existing (future feature)
2. Update kolom harga_beli & harga_jual di Excel
3. Re-import (akan update by kode_barang)
4. Harga ter-update otomatis
```

### Use Case 3: Migrasi dari Sistem Lama
```
Scenario: Pindah dari sistem lama ke sistem baru
Steps:
1. Export data dari sistem lama ke Excel
2. Sesuaikan format kolom dengan template
3. Import ke sistem baru
4. Verifikasi data
```

### Use Case 4: Reset untuk Testing
```
Scenario: Testing fitur dengan data dummy
Steps:
1. Import data dummy
2. Test fitur transaksi
3. Reset semua data (Barang + Mutasi)
4. Import data production
```

---

## 🔒 **Security & Validation**

### File Validation:
- ✅ Format: `.xlsx`, `.xls`, `.csv` only
- ✅ Size: Max 20MB
- ✅ MIME type check
- ✅ Virus scan (future)

### Data Validation:
- ✅ Required fields check
- ✅ Data type validation
- ✅ Unique constraint (kode_barang)
- ✅ Foreign key validation
- ✅ SQL injection prevention

### Rate Limiting:
- Import: **10 requests per minute**
- Reset: **10 requests per minute**

### Access Control:
- Requires authentication
- Admin only (future: role-based)

---

## 📝 **Future Enhancements**

### Planned Features:
1. **Download Template** - Template Excel kosong
2. **Export to Excel** - Export data barang existing
3. **Import History** - Log semua import activity
4. **Scheduled Import** - Auto-import dari FTP/cloud
5. **Validation Preview** - Preview sebelum import
6. **Rollback** - Undo import terakhir
7. **Bulk Update** - Update field tertentu saja
8. **Import Stok** - Import stok per gudang sekaligus

---

## 📖 **FAQ**

### Q: Apakah import akan duplikat data?
**A:** Tidak. Import menggunakan `kode_barang` sebagai unique key. Jika sudah ada, akan di-update.

### Q: Bagaimana jika ada error di tengah import?
**A:** Baris yang error akan dilewati. Baris yang valid tetap ter-import. Anda bisa perbaiki yang error dan upload ulang.

### Q: Apakah bisa import stok sekaligus?
**A:** Belum. Saat ini import hanya untuk master barang. Stok harus di-input via transaksi Barang Masuk atau Import Stok.

### Q: Berapa maksimal baris yang bisa di-import?
**A:** Tidak ada limit baris, tapi file max 20MB. Biasanya ~10,000-15,000 baris.

### Q: Apakah reset bisa di-undo?
**A:** Tidak. Reset adalah permanent delete. Pastikan backup dulu sebelum reset.

### Q: Bagaimana cara backup data?
**A:** Export database via phpMyAdmin atau gunakan command:
```bash
php artisan backup:run
```

---

**Last Updated:** May 10, 2026
**Version:** 1.0.0
**Status:** ✅ Production Ready
