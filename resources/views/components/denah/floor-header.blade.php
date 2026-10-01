{{--
    <x-denah.floor-header> — header lantai untuk halaman Denah Reservasi.
    Tampilan saja; seluruh angka dihitung di halaman pemanggil.
    Props:
      - nomorLantai : '1' | '2' | '3A' | '3B' | '5'
      - kategori    : kategori fasilitas yang sedang dilihat
      - satuan      : 'Jam' | 'Hari' | 'Bulan' | null
      - warna       : warna aksen lantai
      - total, kosong (Tersedia), sebagian (Sebagian Terisi), terisi : int
--}}
@props(['nomorLantai', 'kategori' => null, 'satuan' => null, 'warna' => '#176B87', 'total' => 0, 'kosong' => 0, 'sebagian' => 0, 'terisi' => 0])

<div class="dn-head">
    <div class="dn-head-main">
        <span class="dn-head-chip"><i class="bi bi-grid-3x3-gap"></i>{{ $kategori }} @if ($satuan)· Sewa per {{ $satuan }} @endif</span>
        <span class="dn-head-total">{{ $total }} ruangan</span>
    </div>
    <div class="dn-head-stats">
        <span class="dn-stat hijau"><b>{{ $kosong }}</b> Tersedia</span>
        <span class="dn-stat kuning"><b>{{ $sebagian }}</b> Sebagian</span>
        <span class="dn-stat merah"><b>{{ $terisi }}</b> Terisi</span>
    </div>
</div>

@once
    <style>
        .dn-head { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.6rem 1rem;
            background:#fff; border:1px solid var(--line); border-radius:1rem; padding:.75rem 1rem; margin-bottom:1rem; }
        .dn-head-main { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem .75rem; min-width:0; }
        .dn-head-chip { display:inline-flex; align-items:center; gap:.4rem; font-size:.82rem; font-weight:800; color:var(--primary-dark);
            background:var(--primary-soft); border:1px solid var(--primary-softer); padding:.35rem .75rem; border-radius:2rem; }
        .dn-head-total { font-size:.8rem; font-weight:600; color:var(--muted); }
        .dn-head-stats { display:flex; flex-wrap:wrap; gap:.4rem; }
        .dn-stat { display:inline-flex; align-items:center; gap:.35rem; font-size:.78rem; font-weight:600; color:#475569;
            padding:.3rem .7rem; border-radius:2rem; border:1px solid var(--line); background:#fff; }
        .dn-stat b { font-weight:800; color:var(--ink); }
        .dn-stat::before { content:''; width:.5rem; height:.5rem; border-radius:50%; }
        .dn-stat.hijau::before { background:#1e8a3d; } .dn-stat.kuning::before { background:#c98a1c; } .dn-stat.merah::before { background:#d94f3d; }
    </style>
@endonce
