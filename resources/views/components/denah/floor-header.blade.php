{{--
    <x-denah.floor-header> — FloorHeader: header lantai untuk halaman Denah Reservasi.
    Tampilan saja; seluruh angka dihitung di halaman pemanggil dari data yang sudah ada
    (tidak ada query tambahan di sini).
    Props:
      - nomorLantai : '1' | '2' | '3A' | '3B' | '5'
      - kategori    : string kategori fasilitas yang sedang dilihat
      - satuan      : 'Jam' | 'Hari' | 'Bulan' | null
      - warna       : hex warna aksen lantai (dipertahankan dari implementasi lama)
      - total, kosong, terisi : int
--}}
@props(['nomorLantai', 'kategori' => null, 'satuan' => null, 'warna' => '#176B87', 'total' => 0, 'kosong' => 0, 'terisi' => 0])

<div class="dn-head" style="--dn-accent: {{ $warna }}">
    <div class="dn-head-glow"></div>
    <div class="dn-head-main">
        <p class="dn-head-eyebrow">Langkah 4 dari 5 &middot; {{ $kategori }}@if($satuan) &middot; Per {{ $satuan }}@endif</p>
        <h1 class="dn-head-title"><i class="bi bi-map"></i>Denah Lantai {{ $nomorLantai }}</h1>
        <p class="dn-head-sub">Klik ruangan <strong>hijau</strong> untuk memesan. Warna mengikuti jadwal yang Anda pilih.</p>
    </div>
    <div class="dn-head-stats">
        <div class="dn-stat">
            <span class="dn-stat-v">{{ $total }}</span>
            <span class="dn-stat-l"><i class="bi bi-grid-3x3-gap-fill"></i>Total Ruang</span>
        </div>
        <div class="dn-stat">
            <span class="dn-stat-v">{{ $kosong }}</span>
            <span class="dn-stat-l"><i class="bi bi-check-circle-fill"></i>Kosong</span>
        </div>
        <div class="dn-stat">
            <span class="dn-stat-v">{{ $terisi }}</span>
            <span class="dn-stat-l"><i class="bi bi-x-circle-fill"></i>Terisi</span>
        </div>
    </div>
</div>

@once
    <style>
        .dn-head {
            position: relative; overflow:hidden; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1.25rem;
            padding: 1.6rem 1.75rem; border-radius: 1.5rem; margin-bottom: 1rem;
            background: linear-gradient(120deg, var(--dn-accent), color-mix(in srgb, var(--dn-accent) 65%, #176B87));
            color: #fff; box-shadow: 0 18px 38px -12px color-mix(in srgb, var(--dn-accent) 55%, transparent);
        }
        .dn-head-glow { position:absolute; inset:0; background: radial-gradient(120% 140% at 100% 0%, rgba(255,255,255,.16), transparent 55%); pointer-events:none; }
        .dn-head-main { position:relative; max-width: 32rem; }
        .dn-head-eyebrow { font-size:.72rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase; opacity:.85; margin-bottom:.4rem; }
        .dn-head-title { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:1.55rem; display:flex; align-items:center; gap:.55rem; margin-bottom:.4rem; }
        .dn-head-sub { margin:0; opacity:.92; font-size:.9rem; }
        .dn-head-stats { position:relative; display:flex; gap:.6rem; flex-wrap:wrap; }
        .dn-stat { background:rgba(255,255,255,.14); backdrop-filter:blur(8px); border:1px solid rgba(255,255,255,.22); border-radius:1rem; padding:.7rem 1.1rem; min-width:6.4rem; text-align:center; }
        .dn-stat-v { display:block; font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:1.4rem; line-height:1.1; }
        .dn-stat-l { display:flex; align-items:center; justify-content:center; gap:.3rem; font-size:.7rem; font-weight:600; opacity:.9; margin-top:.2rem; white-space:nowrap; }
        @media (max-width: 575.98px) {
            .dn-head { padding: 1.3rem; }
            .dn-stat { min-width: 5.4rem; padding:.55rem .7rem; }
        }
    </style>
@endonce
