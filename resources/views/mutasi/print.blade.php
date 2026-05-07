<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Surat Jalan {{ $mutasi->nomor_mutasi }}</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 12px; color: #000; margin: 0; padding: 24px; }
    .doc { max-width: 780px; margin: 0 auto; }
    h1, h2, h3, h4 { margin: 0; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 14px; }
    .header .company h2 { font-size: 18px; }
    .header .company small { color: #555; }
    .doc-title { text-align: right; }
    .doc-title h3 { font-size: 16px; letter-spacing: 1px; }
    .doc-title .nomor { font-size: 13px; font-weight: bold; margin-top: 4px; }

    .info { display: flex; gap: 24px; margin-bottom: 14px; }
    .info > div { flex: 1; }
    .info .label { color: #555; font-size: 11px; }
    .info .value { font-weight: bold; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    table th, table td { border: 1px solid #333; padding: 6px 8px; vertical-align: top; }
    table th { background: #f1f1f1; text-align: left; font-size: 11px; }
    .text-end { text-align: right; }
    .text-center { text-align: center; }

    .totals td { border: none; padding: 4px 8px; }
    .totals .lbl { text-align: right; color: #555; }
    .totals .val { text-align: right; font-weight: bold; width: 140px; }

    .signs { display: flex; justify-content: space-between; margin-top: 40px; gap: 20px; }
    .signs .box { flex: 1; text-align: center; }
    .signs .box .role { margin-bottom: 60px; font-weight: bold; }
    .signs .box .name { border-top: 1px solid #000; padding-top: 4px; }

    .keterangan { border: 1px dashed #888; padding: 8px; margin-bottom: 12px; font-style: italic; }
    .meta { color: #555; font-size: 10px; text-align: right; margin-top: 16px; }

    .no-print { margin-bottom: 16px; }
    .no-print button { padding: 6px 14px; cursor: pointer; }

    @media print {
        .no-print { display: none !important; }
        body { padding: 0; }
        .doc { max-width: 100%; }
    }
    @page { size: A4; margin: 12mm; }
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
        <button onclick="window.print()">Cetak / Simpan PDF</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <div class="header">
        <div class="company">
            {{-- <h2>{{ config('app.name', 'LOGISTIK') }}</h2> --}}
            <small>Sistem Manajemen Stok &amp; Mutasi Gudang</small>
        </div>
        <div class="doc-title">
            <h3>{{ $tipeLabel }}</h3>
            <div class="nomor">No: {{ $mutasi->nomor_mutasi }}</div>
            <div>Tanggal: {{ \Illuminate\Support\Carbon::parse($mutasi->tanggal)->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="info">
        <div>
            <div class="label">{{ $mutasi->tipe === 'in' ? 'Masuk Ke Gudang' : 'Dari Gudang' }}</div>
            <div class="value">{{ $mutasi->gudang?->nama_gudang ?? '-' }}</div>
            <small>{{ $mutasi->gudang?->kode_gudang }} &middot; {{ $mutasi->gudang?->alamat }}</small>
        </div>
        <div>
            <div class="label">{{ $mutasi->tipe === 'transfer' ? 'Tujuan Gudang' : 'Referensi' }}</div>
            @if($mutasi->tipe === 'transfer')
                <div class="value">{{ $mutasi->gudangTujuan?->nama_gudang ?? '-' }}</div>
                <small>{{ $mutasi->gudangTujuan?->kode_gudang }} &middot; {{ $mutasi->gudangTujuan?->alamat }}</small>
            @else
                <div class="value">{{ $mutasi->referensi ?? '-' }}</div>
            @endif
        </div>
        <div>
            <div class="label">Petugas</div>
            <div class="value">{{ $mutasi->user?->name ?? '-' }}</div>
            @if($mutasi->tipe === 'transfer' && $mutasi->referensi)
                <small>Ref: {{ $mutasi->referensi }}</small>
            @endif
        </div>
    </div>

    @if($mutasi->keterangan)
        <div class="keterangan"><strong>Keterangan:</strong> {{ $mutasi->keterangan }}</div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width:36px;" class="text-center">No</th>
                <th style="width:110px;">Kode</th>
                <th>Nama Barang</th>
                <th style="width:70px;" class="text-end">Qty</th>
                <th style="width:60px;">Satuan</th>
                @if($totalNilai > 0)
                    <th style="width:100px;" class="text-end">Harga</th>
                    <th style="width:120px;" class="text-end">Subtotal</th>
                @endif
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mutasi->items as $i => $it)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $it->barang?->kode_barang }}</td>
                    <td>{{ $it->barang?->nama_barang }}</td>
                    <td class="text-end">{{ number_format($it->qty, 0, ',', '.') }}</td>
                    <td>{{ $it->barang?->satuan }}</td>
                    @if($totalNilai > 0)
                        <td class="text-end">{{ $it->harga_satuan ? 'Rp ' . number_format($it->harga_satuan, 0, ',', '.') : '-' }}</td>
                        <td class="text-end">
                            @php $sub = (float)($it->harga_satuan ?? 0) * (int)$it->qty; @endphp
                            {{ $sub > 0 ? 'Rp ' . number_format($sub, 0, ',', '.') : '-' }}
                        </td>
                    @endif
                    <td>{{ $it->keterangan ?? '' }}</td>
                </tr>
            @endforeach
            @if(!count($mutasi->items))
                <tr><td colspan="{{ $totalNilai > 0 ? 8 : 6 }}" class="text-center">— Tidak ada item —</td></tr>
            @endif
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="lbl">Total Qty</td>
            <td class="val">{{ number_format($mutasi->total_qty, 0, ',', '.') }}</td>
        </tr>
        @if($totalNilai > 0)
        <tr>
            <td class="lbl">Total Nilai</td>
            <td class="val">Rp {{ number_format($totalNilai, 0, ',', '.') }}</td>
        </tr>
        @endif
    </table>

    <div class="signs">
        <div class="box">
            <div class="role">Penyerah</div>
            <div class="name">( {{ $mutasi->user?->name ?? '...........................' }} )</div>
        </div>
        <div class="box">
            <div class="role">Pengirim / Sopir</div>
            <div class="name">( ........................... )</div>
        </div>
        <div class="box">
            <div class="role">Penerima</div>
            <div class="name">( ........................... )</div>
        </div>
    </div>

    <div class="meta">
        Dicetak: {{ now()->format('d/m/Y H:i') }} &middot; {{ $mutasi->nomor_mutasi }}
    </div>
</div>

<script>
    window.addEventListener('load', () => setTimeout(() => window.print(), 300));
</script>
</body>
</html>
