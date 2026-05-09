<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan {{ $tipe === 'in' ? 'Barang Masuk' : 'Barang Keluar' }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        font-size: 9px; 
        color: #000; 
        padding: 10px; 
        background: #fff;
        max-width: 210mm;
        margin: 0 auto;
    }
    .doc { max-width: 100%; margin: 0 auto; background: #fff; }
    
    .header { 
        text-align: center;
        border-bottom: 2px solid #000; 
        padding-bottom: 8px; 
        margin-bottom: 12px; 
    }
    .header h2 { font-size: 16px; margin-bottom: 3px; font-weight: 700; }
    .header h3 { font-size: 13px; margin-bottom: 6px; font-weight: 600; }
    .header .period { font-size: 10px; color: #666; }

    .info { 
        display: grid; 
        grid-template-columns: repeat(2, 1fr); 
        gap: 12px; 
        margin-bottom: 16px; 
        padding: 10px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 4px;
    }
    .info .item { font-size: 10px; }
    .info .label { color: #666; font-weight: 600; }
    .info .value { font-weight: 700; }

    .summary { 
        margin-bottom: 15px;
        padding: 8px;
        background: #e7f3ff;
        border: 1px solid #0d6efd;
        border-radius: 3px;
    }
    .summary h4 { font-size: 11px; margin-bottom: 6px; color: #0d6efd; }
    .summary .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
    .summary .stat { text-align: center; }
    .summary .stat .label { font-size: 8px; color: #666; margin-bottom: 3px; }
    .summary .stat .value { font-size: 12px; font-weight: 700; color: #0d6efd; }

    .transaction { 
        margin-bottom: 18px; 
        page-break-inside: avoid;
        border: 1px solid #dee2e6;
        border-radius: 3px;
        overflow: hidden;
    }
    .transaction-header { 
        background: #f8f9fa; 
        padding: 6px 8px; 
        border-bottom: 1px solid #dee2e6;
        display: grid;
        grid-template-columns: 1.5fr 1fr 1fr 1fr;
        gap: 8px;
        align-items: center;
    }
    .transaction-header .nomor { font-weight: 700; font-size: 10px; }
    .transaction-header .info-item { font-size: 9px; }
    .transaction-header .label { color: #666; font-size: 8px; display: block; }
    .transaction-header .value { font-weight: 600; }

    table { width: 100%; border-collapse: collapse; }
    table th, table td { padding: 4px 6px; text-align: left; font-size: 9px; }
    table th { background: #fff; color: #666; font-weight: 600; border-bottom: 1px solid #dee2e6; }
    table td { border-bottom: 1px solid #f1f1f1; }
    table tbody tr:last-child td { border-bottom: none; }
    .text-end { text-align: right; }
    .text-center { text-align: center; }

    .transaction-footer {
        background: #f8f9fa;
        padding: 5px 8px;
        border-top: 1px solid #dee2e6;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .transaction-footer .total { font-weight: 700; font-size: 9px; }
    .transaction-footer .keterangan { font-size: 8px; color: #666; font-style: italic; }

    .grand-total {
        margin-top: 15px;
        padding: 10px;
        background: #d1ecf1;
        border: 1px solid #0c5460;
        border-radius: 3px;
        text-align: right;
    }
    .grand-total h4 { font-size: 11px; color: #0c5460; margin-bottom: 5px; }
    .grand-total .amount { font-size: 14px; font-weight: 700; color: #0c5460; }

    .meta { 
        color: #666; 
        font-size: 8px; 
        text-align: right; 
        margin-top: 12px; 
        padding-top: 8px;
        border-top: 1px solid #dee2e6;
    }

    .no-print { margin-bottom: 15px; text-align: center; }
    .no-print button { 
        padding: 8px 20px; 
        cursor: pointer; 
        font-size: 12px;
        border: 1px solid #333;
        background: #fff;
        margin: 0 5px;
        border-radius: 3px;
        font-weight: 600;
    }
    .no-print button:hover { background: #f8f9fa; }
    .no-print button:first-child { background: #0d6efd; color: #fff; border-color: #0d6efd; }
    .no-print button:first-child:hover { background: #0b5ed7; }

    @media print {
        .no-print { display: none !important; }
        body { padding: 0; }
        @page { size: A4 portrait; margin: 10mm; }
    }
</style>
</head>
<body>
@php
    $tipeLabel = [
        'in' => 'BARANG MASUK',
        'out' => 'BARANG KELUAR',
        'transfer' => 'TRANSFER GUDANG',
        'adjust' => 'PENYESUAIAN STOK',
    ][$tipe] ?? 'MUTASI STOK';

    $totalTransaksi = $mutasis->count();
    $totalQty = $mutasis->sum('total_qty');
    $totalNilai = $mutasis->sum('total_value');
    $totalItem = $mutasis->sum(function($m) { return $m->items->count(); });
@endphp

<div class="doc">
    <div class="no-print">
        <button onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
        <button onclick="window.close()">✖ Tutup</button>
    </div>

    <div class="header">
        <h2>{{ config('app.name', 'WAREHOUSE') }}</h2>
        <h3>LAPORAN {{ $tipeLabel }}</h3>
        <div class="period">
            @if($dateFrom && $dateTo)
                Periode: {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
            @elseif($dateFrom)
                Dari: {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}
            @elseif($dateTo)
                Sampai: {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
            @else
                Semua Periode
            @endif
            @if($gudang)
                &middot; Gudang: {{ $gudang->nama_gudang }}
            @endif
        </div>
    </div>

    <div class="summary">
        <h4>📊 Ringkasan</h4>
        <div class="stats">
            <div class="stat">
                <div class="label">Total Transaksi</div>
                <div class="value">{{ number_format($totalTransaksi, 0, ',', '.') }}</div>
            </div>
            <div class="stat">
                <div class="label">Total Item</div>
                <div class="value">{{ number_format($totalItem, 0, ',', '.') }}</div>
            </div>
            <div class="stat">
                <div class="label">Total Qty</div>
                <div class="value">{{ number_format($totalQty, 0, ',', '.') }}</div>
            </div>
            <div class="stat">
                <div class="label">Total Nilai</div>
                <div class="value">Rp {{ number_format($totalNilai, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    @forelse($mutasis as $mutasi)
        <div class="transaction">
            <div class="transaction-header">
                <div>
                    <div class="nomor">{{ $mutasi->nomor_mutasi }}</div>
                    <small style="color: #666;">{{ \Carbon\Carbon::parse($mutasi->tanggal)->format('d/m/Y') }}</small>
                </div>
                <div class="info-item">
                    <span class="label">{{ $tipe === 'in' ? 'Gudang' : 'Dari Gudang' }}</span>
                    <span class="value">{{ $mutasi->gudang?->nama_gudang ?? '-' }}</span>
                </div>
                <div class="info-item">
                    @if($tipe === 'in' && $mutasi->supplier)
                        <span class="label">Supplier</span>
                        <span class="value">{{ $mutasi->supplier?->nama_supplier ?? '-' }}</span>
                    @else
                        <span class="label">Referensi</span>
                        <span class="value">{{ $mutasi->referensi ?: '-' }}</span>
                    @endif
                </div>
                <div class="info-item">
                    <span class="label">Petugas</span>
                    <span class="value">{{ $mutasi->user?->name ?? '-' }}</span>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width:30px;" class="text-center">No</th>
                        <th style="width:100px;">Kode</th>
                        <th>Nama Barang</th>
                        <th style="width:60px;" class="text-center">Qty</th>
                        <th style="width:50px;" class="text-center">Satuan</th>
                        @if($mutasi->total_value > 0)
                            <th style="width:100px;" class="text-end">Harga</th>
                            <th style="width:110px;" class="text-end">Subtotal</th>
                        @endif
                        <th style="width:120px;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mutasi->items as $i => $item)
                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>
                            <td>{{ $item->barang?->kode_barang ?? '-' }}</td>
                            <td>{{ $item->barang?->nama_barang ?? '-' }}</td>
                            <td class="text-center"><strong>{{ number_format($item->qty, 0, ',', '.') }}</strong></td>
                            <td class="text-center">{{ $item->barang?->satuan ?? '-' }}</td>
                            @if($mutasi->total_value > 0)
                                <td class="text-end">{{ $item->harga_satuan ? 'Rp ' . number_format($item->harga_satuan, 0, ',', '.') : '-' }}</td>
                                <td class="text-end">
                                    @php $sub = (float)($item->harga_satuan ?? 0) * (float)$item->qty; @endphp
                                    <strong>{{ $sub > 0 ? 'Rp ' . number_format($sub, 0, ',', '.') : '-' }}</strong>
                                </td>
                            @endif
                            <td>{{ $item->keterangan ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="transaction-footer">
                <div class="keterangan">
                    @if($mutasi->keterangan)
                        <strong>Ket:</strong> {{ $mutasi->keterangan }}
                    @endif
                </div>
                <div class="total">
                    Total: {{ number_format($mutasi->total_qty, 0, ',', '.') }} Item
                    @if($mutasi->total_value > 0)
                        &middot; Rp {{ number_format($mutasi->total_value, 0, ',', '.') }}
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div style="text-align: center; padding: 40px; color: #999;">
            <p style="font-size: 14px;">Tidak ada data transaksi</p>
        </div>
    @endforelse

    @if($totalTransaksi > 0)
        <div class="grand-total">
            <h4>GRAND TOTAL</h4>
            <div>
                <strong>{{ number_format($totalTransaksi, 0, ',', '.') }}</strong> Transaksi &middot; 
                <strong>{{ number_format($totalQty, 0, ',', '.') }}</strong> Qty
                @if($totalNilai > 0)
                    <div class="amount">Rp {{ number_format($totalNilai, 0, ',', '.') }}</div>
                @endif
            </div>
        </div>
    @endif

    <div class="meta">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }} WIB
        @if($gudang)
            &middot; Gudang: {{ $gudang->nama_gudang }}
        @endif
    </div>
</div>

</body>
</html>
