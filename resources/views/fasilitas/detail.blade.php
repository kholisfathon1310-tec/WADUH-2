@extends('layouts.reservasi')
@section('title', $fasilitas->nama_fasilitas)

@php
    $meta = \App\Support\KategoriMeta::get($fasilitas->kategori_fasilitas);
    $urutanSatuan = ['Jam', 'Hari', 'Bulan'];
    $fotoList = $fasilitas->fotoUrls();
    $ikonSatuan = ['Jam' => 'bi-clock', 'Hari' => 'bi-calendar-date', 'Bulan' => 'bi-calendar3-range'];
    $minBulan = max(1, (int) ($tarifPerSatuan->get('Bulan')?->jenisSewa->durasi_minimum ?? 1));
    $catatanSatuan = [
        'Jam'   => 'Minimal 1 jam · 08.00–16.00 WIB',
        'Hari'  => '8 jam per hari · 08.00–16.00 WIB',
        'Bulan' => "Minimal {$minBulan} bulan",
    ];
    $satuanPertama = collect($urutanSatuan)->first(fn ($s) => $tarifPerSatuan->has($s));
@endphp

@section('content')
    {{-- Halaman informasi publik (tanpa form reservasi) — tata letak sama dengan Detail
         Fasilitas di area pemesan: galeri berasio tetap + info, lalu seluruh harga sewa. --}}
    <style>
        .fd-page { --fd-radius:1.1rem; --fd-soft:#f1f5f9; --fd-tint:#e6f2f4; }
        .fd-page .breadcrumb { font-size:.8rem; margin-bottom:1rem; flex-wrap:wrap; }
        .fd-page .breadcrumb-item a { color:var(--muted); text-decoration:none; font-weight:600; }
        .fd-page .breadcrumb-item a:hover { color:var(--primary); }
        .fd-page .breadcrumb-item.active { color:var(--ink); font-weight:700; }

        .fd-card { background:#fff; border:1px solid var(--line); border-radius:var(--fd-radius); box-shadow:0 4px 14px -6px rgba(15,23,42,.08); }
        .fd-section-title { display:flex; align-items:center; gap:.5rem; font-size:1rem; font-weight:800; color:var(--ink); margin:0 0 .9rem; }
        .fd-section-title i { color:var(--primary); }

        .fd-top { display:grid; grid-template-columns:minmax(0, 1.1fr) minmax(0, 1fr); gap:1.25rem; align-items:start; margin-bottom:1.25rem; }
        @media (max-width: 991.98px) { .fd-top { grid-template-columns:minmax(0, 1fr); gap:1rem; } }

        .fd-gallery { padding:.75rem; }
        .fd-gallery-main { position:relative; aspect-ratio:4 / 3; border-radius:.8rem; overflow:hidden; background:var(--fd-soft); }
        .fd-gallery-main img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:block; cursor:zoom-in; }
        .fd-badge { position:absolute; top:.75rem; left:.75rem; display:inline-flex; align-items:center; gap:.4rem;
            background:rgba(255,255,255,.95); color:var(--primary-dark); font-weight:800; font-size:.72rem;
            padding:.35rem .75rem; border-radius:2rem; box-shadow:0 4px 12px rgba(15,23,42,.12); }
        .fd-count { position:absolute; bottom:.75rem; right:.75rem; background:rgba(15,23,42,.72); color:#fff;
            font-size:.72rem; font-weight:700; padding:.25rem .6rem; border-radius:2rem; }
        .fd-nav { position:absolute; top:50%; transform:translateY(-50%); width:2.4rem; height:2.4rem; border-radius:50%;
            border:0; background:rgba(255,255,255,.92); color:var(--ink); display:grid; place-items:center; cursor:pointer;
            box-shadow:0 4px 12px rgba(15,23,42,.18); }
        .fd-nav:hover { background:#fff; }
        .fd-nav.prev { left:.6rem; } .fd-nav.next { right:.6rem; }
        .fd-thumbs { display:flex; gap:.5rem; margin-top:.6rem; overflow-x:auto; padding-bottom:.15rem; }
        .fd-thumb { flex:none; width:4.5rem; aspect-ratio:4 / 3; border-radius:.55rem; overflow:hidden; padding:0;
            border:2px solid transparent; background:var(--fd-soft); cursor:pointer; opacity:.7; transition:opacity .15s ease, border-color .15s ease; }
        .fd-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
        .fd-thumb:hover { opacity:1; }
        .fd-thumb.active { border-color:var(--primary); opacity:1; }

        .fd-info { padding:1.35rem 1.4rem 1.4rem; }
        .fd-eyebrow { font-size:.7rem; font-weight:800; letter-spacing:.12em; text-transform:uppercase; color:var(--primary); margin:0 0 .3rem; }
        .fd-nama { font-family:'Plus Jakarta Sans',sans-serif; font-size:1.45rem; font-weight:800; color:var(--ink); margin:0 0 .9rem; line-height:1.25; overflow-wrap:anywhere; }
        .fd-specs { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:.6rem; margin-bottom:1rem; }
        .fd-spec { background:var(--surface); border:1px solid var(--line); border-radius:.8rem; padding:.7rem .8rem; min-width:0; }
        .fd-spec i { color:var(--primary); font-size:1rem; }
        .fd-spec small { display:block; font-size:.66rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--muted); margin-top:.3rem; }
        .fd-spec b { display:block; font-size:.92rem; font-weight:800; color:var(--ink); overflow-wrap:anywhere; }
        @media (max-width: 575.98px) { .fd-specs { gap:.4rem; } .fd-spec { padding:.6rem .55rem; } .fd-spec b { font-size:.85rem; } }
        @media (max-width: 339.98px) { .fd-specs { grid-template-columns:1fr 1fr; } }
        .fd-desc { font-size:.86rem; color:#475569; line-height:1.6; margin:0 0 1rem; }
        .fd-cta { display:flex; flex-wrap:wrap; gap:.6rem; }
        .fd-cta .btn { flex:1 1 12rem; padding:.75rem 1rem; font-size:.9rem; }
        .fd-cta-note { font-size:.76rem; color:var(--muted); margin:.6rem 0 0; }

        .fd-prices, .fd-includes { padding:1.35rem 1.4rem 1.4rem; margin-bottom:1.25rem; }
        .fd-price-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(min(100%, 14rem), 1fr)); gap:.85rem; }
        .fd-price { display:flex; flex-direction:column; gap:.2rem; border:1px solid var(--line); border-radius:.95rem; padding:1rem 1.05rem; background:#fff; }
        .fd-price-unit { display:inline-flex; align-items:center; gap:.45rem; font-size:.85rem; font-weight:800; color:var(--ink); margin-bottom:.35rem; }
        .fd-price-unit i { display:grid; place-items:center; width:2rem; height:2rem; border-radius:.6rem; background:var(--fd-tint); color:var(--primary-dark); font-size:.95rem; }
        .fd-price-val { font-family:'Plus Jakarta Sans',sans-serif; font-size:1.35rem; font-weight:800; color:var(--primary-dark); letter-spacing:-.02em; line-height:1.2; }
        .fd-price-val small { font-size:.78rem; font-weight:600; color:var(--muted); letter-spacing:0; }
        .fd-price-note { font-size:.76rem; color:var(--muted); font-weight:500; }

        .fd-chips { list-style:none; margin:0; padding:0; display:flex; flex-wrap:wrap; gap:.5rem; }
        .fd-chips li { display:inline-flex; align-items:center; gap:.4rem; font-size:.8rem; font-weight:600; color:#334155;
            background:var(--surface); border:1px solid var(--line); border-radius:.6rem; padding:.4rem .75rem; }
        .fd-chips li i { color:#059669; font-size:.85rem; }
        .fd-note { font-size:.76rem; color:var(--muted); margin:.75rem 0 0; }

        .fp-lightbox { position:fixed; inset:0; z-index:2000; background:rgba(8,15,25,.9); display:none; align-items:center; justify-content:center; padding:1rem; cursor:zoom-out; }
        .fp-lightbox.show { display:flex; }
        .fp-lightbox img { max-width:100%; max-height:100%; border-radius:.75rem; box-shadow:0 24px 60px rgba(0,0,0,.5); cursor:default; }
        .fp-lightbox .fp-lightbox-close { position:absolute; top:1rem; right:1rem; width:2.6rem; height:2.6rem; border-radius:50%; border:none; background:rgba(255,255,255,.18); color:#fff; font-size:1.2rem; display:grid; place-items:center; cursor:pointer; }
        .fp-lightbox .fp-lightbox-close:hover { background:rgba(255,255,255,.3); }

        @media (max-width: 575.98px) {
            .fd-info, .fd-prices, .fd-includes { padding:1.1rem 1rem 1.15rem; }
            .fd-nama { font-size:1.25rem; }
        }
    </style>

    <div class="fd-page">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('fasilitas.index') }}">Fasilitas</a></li>
                <li class="breadcrumb-item">
                    <a href="{{ route('fasilitas.denah', ['kategori' => $fasilitas->kategori_fasilitas, 'lantai' => $fasilitas->id_lantai]) }}">Denah Lantai {{ $fasilitas->lantai->nomor_lantai }}</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">{{ $fasilitas->nama_fasilitas }}</li>
            </ol>
        </nav>

        <div class="fd-top">
            <div class="fd-card fd-gallery" data-reveal data-fd-galeri>
                <div class="fd-gallery-main">
                    <img src="{{ $fotoList[0] }}" alt="Foto {{ $fasilitas->nama_fasilitas }}" class="fp-zoomable" data-fd-utama data-zoom-src="{{ $fotoList[0] }}">
                    <span class="fd-badge"><i class="bi {{ $meta['ikon'] }}"></i>{{ $fasilitas->kategori_fasilitas }}</span>
                    @if (count($fotoList) > 1)
                        <button type="button" class="fd-nav prev" data-fd-geser="-1" aria-label="Foto sebelumnya"><i class="bi bi-chevron-left"></i></button>
                        <button type="button" class="fd-nav next" data-fd-geser="1" aria-label="Foto berikutnya"><i class="bi bi-chevron-right"></i></button>
                        <span class="fd-count"><span data-fd-ke>1</span> / {{ count($fotoList) }}</span>
                    @endif
                </div>
                @if (count($fotoList) > 1)
                    <div class="fd-thumbs">
                        @foreach ($fotoList as $i => $src)
                            <button type="button" class="fd-thumb {{ $i === 0 ? 'active' : '' }}" data-src="{{ $src }}" aria-label="Tampilkan foto {{ $i + 1 }}">
                                <img src="{{ $src }}" alt="" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="fd-card fd-info" data-reveal>
                <p class="fd-eyebrow">Lantai {{ $fasilitas->lantai->nomor_lantai }} · {{ $fasilitas->kode_fasilitas }}</p>
                <h1 class="fd-nama">{{ $fasilitas->nama_fasilitas }}</h1>

                <div class="fd-specs">
                    <div class="fd-spec"><i class="bi bi-people-fill"></i><small>Kapasitas</small><b>{{ $fasilitas->kapasitas }} orang</b></div>
                    <div class="fd-spec"><i class="bi bi-aspect-ratio"></i><small>Luas</small><b>{{ number_format($fasilitas->luas, 2, ',', '.') }} m²</b></div>
                    <div class="fd-spec"><i class="bi bi-geo-alt-fill"></i><small>Lokasi</small><b>Lantai {{ $fasilitas->lantai->nomor_lantai }}</b></div>
                </div>

                <p class="fd-desc mb-0">{{ $fasilitas->deskripsi ?: $meta['desk'] }}</p>
            </div>
        </div>

        <div class="fd-card fd-prices" data-reveal>
            <h2 class="fd-section-title"><i class="bi bi-tags"></i>Harga Sewa</h2>
            @if ($tarifPerSatuan->isEmpty())
                <p class="text-muted small mb-0">Tarif untuk fasilitas ini belum tersedia.</p>
            @else
                <div class="fd-price-grid">
                    @foreach ($urutanSatuan as $satuan)
                        @continue (! $tarifPerSatuan->has($satuan))
                        <div class="fd-price">
                            <span class="fd-price-unit"><i class="bi {{ $ikonSatuan[$satuan] }}"></i>Per {{ $satuan }}</span>
                            <div class="fd-price-val">Rp {{ number_format($tarifPerSatuan[$satuan]->harga, 0, ',', '.') }} <small>/ {{ strtolower($satuan) }}</small></div>
                            <div class="fd-price-note">{{ $catatanSatuan[$satuan] }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($satuanPertama)
            <div class="fd-card fd-includes" data-reveal>
                <h2 class="fd-section-title"><i class="bi bi-patch-check"></i>Fasilitas Termasuk</h2>
                <ul class="fd-chips">
                    @foreach ($bawaanPerSatuan[$satuanPertama] as $d)
                        <li><i class="bi bi-check-circle-fill"></i>{{ $d }}</li>
                    @endforeach
                </ul>
                @if ($fasilitas->kategori_fasilitas === 'Working Space' && $tarifPerSatuan->has('Bulan'))
                    <p class="fd-note"><i class="bi bi-info-circle me-1"></i>Untuk sewa bulanan, Working Space diserahkan dalam keadaan kosong (tanpa meja dan kursi).</p>
                @endif
            </div>
        @endif
    </div>

    <div class="fp-lightbox" id="fpLightbox">
        <button type="button" class="fp-lightbox-close" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
        <img src="" alt="" id="fpLightboxImg">
    </div>
    <script>
        (function () {
            document.querySelectorAll('[data-fd-galeri]').forEach((galeri) => {
                const utama = galeri.querySelector('[data-fd-utama]');
                const thumbs = [...galeri.querySelectorAll('.fd-thumb')];
                const ke = galeri.querySelector('[data-fd-ke]');
                if (!utama || !thumbs.length) return;
                let idx = 0;
                const tampil = (i) => {
                    idx = (i + thumbs.length) % thumbs.length;
                    utama.src = thumbs[idx].dataset.src;
                    utama.dataset.zoomSrc = thumbs[idx].dataset.src;
                    thumbs.forEach((t, n) => t.classList.toggle('active', n === idx));
                    if (ke) ke.textContent = idx + 1;
                    thumbs[idx].scrollIntoView({ block: 'nearest', inline: 'nearest' });
                };
                thumbs.forEach((t, n) => t.addEventListener('click', () => tampil(n)));
                galeri.querySelectorAll('[data-fd-geser]').forEach((b) => b.addEventListener('click', (e) => { e.stopPropagation(); tampil(idx + Number(b.dataset.fdGeser)); }));
            });

            const lb = document.getElementById('fpLightbox');
            const lbImg = document.getElementById('fpLightboxImg');
            const buka = (src, alt) => { lbImg.src = src; lbImg.alt = alt || ''; lb.classList.add('show'); document.body.style.overflow = 'hidden'; };
            const tutup = () => { lb.classList.remove('show'); document.body.style.overflow = ''; };
            document.querySelectorAll('.fp-zoomable').forEach((el) => el.addEventListener('click', () => buka(el.dataset.zoomSrc, el.alt)));
            lb.addEventListener('click', (e) => { if (e.target !== lbImg) tutup(); });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && lb.classList.contains('show')) tutup(); });
        })();
    </script>
@endsection
