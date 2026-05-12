# Validasi Transaksi Penyesuaian Stok

## Overview
Dokumentasi ini menjelaskan validasi yang diterapkan pada transaksi penyesuaian stok untuk memastikan data yang diinput akurat dan mencegah kesalahan.

## Validasi Frontend (Vue.js)

### 1. Validasi Form Sebelum Submit
Fungsi `validateForm()` melakukan pengecekan:

- **Gudang**: Harus dipilih
- **Tanggal**: Harus diisi
- **Minimal 1 Barang**: Harus ada minimal 1 item dalam daftar
- **Barang Duplikat**: Tidak boleh ada barang yang sama dalam satu transaksi
- **Barang Dipilih**: Setiap baris harus memilih barang
- **Stok Baru**: 
  - Harus diisi
  - Harus berupa angka
  - Tidak boleh negatif

### 2. Konfirmasi Sebelum Simpan
Sistem akan menampilkan dialog konfirmasi sebelum menyimpan penyesuaian stok untuk memastikan user yakin dengan data yang diinput.

### 3. Indikator Perubahan Signifikan
Sistem menampilkan peringatan visual jika:
- Perubahan stok > 50% dari stok saat ini
- Selisih stok > 100 unit

Indikator ini membantu user mendeteksi potensi kesalahan input.

### 4. Tampilan Stok Saat Ini
Untuk setiap barang yang dipilih, sistem menampilkan:
- Stok saat ini di gudang yang dipilih
- Perbandingan dengan stok baru yang akan diinput

## Validasi Backend (Laravel)

### 1. Request Validation (StoreMutasiRequest)

#### Rules Dasar:
```php
'tanggal'             => ['required', 'date']
'tipe'                => ['required', Rule::in(['in', 'out', 'transfer', 'adjust'])]
'gudang_id'           => ['required', 'exists:gudangs,id']
'items'               => ['required', 'array', 'min:1']
'items.*.barang_id'   => ['required', 'distinct', 'exists:barangs,id']
'items.*.qty'         => ['required', 'numeric', 'min:0'] // untuk adjust
'items.*.keterangan'  => ['nullable', 'string', 'max:500']
```

#### Custom Validation (withValidator):
- Validasi qty adalah angka valid
- Validasi range stok (maksimal 999,999)
- Validasi barang_id tidak kosong
- Validasi minimal ada 1 item yang valid

### 2. Controller Validation (MutasiController)

Validasi tambahan saat proses penyesuaian:

```php
// Stok tidak boleh negatif
if ($qty < 0) {
    throw ValidationException::withMessages([
        "items.$i.qty" => "Stok baru tidak boleh negatif"
    ]);
}

// Stok tidak boleh terlalu besar
if ($qty > 999999) {
    throw ValidationException::withMessages([
        "items.$i.qty" => "Stok baru terlalu besar (maksimal 999,999)"
    ]);
}
```

### 3. Logging Perubahan
Setiap penyesuaian stok akan mencatat:
- Stok sebelum penyesuaian
- Stok setelah penyesuaian
- Keterangan perubahan

Format: `"Penyesuaian: {stok_lama} → {stok_baru}"`

## API Endpoint Baru

### GET /api/v1/stok/{barangId}/{gudangId}
Endpoint untuk mendapatkan stok saat ini dari barang tertentu di gudang tertentu.

**Response:**
```json
{
    "success": true,
    "barang_id": 1,
    "gudang_id": 2,
    "stok": 150,
    "min_stok": 10
}
```

**Cache:** 60 detik

## Pesan Error

### Frontend:
- "Gudang harus dipilih"
- "Tanggal harus diisi"
- "Minimal harus ada 1 barang"
- "Ada barang yang duplikat dalam daftar"
- "Baris X: Barang harus dipilih"
- "Baris X: Stok baru harus diisi"
- "Baris X: Stok baru harus berupa angka"
- "Baris X: Stok baru tidak boleh negatif"

### Backend:
- "Tanggal wajib diisi"
- "Gudang wajib dipilih"
- "Gudang tidak ditemukan"
- "Minimal 1 baris barang harus diisi"
- "Barang wajib dipilih"
- "Barang tidak boleh duplikat"
- "Barang tidak ditemukan"
- "Qty/Stok wajib diisi"
- "Qty/Stok harus berupa angka"
- "Stok tidak boleh negatif"
- "Stok baru terlalu besar (maksimal 999,999)"
- "Keterangan maksimal 500 karakter"

## Best Practices

1. **Selalu Pilih Gudang Terlebih Dahulu**
   - Stok saat ini hanya ditampilkan setelah gudang dipilih
   - Memastikan data stok yang ditampilkan akurat

2. **Periksa Stok Saat Ini**
   - Bandingkan dengan hasil stock opname fisik
   - Perhatikan peringatan perubahan signifikan

3. **Isi Keterangan**
   - Jelaskan alasan penyesuaian
   - Referensikan dokumen stock opname jika ada

4. **Double Check Sebelum Simpan**
   - Sistem akan menampilkan konfirmasi
   - Pastikan semua data sudah benar

## Keamanan

1. **Validasi Ganda**: Frontend dan backend
2. **Type Checking**: Memastikan data numerik valid
3. **Range Validation**: Mencegah nilai ekstrem
4. **Duplicate Prevention**: Mencegah barang duplikat
5. **Audit Trail**: Semua perubahan tercatat dengan detail

## Testing

Untuk menguji validasi:

1. **Test Gudang Kosong**: Coba submit tanpa memilih gudang
2. **Test Barang Kosong**: Coba submit tanpa memilih barang
3. **Test Stok Negatif**: Coba input stok negatif
4. **Test Stok Terlalu Besar**: Coba input stok > 999,999
5. **Test Barang Duplikat**: Coba tambahkan barang yang sama 2x
6. **Test Perubahan Signifikan**: Input stok yang jauh berbeda dari stok saat ini

## Troubleshooting

### Stok Saat Ini Tidak Muncul
- Pastikan gudang sudah dipilih
- Pastikan barang sudah dipilih
- Cek koneksi ke API endpoint

### Validasi Error Tidak Muncul
- Cek console browser untuk error JavaScript
- Pastikan window.toast tersedia
- Cek network tab untuk response error dari server

### Perubahan Tidak Tersimpan
- Cek error message yang muncul
- Pastikan semua field required terisi
- Cek log Laravel untuk detail error
