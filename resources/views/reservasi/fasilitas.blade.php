@extends('layouts.customer')
@section('title', $fasilitas->nama_fasilitas)

@php
    $satuan = $jenis->satuan->value;
    $meta   = \App\Support\KategoriMeta::get($fasilitas->kategori_fasilitas);

    // Sewa Per Jam & Convention Hall (Per Hari): pemakaian selalu 1 hari (otomatis 8 jam)
    // — cukup satu field tanggal, tidak perlu pilih jam mulai/selesai secara manual.
    $sehariSaja = $satuan === 'Jam'
        || ($fasilitas->kategori_fasilitas === 'Convention Hall' && $satuan === 'Hari');

    // Ruangan tambahan dari denah multi-select (URL: ?antrian=12,34,...).
    $antrianIds = array_filter(explode(',', (string) request('antrian')));
    $ruangLain  = $antrianIds
        ? \App\Models\Fasilitas::with('lantai')->whereIn('id_fasilitas', $antrianIds)->get()
        : collect();

    $semuaRuangan  = collect([$fasilitas])->concat($ruangLain);
    $jumlahRuangan = $semuaRuangan->count();
    $isMulti       = $jumlahRuangan > 1;
    $kapMin        = $semuaRuangan->min('kapasitas');
    $bawaan        = app(\App\Services\FasilitasBawaanService::class)->untuk($fasilitas, $satuan);
    $totalTarif    = $tarif->harga * $jumlahRuangan;
@endphp

@section('content')

    <style>
        .fp-page { --fp-soft:#e6f2f4; --fp-radius:1rem; }

        /* Breadcrumb */
        .fp-page .breadcrumb { font-size:.8rem; margin-bottom:1rem; }
        .fp-page .breadcrumb-item + .breadcrumb-item::before { color:#94a3b8; }
        .fp-page .breadcrumb-item a { color:var(--muted); text-decoration:none; font-weight:600; }
        .fp-page .breadcrumb-item a:hover { color:var(--primary); }
        .fp-page .breadcrumb-item.active { color:var(--ink); font-weight:700; }

        .fp-head { display:flex; align-items:center; justify-content:space-between; gap:.6rem; margin-bottom:.9rem; }
        .fp-head b { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:1rem; color:var(--ink); }
        .fp-head .cnt { background:var(--fp-soft); color:var(--primary); font-size:.72rem; font-weight:700;
            padding:.32rem .75rem; border-radius:2rem; display:inline-flex; align-items:center; gap:.3rem; }

        /* Mini-card per ruangan (mode multi) */
        .fp-mini { display:flex; gap:1rem; background:#fff; border:1px solid var(--line);
            border-radius:var(--fp-radius); padding:.9rem; margin-bottom:.75rem;
            transition:border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
        .fp-mini:hover { border-color:transparent; box-shadow:0 12px 28px -14px rgba(15,23,42,.16); transform:translateY(-2px); }
        .fp-mini .thumb { width:6.5rem; height:6.5rem; border-radius:.7rem;
            background-size:cover; background-position:center; background-color:#f7f9fc; flex:none; }
        .fp-mini .info { flex:1; min-width:0; display:flex; flex-direction:column; justify-content:center; }
        .fp-mini .eyebrow { font-size:.66rem; font-weight:700; letter-spacing:.14em;
            color:var(--primary); text-transform:uppercase; margin-bottom:.15rem; }
        .fp-mini h3 { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800;
            font-size:1rem; color:var(--ink); margin:0 0 .5rem; }
        .fp-mini .meta { display:flex; flex-wrap:wrap; gap:.35rem; }
        .fp-chip { display:inline-flex; align-items:center; gap:.3rem;
            background:#f7f9fc; color:var(--ink); font-size:.72rem; font-weight:600;
            padding:.28rem .6rem; border-radius:2rem; border:1px solid var(--line); }
        .fp-chip i { color:var(--muted); }

        /* Card tarif ringkas — .fp-summary-tarif (dipakai dalam kartu gabungan mode multi)
           berbagi styling teks yang sama, hanya beda wrapper (lihat .fp-summary-card). */
        .fp-tarif { background:linear-gradient(120deg, var(--fp-soft) 0%, #f0f9fa 100%);
            border:1px solid rgba(14,107,125,.15); border-radius:var(--fp-radius);
            padding:1.15rem 1.35rem; margin-bottom:.75rem;
            box-shadow:0 4px 14px -6px rgba(15,23,42,.06); }
        .fp-tarif small.lbl, .fp-summary-tarif small.lbl { display:block; color:var(--muted); font-size:.72rem; font-weight:600;
            margin-bottom:.4rem; letter-spacing:.02em; }
        .fp-tarif .row-t, .fp-summary-tarif .row-t { display:flex; justify-content:space-between; align-items:baseline;
            font-size:.85rem; color:var(--muted); }
        .fp-tarif .row-t + .row-t, .fp-summary-tarif .row-t + .row-t { margin-top:.6rem; padding-top:.6rem;
            border-top:1px dashed rgba(14,107,125,.2); }
        .fp-tarif .row-t b, .fp-summary-tarif .row-t b { font-family:'Plus Jakarta Sans',sans-serif; color:var(--ink); font-weight:700; }
        .fp-tarif .total, .fp-summary-tarif .total { font-family:'Plus Jakarta Sans',sans-serif;
            font-size:1.4rem; font-weight:800; color:var(--primary); }

        /* Card fasilitas termasuk */
        .fp-includes { background:#fff; border:1px solid var(--line);
            border-radius:var(--fp-radius); padding:1.15rem 1.35rem;
            box-shadow:0 4px 14px -6px rgba(15,23,42,.06); }
        .fp-includes h4 { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800;
            font-size:.9rem; margin:0 0 .85rem;
            display:flex; align-items:center; gap:.5rem; color:var(--ink); }
        .fp-includes h4 i { color:var(--primary); }
        .fp-includes ul { list-style:none; padding:0; margin:0;
            display:flex; flex-wrap:wrap; gap:.5rem; }
        .fp-includes li { font-size:.8rem; font-weight:600; color:#475569;
            display:inline-flex; align-items:center; gap:.4rem;
            background:var(--surface); border:1px solid var(--line);
            border-radius:.6rem; padding:.4rem .75rem; line-height:1.3; }
        .fp-includes li i { color:var(--primary); font-size:.85rem; flex:none; }
        .fp-includes .note { font-size:.75rem; color:var(--muted);
            margin:.85rem 0 0; font-style:italic; }

        /* Kartu ringkasan gabungan (mode multi): Tarif + Fasilitas Termasuk ditumpuk dalam
           SATU kartu, bukan 2 kotak berdampingan — menghindari 2 kotak yang tinggi tidak sama. */
        .fp-summary-card { background:#fff; border:1px solid var(--line);
            border-radius:var(--fp-radius); overflow:hidden;
            box-shadow:0 4px 14px -6px rgba(15,23,42,.06); }
        .fp-summary-card .fp-summary-tarif { background:linear-gradient(120deg, var(--fp-soft) 0%, #f0f9fa 100%);
            padding:1.15rem 1.35rem; border-bottom:1px solid rgba(14,107,125,.15); }
        .fp-summary-card .fp-includes { border:0; border-radius:0; box-shadow:none; }

        /* Hero card (mode single ruangan) — foto+info dan "Fasilitas termasuk" digabung
           jadi SATU kartu (bukan 2 kotak terpisah), .fp-hero-card yang jadi kartunya,
           .fp-hero sendiri cuma baris flex foto+body di dalamnya. */
        .fp-hero-card { background:#fff; border:1px solid var(--line);
            border-radius:var(--fp-radius); overflow:hidden;
            box-shadow:0 4px 14px -6px rgba(15,23,42,.06); }
        .fp-hero-card .fp-includes { border:0; border-radius:0; box-shadow:none;
            border-top:1px solid var(--line); }
        .fp-hero .foto-wrap { position:relative; }
        .fp-hero .foto { width:100%; height:220px; object-fit:cover; display:block; background:#f7f9fc; }
        .fp-carousel .carousel-item .foto { border-radius:0; }
        .fp-carousel .carousel-indicators { margin-bottom:.6rem; }
        .fp-carousel .carousel-indicators [data-bs-target] {
            width:.5rem; height:.5rem; border-radius:50%; background:#fff; opacity:.6; }
        .fp-carousel .carousel-indicators .active { opacity:1; }
        .fp-carousel .carousel-control-prev, .fp-carousel .carousel-control-next { width:2.75rem; opacity:0; transition:opacity .15s ease; }
        .fp-carousel:hover .carousel-control-prev, .fp-carousel:hover .carousel-control-next { opacity:1; }

        /* Foto zoomable → lightbox */
        .fp-zoomable { cursor:zoom-in; transition:filter .15s ease; }
        .fp-zoomable:hover { filter:brightness(.92); }

        .fp-lightbox { position:fixed; inset:0; z-index:2000; background:rgba(8,15,25,.9);
            display:none; align-items:center; justify-content:center; padding:2.5rem; cursor:zoom-out; }
        .fp-lightbox.show { display:flex; }
        .fp-lightbox img { max-width:100%; max-height:100%; border-radius:.9rem;
            box-shadow:0 24px 60px rgba(0,0,0,.5); cursor:default; }
        .fp-lightbox .fp-lightbox-close {
            position:absolute; top:1.2rem; right:1.4rem; width:2.6rem; height:2.6rem; border-radius:50%;
            border:none; background:rgba(255,255,255,.15); color:#fff; font-size:1.3rem;
            display:grid; place-items:center; cursor:pointer; transition:background .15s ease; }
        .fp-lightbox .fp-lightbox-close:hover { background:rgba(255,255,255,.28); }

        .fp-hero .cat-badge { position:absolute; top:.85rem; left:.85rem;
            background:rgba(255,255,255,.95); backdrop-filter:blur(6px);
            color:var(--primary); font-weight:700; font-size:.72rem;
            padding:.35rem .8rem; border-radius:2rem;
            box-shadow:0 4px 12px rgba(15,23,42,.1);
            display:inline-flex; align-items:center; gap:.4rem; }
        .fp-hero .body { padding:1.4rem 1.4rem 1.5rem; }
        .fp-hero .body .eyebrow { font-size:.68rem; font-weight:700; letter-spacing:.14em;
            color:var(--primary); text-transform:uppercase; margin-bottom:.35rem; }
        .fp-hero .body h1 { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800;
            font-size:1.35rem; color:var(--ink); margin:0 0 .75rem; }
        .fp-hero .meta { display:flex; flex-wrap:wrap; gap:.4rem; margin-bottom:1rem; }
        .fp-hero .fp-tarif { margin-bottom:1rem; }

        /* Detail ruangan — lebar penuh */
        .fp-hero { display:flex; flex-wrap:wrap; align-items:stretch; }
        .fp-hero .foto-wrap { flex:1 1 22rem; max-width:32rem; position:relative; }
        .fp-hero .foto, .fp-hero .fp-carousel, .fp-hero .fp-carousel .carousel-inner, .fp-hero .fp-carousel .carousel-item { height:100%; min-height:22rem; }
        .fp-hero .body { flex:1 1 20rem; padding:1.8rem 2rem; }
        @media (max-width: 767.98px) {
            .fp-hero .foto-wrap { max-width:none; }
            .fp-hero .foto, .fp-hero .fp-carousel, .fp-hero .fp-carousel .carousel-inner, .fp-hero .fp-carousel .carousel-item { min-height:16rem; }
        }
        .fp-detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; align-items:start; }
        @media (max-width: 767.98px) { .fp-detail-grid { grid-template-columns:1fr; } }
        .fp-mini-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(15.5rem, 1fr)); gap:.9rem; }

        @media (max-width: 991.98px) { .fp-mini .thumb { width:5.5rem; height:5.5rem; } }
        @media (max-width: 575.98px) { .fp-includes ul { grid-template-columns:1fr; } }
    </style>

    <div class="fp-page">

        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('reservasi.index') }}">Fasilitas</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('reservasi.index', ['kategori' => $fasilitas->kategori_fasilitas]) }}">{{ $fasilitas->kategori_fasilitas }}</a>
                </li>
                <li class="breadcrumb-item active">
                    {{ $isMulti ? "$jumlahRuangan Ruangan Terpilih" : $fasilitas->nama_fasilitas }}
                </li>
            </ol>
        </nav>

        @if ($semuaTarif->count() > 1 && ! $isMulti)
            <div class="fp-jenis-switch mb-3" data-reveal>
                <label class="fp-jenis-switch-label" for="fpJenisSewa"><i class="bi bi-tags"></i>Jenis Sewa</label>
                <select id="fpJenisSewa" class="form-select fp-jenis-switch-select"
                        onchange="window.location.href=this.value">
                    @foreach ($semuaTarif as $t)
                        <option value="{{ route('reservasi.fasilitas.show', ['fasilitas' => $fasilitas, 'jenis' => $t->id_jenis_sewa]) }}"
                                @selected($t->id_jenis_sewa === $jenis->id_jenis_sewa)>
                            Per {{ $t->jenisSewa->satuan->value }} &mdash; Rp {{ number_format($t->harga, 0, ',', '.') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <style>
                .fp-jenis-switch { display:flex; align-items:center; gap:.7rem; background:#fff; border:1px solid var(--line); border-radius:1rem; padding:.65rem .9rem; max-width:26rem; }
                .fp-jenis-switch-label { display:flex; align-items:center; gap:.4rem; font-size:.8rem; font-weight:700; color:var(--muted); white-space:nowrap; margin:0; }
                .fp-jenis-switch-label i { color:var(--primary); }
                .fp-jenis-switch-select { border:none; background:var(--surface); font-weight:700; color:var(--ink); border-radius:.65rem; }
                .fp-jenis-switch-select:focus { box-shadow:0 0 0 .18rem rgba(14,107,125,.14); }
            </style>
        @endif

        {{-- ═════════════════════════════════════════════════════
             Detail ruangan — lebar penuh (single vs multi)
             ═════════════════════════════════════════════════════ --}}
        <div data-reveal>

            @if ($isMulti)
                {{-- MODE MULTI: header + mini-card per ruangan (grid) + tarif + fasilitas --}}
                <div class="fp-head">
                    <b>{{ $jumlahRuangan }} Ruangan Terpilih</b>
                    <span class="cnt"><i class="bi bi-collection"></i>{{ $jumlahRuangan }} unit</span>
                </div>

                <div class="fp-mini-grid mb-4">
                    @foreach ($semuaRuangan as $f)
                        @php $fFoto = $f->fotoUrls()[0]; @endphp
                        <div class="fp-mini">
                            <div class="thumb fp-zoomable" data-zoom-src="{{ $fFoto }}" style="background-image:url('{{ $fFoto }}')" role="button" tabindex="0" aria-label="Perbesar foto {{ $f->nama_fasilitas }}"></div>
                            <div class="info">
                                <span class="eyebrow">Lantai {{ $f->lantai->nomor_lantai }} · {{ $f->kode_fasilitas }}</span>
                                <h3>{{ $f->nama_fasilitas }}</h3>
                                <div class="meta">
                                    <span class="fp-chip"><i class="bi bi-people"></i>{{ $f->kapasitas }} orang</span>
                                    <span class="fp-chip"><i class="bi bi-aspect-ratio"></i>{{ $f->luas }} m²</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="fp-summary-card">
                    <div class="fp-summary-tarif">
                        <small class="lbl">
                            Tarif per {{ $satuan }}@if($satuan === 'Hari' || $satuan === 'Jam') · 8 jam/hari @endif
                        </small>
                        <div class="row-t">
                            <span>Rp {{ number_format($tarif->harga, 0, ',', '.') }} × {{ $jumlahRuangan }} ruangan</span>
                            <b>Rp {{ number_format($totalTarif, 0, ',', '.') }}</b>
                        </div>
                        <div class="row-t">
                            <span>Total tarif dasar</span>
                            <span class="total">Rp {{ number_format($totalTarif, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="fp-includes">
                        <h4><i class="bi bi-gift"></i>Fasilitas termasuk</h4>
                        <ul>
                            @foreach ($bawaan as $d)
                                <li><i class="bi bi-check-circle-fill"></i>{{ $d }}</li>
                            @endforeach
                        </ul>
                        @if ($fasilitas->kategori_fasilitas === 'Working Space' && $satuan === 'Bulan')
                            <p class="note"><i class="bi bi-info-circle me-1"></i>Sewa bulanan Working Space diserahkan kosong (tanpa meja &amp; kursi).</p>
                        @endif
                    </div>
                </div>

            @else
                {{-- MODE SINGLE: hero card lebar (foto + info berdampingan) + Fasilitas
                     Termasuk digabung jadi satu kartu, bukan dua kotak terpisah. --}}
                @php $fotoList = $fasilitas->fotoUrls(); @endphp
                <div class="fp-hero-card mb-4">
                <div class="fp-hero">
                    <div class="foto-wrap">
                        @if (count($fotoList) > 1)
                            <div id="fpCarousel" class="carousel slide fp-carousel">
                                <div class="carousel-inner">
                                    @foreach ($fotoList as $i => $src)
                                        <div class="carousel-item @if ($i === 0) active @endif">
                                            <img src="{{ $src }}" alt="{{ $fasilitas->nama_fasilitas }} — foto {{ $i + 1 }}" class="foto fp-zoomable" data-zoom-src="{{ $src }}">
                                        </div>
                                    @endforeach
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#fpCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Sebelumnya</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#fpCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Berikutnya</span>
                                </button>
                                <div class="carousel-indicators">
                                    @foreach ($fotoList as $i => $src)
                                        <button type="button" data-bs-target="#fpCarousel" data-bs-slide-to="{{ $i }}" @if ($i === 0) class="active" @endif aria-label="Foto {{ $i + 1 }}"></button>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <img src="{{ $fotoList[0] }}" alt="{{ $fasilitas->nama_fasilitas }}" class="foto fp-zoomable" data-zoom-src="{{ $fotoList[0] }}">
                        @endif
                        <span class="cat-badge"><i class="bi {{ $meta['ikon'] }}"></i>{{ $fasilitas->kategori_fasilitas }}</span>
                    </div>
                    <div class="body">
                        <p class="eyebrow">Lantai {{ $fasilitas->lantai->nomor_lantai }} · {{ $fasilitas->kode_fasilitas }}</p>
                        <h1>{{ $fasilitas->nama_fasilitas }}</h1>
                        <div class="meta">
                            <span class="fp-chip"><i class="bi bi-people"></i>{{ $fasilitas->kapasitas }} orang</span>
                            <span class="fp-chip"><i class="bi bi-aspect-ratio"></i>{{ $fasilitas->luas }} m²</span>
                        </div>

                        <div class="fp-tarif">
                            <small class="lbl">
                                Tarif per {{ $satuan }}@if($satuan === 'Hari' || $satuan === 'Jam') · 8 jam/hari @endif
                            </small>
                            <span class="total">Rp {{ number_format($tarif->harga, 0, ',', '.') }}</span>
                        </div>

                        @if ($fasilitas->deskripsi)
                            <p class="text-muted small mb-0">{{ $fasilitas->deskripsi }}</p>
                        @endif
                    </div>
                </div>

                <div class="fp-includes">
                    <h4><i class="bi bi-gift"></i>Fasilitas termasuk</h4>
                    <ul>
                        @foreach ($bawaan as $d)
                            <li><i class="bi bi-check-circle-fill"></i>{{ $d }}</li>
                        @endforeach
                    </ul>
                    @if ($fasilitas->kategori_fasilitas === 'Working Space' && $satuan === 'Bulan')
                        <p class="note"><i class="bi bi-info-circle me-1"></i>Sewa bulanan Working Space diserahkan kosong (tanpa meja &amp; kursi).</p>
                    @endif
                </div>
                </div>
            @endif

            <button type="button" id="fpIsiJadwalBtn" class="btn btn-brand w-100 mt-4 py-3">
                <i class="bi bi-calendar-plus me-1"></i>Isi Jadwal
            </button>
        </div>

    {{-- Form Jadwal — modal Atur Jadwal Sewa, auto-open saat halaman dimuat --}}
    @include('reservasi.partials.atur-jadwal-modal')

    {{-- Lightbox foto — dipakai bersama oleh hero/carousel & thumb mini-card --}}
    <div class="fp-lightbox" id="fpLightbox">
        <button type="button" class="fp-lightbox-close" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
        <img src="" alt="" id="fpLightboxImg">
    </div>
    <script>
        (function () {
            const overlay = document.getElementById('fpLightbox');
            const img = document.getElementById('fpLightboxImg');
            if (!overlay || !img) return;

            const buka = (src, alt) => {
                img.src = src;
                img.alt = alt || '';
                overlay.classList.add('show');
                document.body.style.overflow = 'hidden';
            };
            const tutup = () => {
                overlay.classList.remove('show');
                document.body.style.overflow = '';
            };

            document.querySelectorAll('.fp-zoomable').forEach((el) => {
                el.addEventListener('click', () => buka(el.dataset.zoomSrc, el.alt || el.getAttribute('aria-label')));
                el.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); buka(el.dataset.zoomSrc, el.alt || el.getAttribute('aria-label')); }
                });
            });
            overlay.addEventListener('click', (e) => { if (e.target === overlay) tutup(); });
            overlay.querySelector('.fp-lightbox-close').addEventListener('click', tutup);
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') tutup(); });
        })();
    </script>

    <script>
        // Modal "Atur Jadwal Sewa" — dibuka lewat tombol "Isi Jadwal" (klik sengaja),
        // supaya Pemesan melihat Detail Fasilitas dulu sebelum lanjut isi jadwal.
        // Menutup modal (X, klik luar, atau Escape) TIDAK pernah pindah halaman — halaman
        // Detail Fasilitas ini tetap terlihat di belakang, itulah "kembali ke Detail Fasilitas".
        (function () {
            const overlay = document.getElementById('fpFormOverlay');
            const btnTutup = document.getElementById('fpFormClose');
            const btnBuka = document.getElementById('fpIsiJadwalBtn');
            if (!overlay) return;

            const buka = () => { overlay.classList.add('show'); document.body.style.overflow = 'hidden'; };
            const tutup = () => { overlay.classList.remove('show'); document.body.style.overflow = ''; };

            btnBuka?.addEventListener('click', buka);
            btnTutup?.addEventListener('click', tutup);
            overlay.addEventListener('click', (e) => { if (e.target === overlay) tutup(); });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && overlay.classList.contains('show')) tutup();
            });

            @if (($editItem ?? null) || $errors->any() || request()->boolean('buka'))
                {{-- Datang dari "Ubah" di Keranjang (data terisi), baru saja submit dengan
                     error validasi, atau baru ganti Jenis Sewa (Jam/Hari/Bulan) dari dalam modal
                     — buka modal langsung, jangan sampai pemesan terasa "keluar" dari form. --}}
                buka();
            @endif
        })();
    </script>
@endsection