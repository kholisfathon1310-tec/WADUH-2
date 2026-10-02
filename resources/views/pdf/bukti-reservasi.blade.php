{{--
  Template PDF "Bukti Reservasi" — dokumen ringkas untuk pemesan (BUKAN faktur/invoice
  pembayaran; untuk itu lihat resources/views/admin/pdf/faktur.blade.php). Tujuannya
  sebagai bukti tunjuk saat survei lapangan di lokasi BITC, sehingga kode reservasi
  ditampilkan besar & jelas. Kop surat & palet warna (hitam-putih) sengaja dibuat SAMA
  PERSIS dengan admin/pdf/faktur.blade.php supaya kedua dokumen terasa satu keluarga
  resmi, walau isinya tetap beda (di sini tidak ada info pembayaran/tanda tangan).
  Dirender lewat dompdf (barryvdh/laravel-dompdf) — font DejaVu Sans dipakai (bukan
  DM Sans/Plus Jakarta Sans dari web) karena dompdf tidak bisa memuat webfont .woff2
  tanpa konversi lokal; ini konsisten dengan admin/pdf/faktur.blade.php yang sudah ada.
  Variabel yang diharapkan:
    $inst          = config('institusi')
    $kodeTransaksi = "TRX-XXXXXX"
    $pemesan       = "Nama Lengkap"
    $items         = [ ['no'=>1,'kode'=>'RS186','unit'=>'R1','nama'=>...,'kategori'=>...,
                         'lantai'=>'1','tanggal'=>...,'waktu'=>...|null,'durasi'=>'2 Jam',
                         'total'=>200000], ... ]
    $total         = 200000
    $diterbitkan   = "4 Agustus 2026, 10.00 WIB"
--}}
@php
    if (! function_exists('rp')) {
        function rp($n) { return 'Rp '.number_format($n, 0, ',', '.'); }
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; color:#000; margin:0; padding:24px 28px; font-size:12px; }
    table { border-collapse: collapse; width:100%; }

    /* Header — SAMA PERSIS dengan admin/pdf/faktur.blade.php: logo kop surat resmi
       (logofaktur.png, BUKAN logo BITC) rata kiri, di-crop-zoom karena ada banyak
       ruang kosong di sekeliling lambangnya, + kolom spacer di kanan. */
    .hdr-table { border:none; }
    .hdr-table td { vertical-align: top; border:none; padding:0; }
    .logo-cell { width:180px; text-align:left; }
    .logo-frame { width:165px; height:165px; overflow:hidden; position:relative; }
    .logo-frame img { position:absolute; top:-27px; left:-80px; width:325px; height:auto; }
    .logo-box { width:165px; height:165px; border:1px dashed #999; text-align:center; line-height:165px; font-size:11px; color:#999; }
    .spacer-cell { width:90px; }
    .inst { text-align:center; padding-top:8px; }
    .inst .l1, .inst .l2 { font-weight:bold; font-size:14px; line-height:1.35; }
    .inst .l3 { font-weight:bold; font-size:14px; line-height:1.35; margin-bottom:5px; }
    .inst .addr { font-size:10.5px; line-height:1.55; }
    .inst .addr .alamat-1baris { white-space:nowrap; font-size:10px; }
    .rule { border:none; border-top:3px solid #000; margin:10px 0 4px; }
    .judul { text-align:center; font-size:22px; font-weight:bold; letter-spacing:1px; margin:14px 0 12px; }

    /* Kode besar */
    .kode-box { text-align:center; margin-bottom:18px; }
    .kode-besar { font-size:26px; font-weight:bold; letter-spacing:2px; color:#000;
                  border:2px solid #000; border-radius:6px; padding:9px 22px; display:inline-block; }

    /* Kepada + meta */
    .mid-table { width:100%; border:none; margin-bottom:14px; }
    .mid-table > tbody > tr > td { border:none; vertical-align:top; padding:0; }
    .to-cell { width:52%; }
    .to-row td { border:none; padding:2px 0; font-size:13px; }
    .to-label { width:70px; }
    .to-colon { width:14px; }
    .meta { border-collapse:collapse; width:100%; }
    .meta td { border:1px solid #000; padding:6px 9px; font-size:11.5px; }
    .meta .k { font-weight:bold; width:42%; }

    /* Tabel item — palet sama dengan .items admin/pdf/faktur.blade.php */
    .items { margin-top:4px; }
    .items th, .items td { border:1px solid #444; padding:7px 6px; font-size:10.5px; text-align:center; vertical-align:middle; }
    .items th { background:#d9d9d9; font-weight:bold; text-transform:uppercase; letter-spacing:.4px; font-size:9.5px; }
    .items td.left { text-align:left; }
    .items td.kode, .items td.unit { white-space:nowrap; }
    .items td.left .sub { color:#444; }
    .items .total-label { text-align:right; font-weight:bold; }
    .items .total-val { text-align:right; font-weight:bold; }

    /* Footer */
    .footer-note { margin-top:20px; padding:10px 12px; border:1px solid #000; border-radius:4px; font-size:10px; color:#333; }
    .terbit { margin-top:14px; font-size:10.5px; color:#333; }
    .terbit b { color:#000; }
</style>
</head>
<body>

    {{-- ===== HEADER ===== --}}
    <table class="hdr-table">
        <tr>
            <td class="logo-cell">
                @if(!empty($inst['logo_path']) && file_exists(public_path($inst['logo_path'])))
                    <div class="logo-frame"><img src="{{ public_path($inst['logo_path']) }}"></div>
                @else
                    <div class="logo-box">LOGO</div>
                @endif
            </td>
            <td class="inst">
                <div class="l1">{{ $inst['nama'] }}</div>
                <div class="l2">{{ $inst['departemen'] }}</div>
                <div class="l3">{{ $inst['unit'] }}</div>
                <div class="addr">
                    <span class="alamat-1baris">{{ $inst['alamat'] }}</span><br>
                    Telepon {{ $inst['telepon'] }}, Faks {{ $inst['fax'] }},<br>
                    Situs {{ $inst['website'] }}<br>
                    Surel {{ $inst['email'] }}
                </div>
            </td>
            <td class="spacer-cell"></td>
        </tr>
    </table>
    <hr class="rule">

    <div class="judul">BUKTI RESERVASI</div>

    {{-- ===== KODE BESAR ===== --}}
    <div class="kode-box">
        <div class="kode-besar">{{ $kodeTransaksi }}</div>
    </div>

    {{-- ===== KEPADA + META ===== --}}
    <table class="mid-table">
        <tr>
            <td class="to-cell">
                <table>
                    <tr class="to-row"><td class="to-label">Kepada</td><td class="to-colon">:</td><td><strong>{{ $pemesan }}</strong></td></tr>
                </table>
            </td>
            <td>
                <table class="meta">
                    <tr><td class="k">Kode Transaksi</td><td>: {{ $kodeTransaksi }}</td></tr>
                    <tr><td class="k">Jumlah Ruangan</td><td>: {{ count($items) }} ruangan</td></tr>
                    <tr><td class="k">Tanggal Terbit</td><td>: {{ $diterbitkan }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===== TABEL RUANGAN ===== --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width:3%;">No.</th>
                <th style="width:14%;">Kode Reservasi</th>
                <th style="width:8%;">Unit</th>
                <th style="width:21%;">Ruangan / Jenis</th>
                <th style="width:6%;">Lantai</th>
                <th style="width:14%;">Tanggal</th>
                <th style="width:12%;">Waktu</th>
                <th style="width:10%;">Durasi</th>
                <th style="width:12%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $it)
                <tr>
                    <td>{{ $it['no'] }}</td>
                    <td class="kode"><strong>{{ $it['kode'] }}</strong></td>
                    <td class="unit">{{ $it['unit'] }}</td>
                    <td class="left">{{ $it['nama'] }}<br><span class="sub">{{ $it['kategori'] }}</span></td>
                    <td>{{ $it['lantai'] }}</td>
                    <td>{{ $it['tanggal'] }}</td>
                    <td>{{ $it['waktu'] ?? '-' }}</td>
                    <td>{{ $it['durasi'] }}</td>
                    <td>{{ rp($it['total']) }}</td>
                </tr>
            @endforeach
            <tr>
                <td class="total-label" colspan="8">TOTAL KESELURUHAN</td>
                <td class="total-val">{{ rp($total) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="terbit">Diterbitkan pada: <b>{{ $diterbitkan }}</b></div>

</body>
</html>
