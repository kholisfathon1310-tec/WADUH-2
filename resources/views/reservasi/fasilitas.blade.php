@extends('layouts.customer')
@section('title', 'Detail Fasilitas')

@php
    use Illuminate\Support\Carbon;

    $satuan = $jenis->satuan->value;

    // Sewa Per Jam & Convention Hall (Per Hari): pemakaian selalu dalam satu hari.
    $sehariSaja = $satuan === 'Jam'
        || ($fasilitas->kategori_fasilitas === 'Convention Hall' && $satuan === 'Hari');

    // $semuaRuangan = ruangan utama + ruangan lain dari denah multi-pilih (?antrian=12,34).
    // Tampilan satu ruangan maupun banyak ruangan memakai blok yang SAMA per ruangan.
    $jumlahRuangan = $semuaRuangan->count();
    $isMulti       = $jumlahRuangan > 1;
    $kapMin        = $semuaRuangan->min('kapasitas');
    $meta          = \App\Support\KategoriMeta::get($fasilitas->kategori_fasilitas);
    $bawaanService = app(\App\Services\FasilitasBawaanService::class);

    // Tarif tiap ruangan untuk jenis sewa tertentu (harga antar ruangan bisa berbeda).
    $tarifRuangan = fn (int $idFasilitas, int $idJenis) => ($tarifPerRuangan[$idFasilitas] ?? collect())->firstWhere('id_jenis_sewa', $idJenis);
    $totalTarif = $semuaRuangan->sum(fn ($f) => $tarifRuangan($f->id_fasilitas, $jenis->id_jenis_sewa)?->harga ?? 0);

    $tanggalAcuanC = Carbon::parse($tanggalAcuan);
    $ikonStatus = ['hijau' => 'bi-check-circle-fill', 'kuning' => 'bi-exclamation-circle-fill', 'merah' => 'bi-x-circle-fill'];
    $ketKondisi = function (array $k) {
        $jam = collect($k['jam_terisi'])
            ->reject(fn ($r) => $r['mulai'] === '00:00')
            ->map(fn ($r) => str_replace(':', '.', $r['mulai']).'–'.str_replace(':', '.', $r['selesai']))
            ->implode(', ');

        return match ($k['status']) {
            'kuning' => 'Sebagian jam sudah terisi'.($jam ? " ({$jam})" : '').'. Hanya dapat dipesan per jam pada jam yang masih kosong.',
            default  => 'Sudah terisi pada tanggal ini. Silakan periksa tanggal lain.',
        };
    };

    $minBulan = max(1, (int) ($semuaTarif->first(fn ($t) => $t->jenisSewa->satuan->value === 'Bulan')?->jenisSewa->durasi_minimum ?? 1));
    $catatanSatuan = [
        'Jam'   => 'Minimal 1 jam · 08.00–16.00 WIB',
        'Hari'  => '8 jam per hari · 08.00–16.00 WIB',
        'Bulan' => "Minimal {$minBulan} bulan",
    ];
    $ikonSatuanHarga = ['Jam' => 'bi-clock', 'Hari' => 'bi-calendar-date', 'Bulan' => 'bi-calendar3-range'];

    // Kartu harga: total seluruh ruangan terpilih per jenis sewa + apakah dapat dipesan.
    $kartuHarga = $semuaTarif->map(function ($t) use ($semuaRuangan, $tarifRuangan, $kondisi) {
        $s = $t->jenisSewa->satuan->value;
        $lengkap = $semuaRuangan->every(fn ($f) => $tarifRuangan($f->id_fasilitas, $t->id_jenis_sewa) !== null);

        return [
            'tarif'   => $t,
            'satuan'  => $s,
            'total'   => $semuaRuangan->sum(fn ($f) => $tarifRuangan($f->id_fasilitas, $t->id_jenis_sewa)?->harga ?? 0),
            'lengkap' => $lengkap,
            'bisa'    => $lengkap && ($kondisi['bisa'][$s] ?? false),
        ];
    });
    $adaYangBisa = $kartuHarga->contains(fn ($k) => $k['bisa']);

    $paramDasar = array_filter([
        'fasilitas'  => $fasilitas->id_fasilitas,
        'antrian'    => request('antrian'),
        'edit_index' => $editIndex,
    ], fn ($v) => $v !== null && $v !== '');
@endphp

@section('content')
    <style>
        .fd-page { --fd-radius:1.1rem; }
        .fd-page .breadcrumb { font-size:.8rem; margin-bottom:1rem; flex-wrap:wrap; }
        .fd-page .breadcrumb-item a { color:var(--muted); text-decoration:none; font-weight:600; }
        .fd-page .breadcrumb-item a:hover { color:var(--primary); }
        .fd-page .breadcrumb-item.active { color:var(--ink); font-weight:700; }

        .fd-card { background:#fff; border:1px solid var(--line); border-radius:var(--fd-radius); box-shadow:var(--card-shadow); }
        .fd-section-title { display:flex; align-items:center; gap:.5rem; font-size:1rem; font-weight:800; color:var(--ink); margin:0; }
        .fd-section-title i { color:var(--primary); }
        .fd-section-head { display:flex; align-items:center; justify-content:space-between; gap:.6rem 1rem; flex-wrap:wrap; margin-bottom:.9rem; }
        .fd-section-sub { font-size:.78rem; color:var(--muted); font-weight:500; margin:.15rem 0 0; }

        /* ── Ringkasan multi-pilih ── */
        .fd-multi-head { display:flex; align-items:center; gap:.85rem; padding:1rem 1.25rem; margin-bottom:1.25rem; }
        .fd-multi-ic { display:grid; place-items:center; width:2.75rem; height:2.75rem; border-radius:.85rem; flex:none;
            background:var(--primary-soft); color:var(--primary-dark); font-size:1.15rem; border:1px solid var(--primary-softer); }
        .fd-multi-head h1 { font-size:1.15rem; font-weight:800; margin:0; color:var(--ink); }
        .fd-multi-head p { font-size:.8rem; color:var(--muted); margin:.1rem 0 0; }

        /* ── Blok ruangan: galeri + informasi. Galeri memakai rasio TETAP, jadi foto setinggi/
              selebar apa pun tidak pernah menggeser kolom informasi. Dipakai sama persis untuk
              satu ruangan maupun tiap ruangan pada multi-pilih. ── */
        .fd-top { display:grid; grid-template-columns:minmax(0, 1.1fr) minmax(0, 1fr); gap:1.25rem; align-items:start; margin-bottom:1.25rem; }
        @media (max-width: 991.98px) { .fd-top { grid-template-columns:minmax(0, 1fr); gap:1rem; } }

        .fd-gallery { padding:.75rem; }
        .fd-gallery-main { position:relative; aspect-ratio:4 / 3; border-radius:.8rem; overflow:hidden; background:var(--surface-2); }
        .fd-gallery-main img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:block; cursor:zoom-in; }
        .fd-badge { position:absolute; top:.75rem; left:.75rem; display:inline-flex; align-items:center; gap:.4rem;
            background:rgba(255,255,255,.95); color:var(--primary-dark); font-weight:800; font-size:.72rem;
            padding:.35rem .75rem; border-radius:2rem; box-shadow:0 4px 12px rgba(15,23,42,.12); }
        .fd-count { position:absolute; bottom:.75rem; right:.75rem; background:rgba(15,23,42,.72); color:#fff;
            font-size:.72rem; font-weight:700; padding:.25rem .6rem; border-radius:2rem; }
        .fd-nav { position:absolute; top:50%; transform:translateY(-50%); width:2.4rem; height:2.4rem; border-radius:50%;
            border:0; background:rgba(255,255,255,.92); color:var(--ink); display:grid; place-items:center; cursor:pointer;
            box-shadow:0 4px 12px rgba(15,23,42,.18); transition:background .15s ease; }
        .fd-nav:hover { background:#fff; }
        .fd-nav.prev { left:.6rem; } .fd-nav.next { right:.6rem; }
        .fd-thumbs { display:flex; gap:.5rem; margin-top:.6rem; overflow-x:auto; padding-bottom:.15rem; }
        .fd-thumb { flex:none; width:4.5rem; aspect-ratio:4 / 3; border-radius:.55rem; overflow:hidden; padding:0;
            border:2px solid transparent; background:var(--surface-2); cursor:pointer; opacity:.7; transition:opacity .15s ease, border-color .15s ease; }
        .fd-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
        .fd-thumb:hover { opacity:1; }
        .fd-thumb.active { border-color:var(--primary); opacity:1; }

        .fd-info { padding:1.35rem 1.4rem 1.4rem; }
        .fd-eyebrow { display:flex; align-items:center; flex-wrap:wrap; gap:.35rem .6rem; font-size:.7rem; font-weight:800; letter-spacing:.12em;
            text-transform:uppercase; color:var(--primary); margin:0 0 .3rem; }
        .fd-urut { letter-spacing:.04em; background:var(--primary-soft); color:var(--primary-dark); border:1px solid var(--primary-softer);
            padding:.12rem .5rem; border-radius:2rem; font-size:.66rem; }
        .fd-nama { font-size:1.45rem; font-weight:800; color:var(--ink); margin:0 0 .9rem; line-height:1.25; overflow-wrap:anywhere; }
        .fd-specs { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:.6rem; margin-bottom:1rem; }
        .fd-spec { background:var(--surface); border:1px solid var(--line-soft); border-radius:.8rem; padding:.7rem .8rem; min-width:0; }
        .fd-spec i { color:var(--primary); font-size:1rem; }
        .fd-spec small { display:block; font-size:.66rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--muted); margin-top:.3rem; }
        .fd-spec b { display:block; font-size:.92rem; font-weight:800; color:var(--ink); overflow-wrap:anywhere; }
        @media (max-width: 575.98px) { .fd-specs { gap:.4rem; } .fd-spec { padding:.6rem .55rem; } .fd-spec b { font-size:.85rem; } }
        @media (max-width: 339.98px) { .fd-specs { grid-template-columns:1fr 1fr; } }
        .fd-desc { font-size:.86rem; color:#475569; line-height:1.6; margin:0 0 1rem; }

        .fd-avail { border:1px solid var(--line); border-radius:.9rem; padding:.85rem 1rem; }
        .fd-avail.hijau { background:#f0fdf6; border-color:#bbf7d0; }
        .fd-avail.kuning { background:var(--amber-tint); border-color:#fde68a; }
        .fd-avail.merah { background:var(--rose-tint); border-color:#fecdd3; }
        .fd-status { display:inline-flex; align-items:center; gap:.4rem; font-size:.8rem; font-weight:800; padding:.3rem .75rem; border-radius:2rem; border:1px solid; background:#fff; }
        .fd-status.hijau { color:#047857; border-color:#a7f3d0; }
        .fd-status.kuning { color:#a16207; border-color:#fde68a; }
        .fd-status.merah { color:#be123c; border-color:#fecdd3; }
        .fd-avail p { font-size:.8rem; color:#334155; margin:.55rem 0 0; line-height:1.5; }
        .fd-avail p b { color:var(--ink); }

        .fd-tarif-list { list-style:none; margin:1rem 0 0; padding:0; border:1px solid var(--line); border-radius:.9rem; overflow:hidden; }
        .fd-tarif-list li { display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.6rem .9rem; font-size:.84rem; }
        .fd-tarif-list li + li { border-top:1px solid var(--line-soft); }
        .fd-tarif-list span { display:inline-flex; align-items:center; gap:.45rem; color:#475569; font-weight:600; }
        .fd-tarif-list span i { color:var(--primary); }
        .fd-tarif-list b { font-weight:800; color:var(--ink); white-space:nowrap; }
        .fd-tarif-list li.is-aktif { background:var(--primary-tint); }
        .fd-tarif-list li.is-aktif b { color:var(--primary-dark); }

        /* ── Harga sewa: seluruh jenis sewa tampil berdampingan, tanpa filter ── */
        .fd-prices { padding:1.35rem 1.4rem 1.4rem; margin-bottom:1.25rem; }
        .fd-tanggal { display:flex; align-items:center; gap:.5rem; margin:0; }
        .fd-tanggal label { font-size:.76rem; font-weight:700; color:var(--muted); margin:0; white-space:nowrap; }
        .fd-tanggal input { border:1px solid var(--line); border-radius:.65rem; padding:.4rem .6rem; font-size:.85rem; font-weight:700;
            color:var(--ink); background:#fff; font-family:inherit; min-height:2.5rem; }
        .fd-tanggal input:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(23,107,135,.12); }
        .fd-price-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(min(100%, 14rem), 1fr)); gap:.85rem; }
        .fd-price { display:flex; flex-direction:column; gap:.2rem; border:1px solid var(--line); border-radius:.95rem;
            padding:1rem 1.05rem 1.05rem; background:#fff; transition:border-color .15s ease, box-shadow .15s ease; }
        .fd-price.is-aktif { border-color:var(--primary); box-shadow:0 0 0 3px rgba(23,107,135,.1); }
        .fd-price.is-tutup { background:var(--surface); }
        .fd-price-top { display:flex; align-items:center; justify-content:space-between; gap:.5rem; flex-wrap:wrap; margin-bottom:.35rem; }
        .fd-price-unit { display:inline-flex; align-items:center; gap:.45rem; font-size:.85rem; font-weight:800; color:var(--ink); }
        .fd-price-unit i { display:grid; place-items:center; width:2rem; height:2rem; border-radius:.6rem; background:var(--primary-soft); color:var(--primary-dark); font-size:.95rem; }
        .fd-price-badge { font-size:.66rem; font-weight:800; padding:.22rem .55rem; border-radius:2rem; border:1px solid; white-space:nowrap; }
        .fd-price-badge.ok { background:var(--emerald-soft); color:#047857; border-color:#a7f3d0; }
        .fd-price-badge.no { background:#f1f5f9; color:#64748b; border-color:var(--line); }
        .fd-price-val { font-size:1.35rem; font-weight:800; color:var(--primary-dark); letter-spacing:-.02em; line-height:1.2; }
        .fd-price-val small { font-size:.78rem; font-weight:600; color:var(--muted); letter-spacing:0; }
        .fd-price.is-tutup .fd-price-val { color:#64748b; }
        .fd-price-note { font-size:.76rem; color:var(--muted); font-weight:500; line-height:1.45; }
        .fd-price-btn { margin-top:auto; display:inline-flex; align-items:center; justify-content:center; gap:.4rem; width:100%;
            padding:.6rem .8rem; min-height:2.6rem; border-radius:.7rem; font-size:.82rem; font-weight:800; font-family:inherit; text-decoration:none; cursor:pointer;
            border:1px solid var(--primary); background:#fff; color:var(--primary-dark); transition:background .15s ease, color .15s ease; }
        .fd-price-note + .fd-price-btn { margin-top:.75rem; }
        .fd-price-btn:hover { background:var(--primary); color:#fff; }
        .fd-price.is-aktif .fd-price-btn { background:var(--primary); color:#fff; }
        .fd-price.is-aktif .fd-price-btn:hover { background:var(--primary-dark); }
        .fd-price-btn.is-tutup { border-color:var(--line); color:#94a3b8; background:transparent; cursor:not-allowed; pointer-events:none; }
        .fd-tutup-note { display:flex; align-items:flex-start; gap:.5rem; margin:.9rem 0 0; padding:.7rem .9rem; border-radius:.8rem;
            background:var(--rose-tint); border:1px solid #fecdd3; color:#9f1239; font-size:.8rem; line-height:1.5; }

        .fd-includes { padding:1.35rem 1.4rem 1.4rem; margin-bottom:1.25rem; }
        .fd-chips { list-style:none; margin:0; padding:0; display:flex; flex-wrap:wrap; gap:.5rem; }
        .fd-chips li { display:inline-flex; align-items:center; gap:.4rem; font-size:.8rem; font-weight:600; color:#334155;
            background:var(--surface); border:1px solid var(--line-soft); border-radius:.6rem; padding:.4rem .75rem; }
        .fd-chips li i { color:var(--emerald); font-size:.85rem; }
        .fd-note { font-size:.76rem; color:var(--muted); margin:.75rem 0 0; }

        /* Lightbox foto */
        .fp-lightbox { position:fixed; inset:0; z-index:2000; background:rgba(8,15,25,.9);
            display:none; align-items:center; justify-content:center; padding:1rem; cursor:zoom-out; }
        .fp-lightbox.show { display:flex; }
        .fp-lightbox img { max-width:100%; max-height:100%; border-radius:.75rem; box-shadow:0 24px 60px rgba(0,0,0,.5); cursor:default; }
        .fp-lightbox .fp-lightbox-close { position:absolute; top:1rem; right:1rem; width:2.6rem; height:2.6rem; border-radius:50%;
            border:none; background:rgba(255,255,255,.18); color:#fff; font-size:1.2rem; display:grid; place-items:center; cursor:pointer; }
        .fp-lightbox .fp-lightbox-close:hover { background:rgba(255,255,255,.3); }

        @media (max-width: 575.98px) {
            .fd-info, .fd-prices, .fd-includes { padding:1.1rem 1rem 1.15rem; }
            .fd-multi-head { padding:.9rem 1rem; }
            .fd-nama { font-size:1.25rem; }
            .fd-tanggal { width:100%; }
            .fd-tanggal input { flex:1; min-width:0; font-size:1rem; }
        }
    </style>

    <div class="fd-page">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('reservasi.index') }}">Fasilitas</a></li>
                <li class="breadcrumb-item">
                    <a href="{{ route('reservasi.denah', ['kategori' => $fasilitas->kategori_fasilitas, 'lantai' => $fasilitas->id_lantai, 'jenis' => $jenis->id_jenis_sewa]) }}">Denah Lantai {{ $fasilitas->lantai->nomor_lantai }}</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">{{ $isMulti ? "$jumlahRuangan Fasilitas Terpilih" : $fasilitas->nama_fasilitas }}</li>
            </ol>
        </nav>

        @if ($isMulti)
            <div class="fd-card fd-multi-head" data-reveal>
                <span class="fd-multi-ic"><i class="bi bi-collection"></i></span>
                <div>
                    <h1>{{ $jumlahRuangan }} Fasilitas Terpilih</h1>
                    <p>Satu jadwal berlaku untuk seluruh fasilitas di bawah ini.</p>
                </div>
            </div>
        @endif

        {{-- ═══ BLOK RUANGAN — struktur yang sama untuk satu maupun banyak ruangan ═══ --}}
        @foreach ($semuaRuangan as $urut => $f)
            @php
                $fFoto = $f->fotoUrls();
                $fMeta = \App\Support\KategoriMeta::get($f->kategori_fasilitas);
                $fKondisi = $kondisiPerRuangan[$f->id_fasilitas];
                $fTarif = ($tarifPerRuangan[$f->id_fasilitas] ?? collect())
                    ->sortBy(fn ($t) => array_search($t->jenisSewa->satuan->value, ['Jam', 'Hari', 'Bulan']));
            @endphp
            <div class="fd-top" data-reveal>
                <div class="fd-card fd-gallery" data-fd-galeri>
                    <div class="fd-gallery-main">
                        <img src="{{ $fFoto[0] }}" alt="Foto {{ $f->nama_fasilitas }}" class="fp-zoomable" data-fd-utama data-zoom-src="{{ $fFoto[0] }}" @if ($urut > 0) loading="lazy" @endif>
                        <span class="fd-badge"><i class="bi {{ $fMeta['ikon'] }}"></i>{{ $f->kategori_fasilitas }}</span>
                        @if (count($fFoto) > 1)
                            <button type="button" class="fd-nav prev" data-fd-geser="-1" aria-label="Foto sebelumnya"><i class="bi bi-chevron-left"></i></button>
                            <button type="button" class="fd-nav next" data-fd-geser="1" aria-label="Foto berikutnya"><i class="bi bi-chevron-right"></i></button>
                            <span class="fd-count"><span data-fd-ke>1</span> / {{ count($fFoto) }}</span>
                        @endif
                    </div>
                    @if (count($fFoto) > 1)
                        <div class="fd-thumbs">
                            @foreach ($fFoto as $i => $src)
                                <button type="button" class="fd-thumb {{ $i === 0 ? 'active' : '' }}" data-src="{{ $src }}" aria-label="Tampilkan foto {{ $i + 1 }}">
                                    <img src="{{ $src }}" alt="" loading="lazy">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="fd-card fd-info">
                    <p class="fd-eyebrow">
                        @if ($isMulti)<span class="fd-urut">Fasilitas {{ $urut + 1 }} dari {{ $jumlahRuangan }}</span>@endif
                        <span>Lantai {{ $f->lantai->nomor_lantai }} · {{ $f->kode_fasilitas }}</span>
                    </p>
                    @if ($isMulti)
                        <h2 class="fd-nama">{{ $f->nama_fasilitas }}</h2>
                    @else
                        <h1 class="fd-nama">{{ $f->nama_fasilitas }}</h1>
                    @endif

                    <div class="fd-specs">
                        <div class="fd-spec"><i class="bi bi-people-fill"></i><small>Kapasitas</small><b>{{ $f->kapasitas }} orang</b></div>
                        <div class="fd-spec"><i class="bi bi-aspect-ratio"></i><small>Luas</small><b>{{ number_format($f->luas, 2, ',', '.') }} m²</b></div>
                        <div class="fd-spec"><i class="bi bi-geo-alt-fill"></i><small>Lokasi</small><b>Lantai {{ $f->lantai->nomor_lantai }}</b></div>
                    </div>

                    <p class="fd-desc">{{ $f->deskripsi ?: $fMeta['desk'] }}</p>

                    {{-- Keterangan hanya untuk ruangan yang sudah (sebagian) terisi — kondisi Tersedia tidak perlu ditampilkan. --}}
                    {{-- Wilayah live: ketersediaan diperbarui realtime (lihat partials/pantau-status). --}}
                    <div data-live="kondisi-{{ $f->id_fasilitas }}">
                    @if ($fKondisi['status'] !== 'hijau')
                    <div class="fd-avail {{ $fKondisi['status'] }}">
                        <span class="fd-status {{ $fKondisi['status'] }}"><i class="bi {{ $ikonStatus[$fKondisi['status']] }}"></i>{{ $fKondisi['label'] }}</span>
                        <p><b>{{ $tanggalAcuanC->translatedFormat('l, j F Y') }}.</b> {{ $ketKondisi($fKondisi) }}</p>
                    </div>
                    @endif
                    </div>

                    <ul class="fd-tarif-list" aria-label="Tarif {{ $f->nama_fasilitas }}">
                        @foreach ($fTarif as $t)
                            <li>
                                <span><i class="bi {{ $ikonSatuanHarga[$t->jenisSewa->satuan->value] ?? 'bi-tag' }}"></i>Per {{ $t->jenisSewa->satuan->value }}</span>
                                <b>Rp {{ number_format($t->harga, 0, ',', '.') }}</b>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endforeach

        {{-- ═══ HARGA SEWA — seluruh jenis sewa ditampilkan sekaligus ═══ --}}
        <div class="fd-card fd-prices" data-reveal>
            <div class="fd-section-head">
                <div>
                    <h2 class="fd-section-title"><i class="bi bi-tags"></i>Harga Sewa{{ $isMulti ? " ($jumlahRuangan fasilitas)" : '' }}</h2>
                    <p class="fd-section-sub">Jenis sewa yang dapat dipesan mengikuti kondisi fasilitas pada tanggal yang dipilih.</p>
                </div>
                <form method="GET" action="{{ route('reservasi.fasilitas.show', $fasilitas) }}" class="fd-tanggal" id="fdTanggalForm">
                    <input type="hidden" name="jenis" value="{{ $jenis->id_jenis_sewa }}">
                    @if (request()->filled('antrian')) <input type="hidden" name="antrian" value="{{ request('antrian') }}"> @endif
                    @if ($editIndex !== null) <input type="hidden" name="edit_index" value="{{ $editIndex }}"> @endif
                    <label for="fdTanggal">Ketersediaan pada</label>
                    <input type="date" id="fdTanggal" name="tanggal_mulai" min="{{ now()->toDateString() }}" value="{{ $tanggalAcuan }}">
                </form>
            </div>

            <div data-live="harga">
            <div class="fd-price-grid">
                @foreach ($kartuHarga as $k)
                    @php $t = $k['tarif']; $s = $k['satuan']; $aktif = $t->id_jenis_sewa === $jenis->id_jenis_sewa; @endphp
                    <div class="fd-price {{ $k['bisa'] ? '' : 'is-tutup' }}">
                        <div class="fd-price-top">
                            <span class="fd-price-unit"><i class="bi {{ $ikonSatuanHarga[$s] ?? 'bi-tag' }}"></i>Per {{ $s }}</span>
                            <span class="fd-price-badge {{ $k['bisa'] ? 'ok' : 'no' }}">{{ $k['bisa'] ? 'Dapat dipesan' : 'Tidak tersedia' }}</span>
                        </div>
                        <div class="fd-price-val">Rp {{ number_format($k['total'], 0, ',', '.') }} <small>/ {{ strtolower($s) }}</small></div>
                        <div class="fd-price-note">
                            {{ $catatanSatuan[$s] ?? '' }}@if ($isMulti) · total {{ $jumlahRuangan }} fasilitas @endif
                            @unless ($k['lengkap']) <br>Tidak semua fasilitas terpilih menyediakan jenis sewa ini. @endunless
                        </div>
                        @if (! $k['bisa'])
                            <span class="fd-price-btn is-tutup" aria-disabled="true"><i class="bi bi-slash-circle"></i>Tidak dapat dipesan</span>
                        @elseif ($aktif)
                            <button type="button" class="fd-price-btn" data-fd-buka><i class="bi bi-calendar-plus"></i>Isi Jadwal</button>
                        @else
                            <a class="fd-price-btn" href="{{ route('reservasi.fasilitas.show', $paramDasar + ['jenis' => $t->id_jenis_sewa, 'tanggal_mulai' => $tanggalAcuan, 'buka' => 1]) }}"><i class="bi bi-calendar-plus"></i>Isi Jadwal</a>
                        @endif
                    </div>
                @endforeach
            </div>

            @if (! $adaYangBisa)
                @php
                    $penghalang = $semuaRuangan
                        ->filter(fn ($f) => $kondisiPerRuangan[$f->id_fasilitas]['status'] !== 'hijau')
                        ->map(fn ($f) => $f->nama_fasilitas.' ('.strtolower($kondisiPerRuangan[$f->id_fasilitas]['label']).')')
                        ->implode(', ');
                @endphp
                <p class="fd-tutup-note"><i class="bi bi-info-circle-fill"></i><span>{{ $isMulti ? 'Fasilitas terpilih' : 'Fasilitas' }} tidak dapat dipesan pada {{ $tanggalAcuanC->translatedFormat('j F Y') }}@if ($isMulti && $penghalang) karena {{ $penghalang }}@endif. Pilih
 tanggal lain pada kolom <b>Ketersediaan pada</b> untuk memeriksa jadwal yang masih kosong.@if ($editItem) <a href="#" data-fd-buka>Ubah jadwal item keranjang</a>.@endif</span></p>
            @endif
            </div>
        </div>

        {{-- ═══ FASILITAS TERMASUK ═══ --}}
        <div class="fd-card fd-includes" data-reveal>
            <div class="fd-section-head">
                <h2 class="fd-section-title"><i class="bi bi-patch-check"></i>Fasilitas Termasuk</h2>
            </div>
            <ul class="fd-chips">
                @foreach ($bawaanService->untuk($fasilitas, $satuan) as $d)
                    <li><i class="bi bi-check-circle-fill"></i>{{ $d }}</li>
                @endforeach
            </ul>
            @if ($semuaRuangan->contains('kategori_fasilitas', 'Working Space') && $semuaTarif->contains(fn ($t) => $t->jenisSewa->satuan->value === 'Bulan'))
                <p class="fd-note"><i class="bi bi-info-circle me-1"></i>Untuk sewa bulanan, Working Space diserahkan dalam keadaan kosong (tanpa meja dan kursi).</p>
            @endif
        </div>
    </div>

    @include('reservasi.partials.atur-jadwal-modal')

    <div class="fp-lightbox" id="fpLightbox">
        <button type="button" class="fp-lightbox-close" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
        <img src="" alt="" id="fpLightboxImg">
    </div>

    <script>
        (function () {
            // ── Tanggal ketersediaan: sekali pilih langsung memuat ulang kondisi ──
            const tgl = document.getElementById('fdTanggal');
            // Tanggal lampau / Sabtu–Minggu ditolak dengan pesan dan tidak memuat ulang halaman.
            if (tgl) {
                const awal = tgl.value;
                const akhirPekan = (v) => { const [y, m, d] = v.split('-').map(Number); const h = new Date(Date.UTC(y, m - 1, d)).getUTCDay(); return h === 0 || h === 6; };
                tgl.addEventListener('change', () => {
                    if (!tgl.value) return;
                    const pesan = tgl.value < tgl.min
                        ? 'Tanggal yang sudah lewat tidak dapat dipilih. Pilih hari ini atau tanggal sesudahnya.'
                        : (akhirPekan(tgl.value) ? 'Gedung tidak beroperasi pada hari Sabtu dan Minggu. Silakan pilih hari kerja (Senin–Jumat).' : '');
                    if (pesan) {
                        tgl.value = awal;
                        Swal.fire({ icon: 'warning', title: 'Tanggal Tidak Dapat Dipilih', text: pesan, confirmButtonColor: '#176b87', confirmButtonText: 'Mengerti' });
                        return;
                    }
                    document.getElementById('fdTanggalForm').submit();
                });
            }

            // ── Galeri per ruangan: foto utama berasio tetap, thumbnail & panah mengganti sumbernya ──
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

            // ── Lightbox ──
            const lb = document.getElementById('fpLightbox');
            const lbImg = document.getElementById('fpLightboxImg');
            const bukaLb = (src, alt) => { lbImg.src = src; lbImg.alt = alt || ''; lb.classList.add('show'); document.body.style.overflow = 'hidden'; };
            const tutupLb = () => { lb.classList.remove('show'); document.body.style.overflow = ''; };
            document.querySelectorAll('.fp-zoomable').forEach((el) => el.addEventListener('click', () => bukaLb(el.dataset.zoomSrc, el.alt)));
            lb.addEventListener('click', (e) => { if (e.target !== lbImg) tutupLb(); });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && lb.classList.contains('show')) tutupLb(); });

            // ── Tombol "Isi Jadwal" — membuka modal Atur Jadwal Sewa ──
            // Didelegasikan: tombol di kartu harga bisa diganti saat ketersediaan diperbarui realtime.
            document.addEventListener('click', (e) => {
                if (!e.target.closest('[data-fd-buka]')) return;
                e.preventDefault();
                window.ajBukaModal?.();
            });
            @if (($editItem ?? null) || $errors->any() || request()->boolean('buka'))
                {{-- Dari "Ubah" di Keranjang, setelah validasi gagal, atau baru berganti jenis sewa. --}}
                window.ajBukaModal?.();
            @endif
        })();
    </script>
@endsection
