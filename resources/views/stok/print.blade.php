<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Stok {{ $gudang?->nama_gudang ?? 'Semua Gudang' }}</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #000; margin: 0; padding: 18px; }
    .doc { max-width: 100%; }
    h1, h2, h3 { margin: 0; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 12px; }
    .header .company h2 { font-size: 16px; }
    .header .company small { color: #555; }
    .doc-title { text-align: right; }
    .doc-title h3 { font-size: 14px; letter-spacing: 1px; }
    .doc-title .gudang { font-weight: bold; margin-top: 4px; }

    .info { display: flex; gap: 16px; margin-bottom: 10px; flex-wrap: wrap; }
    .info .box { flex: 1; min-width: 130px; border: 1px solid #ccc; padding: 6px 8px; }
    .info .label { color: #555; font-size: 10px; }
    .info .value { font-weight: bold; font-size: 13px; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table th, table td { border: 1px solid #444; padding: 4px 6px; vertical-align: top; }
    table th { background: #eee; text-align: left; font-size: 10px; }
    .text-end { text-align: right; }
    .text-center { text-align: center; }
    tr.row-zero td { background: #fff1f1; }
    tr.row-low td  { background: #fffbe6; }

    .signs { display: flex; justify-content: flex-end; margin-top: 30px; }
    .signs .box { width: 220px; text-align: center; }
    .signs .role { margin-bottom: 50px; font-weight: bold; }
    .signs .name { border-top: 1px solid #000; padding-top: 4px; }

    .meta { color: #555; font-size: 9px; text-align: right; margin-top: 12px; }
    .filter-line { margin-bottom: 8px; font-size: 10px; color: #444; }

    .no-print { margin-bottom: 12px; }
    .no-print button { padding: 6px 14px; cursor: pointer; }

    @media print {
        .no-print { display: none !important; }
        body { padding: 0; }
        thead { display: table-header-group; }
    }
    @page { size: A4 landscape; margin: 10mm; }
</style>
</head>
<body>
<div class="doc">
    <div class="no-print">
        <button onclick="window.print()">Cetak / Simpan PDF</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <div class="header">
        <div class="company">
            <h2>{{ config('app.name', 'LOGISTIK') }}</h2>
            <small>Sistem Manajemen Stok &amp; Mutasi Gudang</small>
        </div>
        <div class="doc-title">
            <h3>LAPORAN STOK PER GUDANG</h3>
            <div class="gudang">{{ $gudang?->nama_gudang ?? 'Semua Gudang' }} @if($gudang) ({{ $gudang->kode_gudang }}) @endif</div>
            <div>Per: {{ now()->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    @if($gudang?->alamat)
        <div class="filter-line"><strong>Alamat:</strong> {{ $gudang->alamat }}</div>
    @endif

    <div class="info">
        <div class="box">
            <div class="label">Total SKU</div>
            <div class="value">{{ number_format($summary['total_sku'], 0, ',', '.') }}</div>
        </div>
        <div class="box">
            <div class="label">Stok Kosong</div>
            <div class="value" style="color:#c0392b;">{{ number_format($summary['zero_count'], 0, ',', '.') }}</div>
        </div>
        <div class="box">
            <div class="label">Di Bawah Min</div>
            <div class="value" style="color:#b8860b;">{{ number_format($summary['low_count'], 0, ',', '.') }}</div>
        </div>
        <div class="box">
            <div class="label">Total Nilai (harga jual)</div>
            <div class="value" style="color:#1e6091;">Rp {{ number_format($summary['total_value'], 0, ',', '.') }}</div>
        </div>
    </div>

    @if(!empty($filters['search']) || !empty($filters['low_only']))
        <div class="filter-line">
            <strong>Filter:</strong>
            @if(!empty($filters['search'])) Pencarian: "{{ $filters['search'] }}" @endif
            @if(!empty($filters['low_only'])) &middot; Hanya stok ≤ min @endif
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width:30px;" class="text-center">No</th>
                <th style="width:30px;">Kode</th>
                <th style="width:150px;">Part Number</th>
                <th style="width:150px;">Nama Barang</th>
                <th style="width:50px;">Merk</th>
                <th style="width:30px;">Satuan</th>
                <th style="width:60px;" class="text-end">Stok</th>
                <th style="width:50px;" class="text-end">Min</th>
                <th style="width:70px;">Status</th>
                <th style="width:90px;" class="text-end">Harga</th>
                <th style="width:110px;" class="text-end">Nilai Stok</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotal = 0; @endphp
            @foreach($items as $i => $r)
                @php
                    $stok   = (int) $r->stok_gudang;
                    $min    = (int) $r->min_stok_efektif;
                    $nilai  = $stok * (float) $r->harga_jual;
                    $grandTotal += $nilai;
                    $rowCls = $stok === 0 ? 'row-zero' : ($stok <= $min ? 'row-low' : '');
                    $status = $stok === 0 ? 'Kosong' : ($stok <= $min ? 'Di bwh min' : 'Aman');
                @endphp
                <tr class="{{ $rowCls }}">
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $r->kode_barang }}</td>
                    <td>{{ $r->part_number ?? '-' }}</td>
                    <td>{{ $r->nama_barang }}</td>
                    <td>{{ $r->merk?->nama_merk ?? '-' }}</td>
                    <td>{{ $r->satuan }}</td>
                    <td class="text-end"><strong>{{ number_format($stok, 0, ',', '.') }}</strong></td>
                    <td class="text-end">{{ number_format($min, 0, ',', '.') }}</td>
                    <td>{{ $status }}</td>
                    <td class="text-end">{{ $r->harga_jual ? 'Rp ' . number_format($r->harga_jual, 0, ',', '.') : '-' }}</td>
                    <td class="text-end">{{ $nilai > 0 ? 'Rp ' . number_format($nilai, 0, ',', '.') : '-' }}</td>
                </tr>
            @endforeach
            @if(!count($items))
                <tr><td colspan="11" class="text-center">— Tidak ada data —</td></tr>
            @endif
        </tbody>
        <tfoot>
            <tr>
                <th colspan="10" class="text-end">Grand Total Nilai Stok</th>
                <th class="text-end">Rp {{ number_format($grandTotal, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>

    <div class="signs">
        <div class="box">
            <div class="role">Penanggung Jawab Gudang</div>
            <div class="name">( {{ $gudang?->penanggung_jawab ?? '...........................' }} )</div>
        </div>
    </div>

    <div class="meta">
        Dicetak: {{ now()->format('d/m/Y H:i') }} &middot; Total baris: {{ count($items) }}
    </div>
</div>

<script>
    window.addEventListener('load', () => setTimeout(() => window.print(), 300));
</script>
</body>
</html>
