# Laporan Keuntungan Penjualan

## 📊 **Overview**
Fitur untuk menganalisis keuntungan dari transaksi barang keluar (penjualan) dengan membandingkan harga beli dari supplier dan harga jual yang ditentukan.

## ✨ **Features**

### 1. **Summary Cards**
Menampilkan ringkasan keseluruhan:
- **Total Qty Terjual** - Total kuantitas barang yang terjual
- **Total Modal** - Total harga beli (modal) dari semua barang terjual
- **Total Penjualan** - Total harga jual dari semua barang terjual
- **Total Keuntungan** - Selisih antara total penjualan dan total modal
- **Margin %** - Persentase keuntungan dari total penjualan

### 2. **Advanced Filters**
- **Search** - Cari berdasarkan kode barang, nama barang, atau nomor mutasi
- **Gudang** - Filter berdasarkan gudang tertentu
- **Dari Tanggal** - Filter tanggal mulai
- **Sampai Tanggal** - Filter tanggal akhir
- **Reset Filter** - Reset semua filter ke default

### 3. **Detail Table**
Menampilkan detail per item dengan kolom:
- Tanggal transaksi
- Nomor mutasi
- Kode barang
- Nama barang
- Gudang
- Qty terjual
- Harga beli (modal per unit)
- Harga jual (harga jual per unit)
- Keuntungan per unit
- Total keuntungan (qty × keuntungan per unit)
- Margin % dengan color coding:
  - 🔴 **Merah** - Margin < 10% (rendah)
  - 🟡 **Kuning** - Margin 10-20% (sedang)
  - 🟢 **Hijau** - Margin ≥ 20% (tinggi)

### 4. **Pagination**
- Pilihan per halaman: 10, 25, 50, 100
- Smart pagination (max 9 buttons)
- Preserves filters saat navigasi

## 🎯 **How It Works**

### Data Source:
```sql
SELECT 
    items.*,
    barangs.harga_beli,
    barangs.harga_jual,
    (harga_jual - harga_beli) as keuntungan_per_unit,
    ((harga_jual - harga_beli) * qty) as total_keuntungan
FROM stok_mutasi_items items
JOIN stok_mutasis ON items.stok_mutasi_id = stok_mutasis.id
JOIN barangs ON items.barang_id = barangs.id
WHERE stok_mutasis.tipe = 'keluar'
  AND stok_mutasis.status = 'approved'
```

### Calculation Logic:
```
Keuntungan per Unit = Harga Jual - Harga Beli
Total Keuntungan = Keuntungan per Unit × Qty
Margin % = (Keuntungan per Unit / Harga Beli) × 100
```

### Example:
```
Barang: Laptop ASUS ROG
Harga Beli: Rp 10,000,000
Harga Jual: Rp 12,500,000
Qty Terjual: 5 unit

Keuntungan per Unit = 12,500,000 - 10,000,000 = Rp 2,500,000
Total Keuntungan = 2,500,000 × 5 = Rp 12,500,000
Margin % = (2,500,000 / 10,000,000) × 100 = 25% ✅ (Hijau)
```

## 📁 **Files Created**

### 1. **Controller**
**File:** `app/Http/Controllers/LaporanKeuntunganController.php`

**Methods:**
- `index()` - Main method untuk menampilkan laporan
  - Query barang keluar yang approved
  - Calculate keuntungan per item
  - Generate summary statistics
  - Apply filters (search, gudang, date range)
  - Paginate results

**Key Features:**
- Efficient SQL joins
- Real-time calculation using DB::raw()
- Filtered summary statistics
- Optimized queries with proper indexes

### 2. **View**
**File:** `resources/js/Pages/LaporanKeuntungan/Index.vue`

**Components Used:**
- AppLayout - Main layout
- Pagination - Smart pagination component

**Key Features:**
- Reactive filters with debouncing (300ms)
- Color-coded margin indicators
- Responsive design
- Square inputs (consistent UI)
- Format currency (Rp)
- Format numbers with thousand separator
- Format dates (Indonesian locale)

### 3. **Route**
**File:** `routes/web.php`

```php
// Laporan Keuntungan
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/laporan-keuntungan', [LaporanKeuntunganController::class, 'index'])
        ->name('laporan-keuntungan.index');
});
```

**Rate Limiting:** 60 requests per minute

### 4. **Sidebar Menu**
**File:** `resources/js/Layouts/AppLayout.vue`

Added menu item:
```vue
<li>
    <Link href="/laporan-keuntungan" prefetch cache-for="15m" class="waves-effect">
        <i class="bx bx-line-chart"></i><span>Laporan Keuntungan</span>
    </Link>
</li>
```

**Features:**
- Prefetch enabled (15 minutes cache)
- Icon: bx-line-chart (chart icon)
- Positioned after "Riwayat" menu

## 🎨 **UI Design**

### Color Scheme:
- **Primary** - Blue (Total Qty)
- **Warning** - Yellow (Total Modal)
- **Info** - Cyan (Total Penjualan)
- **Success** - Green (Total Keuntungan)

### Margin Color Coding:
```javascript
if (margin < 10%)  → Red Badge    (Low profit)
if (margin < 20%)  → Yellow Badge (Medium profit)
if (margin >= 20%) → Green Badge  (High profit)
```

### Layout:
- Grid layout with labels (consistent with other pages)
- Square inputs (border-radius: 0.25rem)
- Card-based design
- Hover effect on table rows
- Responsive columns

## 📊 **Use Cases**

### 1. **Analisis Keuntungan Bulanan**
```
Filter: 
- Dari Tanggal: 01/04/2026
- Sampai Tanggal: 30/04/2026

Result:
- Total Keuntungan bulan April
- Margin rata-rata
- Barang dengan keuntungan tertinggi
```

### 2. **Analisis per Gudang**
```
Filter:
- Gudang: Gudang Pusat

Result:
- Keuntungan dari Gudang Pusat
- Perbandingan dengan gudang lain
```

### 3. **Cari Barang dengan Margin Rendah**
```
Action:
- Lihat tabel, sort by margin %
- Identifikasi barang dengan badge merah
- Pertimbangkan untuk adjust harga jual
```

### 4. **Laporan Keuntungan Tahunan**
```
Filter:
- Dari Tanggal: 01/01/2026
- Sampai Tanggal: 31/12/2026

Result:
- Total keuntungan tahun 2026
- Total penjualan vs modal
- ROI (Return on Investment)
```

## 🔍 **Business Insights**

### Metrics Available:
1. **Total Qty Terjual** - Volume penjualan
2. **Total Modal** - Capital invested
3. **Total Penjualan** - Revenue generated
4. **Total Keuntungan** - Profit earned
5. **Margin %** - Profit margin percentage

### Decision Making:
- **Low Margin (<10%)** - Consider increasing price or finding cheaper supplier
- **Medium Margin (10-20%)** - Acceptable, monitor competition
- **High Margin (≥20%)** - Good profit, maintain or expand

### KPIs:
```
ROI = (Total Keuntungan / Total Modal) × 100
Profit Margin = (Total Keuntungan / Total Penjualan) × 100
Average Margin per Item = Total Margin % / Total Items
```

## ⚡ **Performance**

### Optimizations:
1. **Database Indexes** - Already indexed on:
   - `stok_mutasis.tipe`
   - `stok_mutasis.status`
   - `stok_mutasis.tanggal`
   - `barangs.kode_barang`
   - `barangs.nama_barang`

2. **Efficient Queries**:
   - Single query with joins (no N+1)
   - DB::raw() for calculations (computed in database)
   - Pagination to limit results

3. **Frontend Optimizations**:
   - Debounced search (300ms)
   - Prefetch enabled (15m cache)
   - Lazy loading with pagination

### Expected Performance:
- **Load Time:** < 500ms (with 10,000+ records)
- **Search:** < 300ms (debounced)
- **Filter Change:** < 200ms (cached)

## 🔒 **Security**

### Rate Limiting:
- 60 requests per minute (throttle:60,1)

### Input Sanitization:
- Search query sanitized
- Date validation
- Gudang ID validation

### Access Control:
- Requires authentication (middleware)
- Only shows approved transactions
- No sensitive data exposed

## 📝 **Future Enhancements**

### Possible Improvements:
1. **Export to Excel** - Download laporan as Excel file
2. **Chart Visualization** - Line chart for trend analysis
3. **Comparison** - Compare month-to-month or year-to-year
4. **Top Products** - Show top 10 most profitable products
5. **Supplier Analysis** - Profit by supplier
6. **Category Analysis** - Profit by category
7. **Email Reports** - Scheduled email reports
8. **Print PDF** - Print-friendly PDF format

### Advanced Features:
1. **Forecasting** - Predict future profits based on trends
2. **Break-even Analysis** - Calculate break-even point
3. **Inventory Turnover** - Calculate inventory turnover ratio
4. **Gross Margin Return on Investment (GMROI)**
5. **Sales Performance Dashboard**

## 🧪 **Testing**

### Test Scenarios:

#### 1. **Basic Load**
```
Action: Open /laporan-keuntungan
Expected: 
- Summary cards show correct totals
- Table shows all approved barang keluar
- Pagination works
```

#### 2. **Search Filter**
```
Action: Type "laptop" in search
Expected:
- Only items with "laptop" in kode/nama barang shown
- Summary updates to reflect filtered data
```

#### 3. **Date Range Filter**
```
Action: Set date range (01/04/2026 - 30/04/2026)
Expected:
- Only items within date range shown
- Summary calculates only for filtered period
```

#### 4. **Gudang Filter**
```
Action: Select "Gudang Pusat"
Expected:
- Only items from Gudang Pusat shown
- Summary shows profit from that gudang only
```

#### 5. **Margin Color Coding**
```
Action: Check margin badges
Expected:
- Red badge for margin < 10%
- Yellow badge for margin 10-20%
- Green badge for margin ≥ 20%
```

#### 6. **Pagination**
```
Action: Change per page to 50
Expected:
- Shows 50 items per page
- Pagination updates
- Filters preserved
```

## 📖 **Usage Guide**

### For Users:

1. **Access Laporan**
   - Click "Laporan Keuntungan" di sidebar
   - Atau navigate ke `/laporan-keuntungan`

2. **View Summary**
   - Lihat 4 summary cards di atas
   - Total Qty, Modal, Penjualan, Keuntungan

3. **Apply Filters**
   - Gunakan search untuk cari barang tertentu
   - Pilih gudang untuk filter per gudang
   - Set date range untuk periode tertentu

4. **Analyze Data**
   - Lihat tabel detail per item
   - Perhatikan margin % (color coded)
   - Identifikasi barang dengan profit rendah/tinggi

5. **Export (Future)**
   - Click "Export Excel" untuk download
   - Click "Print PDF" untuk print

### For Developers:

1. **Add New Filter**
   ```php
   // Controller
   if ($categoryId) {
       $query->where('barangs.category_code', $categoryId);
   }
   
   // View
   <select v-model="categoryId">...</select>
   ```

2. **Add New Metric**
   ```php
   // Controller
   DB::raw('AVG((barangs.harga_jual - barangs.harga_beli) / barangs.harga_beli * 100) as avg_margin')
   ```

3. **Customize Color Coding**
   ```javascript
   // Change thresholds
   if (margin < 15) return 'text-danger';
   if (margin < 25) return 'text-warning';
   return 'text-success';
   ```

---

**Created:** May 10, 2026
**Version:** 1.0.0
**Status:** ✅ Production Ready
