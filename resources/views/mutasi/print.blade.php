<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Print {{ $mutasi->nomor_mutasi }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        font-size: 11px; 
        color: #000; 
        padding: 20px; 
        background: #fff;
    }
    .doc { max-width: 800px; margin: 0 auto; background: #fff; }
    
    .header { 
        display: flex; 
        justify-content: space-between; 
        align-items: flex-start; 
        border-bottom: 3px solid #000; 
        padding-bottom: 12px; 
        margin-bottom: 16px; 
    }
    .header .company h2 { font-size: 20px; margin-bottom: 4px; font-weight: 700; }
    .header .company small { color: #666; font-size: 10px; }
    .doc-title { text-align: right; }
    .doc-title h3 { font-size: 16px; letter-spacing: 0.5px; font-weight: 700; margin-bottom: 6px; }
    .doc-title .nomor { font-size: 12px; font-weight: 600; margin-top: 4px; }
    .doc-title .tanggal { font-size: 11px; margin-top: 2px; color: #333; }

    .info { 
        display: grid; 
        grid-template-columns: repeat(3, 1fr); 
        gap: 16px; 
        margin-bottom: 16px; 
        padding: 12px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 4px;
    }
    .info > div { }
    .info .label { color: #666; font-size: 10px; text-transform: uppercase; margin-bottom: 4px; font-weight: 600; }
    .info .value { font-weight: 700; font-size: 12px; margin-bottom: 2px; }
    .info small { color: #666; font-size: 10px; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    table th, table td { border: 1px solid #333; padding: 8px; vertical-align: middle; }
    table th { background: #e9ecef; text-align: left; font-size: 10px; font-weight: 700; text-transform: uppercase; }
    table td { font-size: 11px; }
    .text-end { text-align: right; }
    .text-center { text-align: center; }

    .totals { border: none; margin-top: 8px; }
    .totals td { border: none; padding: 6px 8px; }
    .totals .lbl { text-align: right; color: #666; font-weight: 600; }
    .totals .val { text-align: right; font-weight: 700; width: 160px; font-size: 12px; }

    .signs { 
        display: grid; 
        grid-template-columns: repeat(3, 1fr); 
        gap: 20px; 
        margin-top: 48px; 
        margin-bottom: 24px;
    }
    .signs .box { text-align: center; }
    .signs .box .role { margin-bottom: 70px; font-weight: 700; font-size: 11px; }
    .signs .box .name { border-top: 1px solid #000; padding-top: 6px; display: inline-block; min-width: 150px; }

    .keterangan { 
        border: 1px dashed #999; 
        padding: 10px; 
        margin-bottom: 16px; 
        font-style: italic; 
        background: #fffbf0;
        border-radius: 4px;
    }
    .keterangan strong { font-style: normal; }
    
    .cancelled-notice { 
        border: 2px solid #dc3545; 
        background: #ffe6e6; 
        padding: 14px; 
        margin-bottom: 16px; 
        color: #721c24;
        border-radius: 4px;
    }
    .cancelled-notice h4 { color: #dc3545; margin-bottom: 8px; font-size: 13px; font-weight: 700; }
    .cancelled-notice p { margin: 4px 0; font-size: 11px; }
    
    .meta { 
        color: #666; 
        font-size: 9px; 
        text-align: right; 
        margin-top: 20px; 
        padding-top: 12px;
        border-top: 1px solid #dee2e6;
    }

    .no-print { margin-bottom: 20px; text-align: center; }
    .no-print button { 
        padding: 10px 24px; 
        cursor: pointer; 
        font-size: 13px;
        border: 1px solid #333;
        background: #fff;
        margin: 0 6px;
        border-radius: 4px;
        font-weight: 600;
    }
    .no-print button:hover { background: #f8f9fa; }
    .no-print button:first-child { background: #0d6efd; color: #fff; border-color: #0d6efd; }
    .no-print button:first-child:hover { background: #0b5ed7; }

    .rupiah { display: inline-flex; justify-content: space-between; width: 100%; }
    .rupiah .rp { margin-right: 4px; }
    .rupiah .amount { text-align: right; flex: 1; }

    @media print {
        .no-print { display: none !important; }
        body { padding: 0; }
        .doc { max-width: 100%; }
        @page { size: A4; margin: 15mm; }
    }
</style>
</head>
<body>
@php
    $tipeLabel = [
        'in'       => 'BUKTI BARANG MASUK',
        'out'      => 'SURAT JALAN / BARANG KELUAR',
        'transfer' => 'SURAT JALAN TRANSFER GUDANG',
        'adjust'   => 'BERITA ACARA PENYESUAIAN STOK',
    ][$mutasi->tipe] ?? 'MUTASI STOK';
    $totalNilai = (float) $mutasi->total_value;
@endphp

<div class="doc">
    <div class="no-print">
        <button onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
        <button onclick="window.close()">✖ Tutup</button>
    </div>

    <div class="header">
        <div class="company">
            <h2>{{ config('app.name', 'WAREHOUSE') }}</h2>
            <small>Sistem Manajemen Stok &amp; Mutasi Gudang</small>
        </div>
        <div class="doc-title">
            <h3>{{ $tipeLabel }}</h3>
            @if($mutasi->cancelled_at)
                <div style="color: #dc3545; font-weight: bold; margin-top: 4px; font-size: 12px;">[ DIBATALKAN ]</div>
            @endif
            <div class="nomor">No: {{ $mutasi->nomor_mutasi }}</div>
            <div class="tanggal">Tanggal: {{ \Illuminate\Support\Carbon::parse($mutasi->tanggal)->format('d/m/Y') }}</div>
        </div>
    </div>

    @if($mutasi->cancelled_at)
        <div class="cancelled-notice">
            <h4>⚠️ TRANSAKSI DIBATALKAN</h4>
            <p><strong>Dibatalkan oleh:</strong> {{ $mutasi->canceller?->name ?? '-' }}</p>
            <p><strong>Tanggal pembatalan:</strong> {{ \Illuminate\Support\Carbon::parse($mutasi->cancelled_at)->format('d/m/Y H:i') }}</p>
            <p><strong>Alasan:</strong> {{ $mutasi->cancellation_reason ?? '-' }}</p>
        </div>
    @endif

    <div class="info">
        <div>
            <div class="label">{{ $mutasi->tipe === 'in' ? 'Masuk Ke Gudang' : 'Dari Gudang' }}</div>
            <div class="value">{{ $mutasi->gudang?->nama_gudang ?? '-' }}</div>
            <small>{{ $mutasi->gudang?->kode_gudang }}</small>
            @if($mutasi->gudang?->alamat)
                <br><small>{{ $mutasi->gudang?->alamat }}</small>
            @endif
        </div>
        <div>
            @if($mutasi->tipe === 'transfer')
                <div class="label">Tujuan Gudang</div>
                <div class="value">{{ $mutasi->gudangTujuan?->nama_gudang ?? '-' }}</div>
                <small>{{ $mutasi->gudangTujuan?->kode_gudang }}</small>
                @if($mutasi->gudangTujuan?->alamat)
                    <br><small>{{ $mutasi->gudangTujuan?->alamat }}</small>
                @endif
            @elseif($mutasi->tipe === 'in' && $mutasi->supplier)
                <div class="label">Supplier</div>
                <div class="value">{{ $mutasi->supplier?->nama_supplier ?? '-' }}</div>
                <small>{{ $mutasi->supplier?->kode_supplier }}</small>
            @else
                <div class="label">Referensi</div>
                <div class="value">{{ $mutasi->referensi ?: '-' }}</div>
            @endif
        </div>
        <div>
            <div class="label">Petugas</div>
            <div class="value">{{ $mutasi->user?->name ?? '-' }}</div>
            @if($mutasi->tipe === 'transfer' && $mutasi->referensi)
                <small>Ref: {{ $mutasi->referensi }}</small>
            @elseif($mutasi->approver)
                <small>Disetujui: {{ $mutasi->approver?->name }}</small>
            @endif
        </div>
    </div>

    @if($mutasi->keterangan)
        <div class="keterangan"><strong>Keterangan:</strong> {{ $mutasi->keterangan }}</div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width:40px;" class="text-center">No</th>
                <th style="width:120px;">Kode Barang</th>
                <th>Nama Barang</th>
                <th style="width:80px;" class="text-center">Qty</th>
                <th style="width:60px;" class="text-center">Satuan</th>
                @if($totalNilai > 0)
                    <th style="width:110px;" class="text-end">Harga</th>
                    <th style="width:130px;" class="text-end">Subtotal</th>
                @endif
                <th style="width:150px;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($mutasi->items as $i => $it)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $it->barang?->kode_barang ?? '-' }}</td>
                    <td>{{ $it->barang?->nama_barang ?? '-' }}</td>
                    <td class="text-center"><strong>{{ number_format($it->qty, 0, ',', '.') }}</strong></td>
                    <td class="text-center">{{ $it->barang?->satuan ?? '-' }}</td>
                    @if($totalNilai > 0)
                        <td class="text-end">{!! $it->harga_satuan ? '<span class="rupiah"><span class="rp">Rp</span><span class="amount">' . number_format($it->harga_satuan, 0, ',', '.') . '</span></span>' : '-' !!}</td>
                        <td class="text-end">
                            @php $sub = (float)($it->harga_satuan ?? 0) * (float)$it->qty; @endphp
                            <strong>{!! $sub > 0 ? '<span class="rupiah"><span class="rp">Rp</span><span class="amount">' . number_format($sub, 0, ',', '.') . '</span></span>' : '-' !!}</strong>
                        </td>
                    @endif
                    <td>{{ $it->keterangan ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ $totalNilai > 0 ? 8 : 6 }}" class="text-center" style="padding: 20px; color: #999;">— Tidak ada item —</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="lbl">Total Qty:</td>
            <td class="val">{{ number_format($mutasi->total_qty, 0, ',', '.') }} Item</td>
        </tr>
        @if($totalNilai > 0)
        <tr>
            <td class="lbl">Total Nilai:</td>
            <td class="val"><span class="rupiah"><span class="rp">Rp</span><span class="amount">{{ number_format($totalNilai, 0, ',', '.') }}</span></span></td>
        </tr>
        @endif
    </table>

    <div class="signs">
        <div class="box">
            <div class="role">Dibuat Oleh</div>
            <div class="name">{{ $mutasi->user?->name ?? '_______________' }}</div>
        </div>
        <div class="box">
            <div class="role">{{ $mutasi->tipe === 'in' ? 'Pengirim' : 'Penerima' }}</div>
            <div class="name">_______________</div>
        </div>
        <div class="box">
            <div class="role">Mengetahui</div>
            <div class="name">{{ $mutasi->approver?->name ?? '_______________' }}</div>
        </div>
    </div>

    <div class="meta">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }} WIB &middot; {{ $mutasi->nomor_mutasi }}
    </div>
</div>

</body>
</html>
