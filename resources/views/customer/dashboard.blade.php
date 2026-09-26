@extends('layouts.customer')
@section('title', 'Dashboard')

@php
    use App\Enums\StatusReservasi;
    use Illuminate\Support\Carbon;

    $chipClass = [
        'Menunggu' => 'menunggu', 'Disetujui' => 'disetujui', 'Ditolak' => 'ditolak',
        'Selesai' => 'selesai', 'Dibatalkan' => 'dibatalkan', 'Kadaluwarsa' => 'kadaluwarsa',
    ];
    $chipLabel = [
        'Menunggu' => 'Menunggu', 'Disetujui' => 'Disetujui', 'Ditolak' => 'Ditolak',
        'Selesai' => 'Selesai', 'Dibatalkan' => 'Dibatalkan', 'Kadaluwarsa' => 'Kadaluwarsa',
    ];

    $jml = fn ($key) => $jumlahPerStatus[$key] ?? 0;
    $totalMenunggu  = $jml(StatusReservasi::Menunggu->value);
    $totalDisetujui = $jml(StatusReservasi::Disetujui->value);
    $totalSelesai   = $jml(StatusReservasi::Selesai->value);
    // "Aktif" HANYA yang sudah Disetujui (benar-benar berjalan/siap dipakai) — reservasi yang
    // masih Menunggu verifikasi BUKAN "aktif", itu kenapa ada kartu terpisah untuk itu. Kalau
    // ikut dihitung di sini, kartu ini berubah begitu ada pengajuan baru padahal belum
    // disetujui sama sekali, membingungkan pemesan.
    $totalAktif     = $totalDisetujui;

    // ══════════════ KALENDER (lihat partials.kalender-reservasi) ══════════════
    $rangeKalender = \App\Support\KalenderReservasi::range(request('view'), request('tanggal'), request('bulan'));
    ['view' => $view, 'tanggalAcuan' => $tanggalAcuan, 'rentangAwal' => $rentangAwal, 'rentangAkhir' => $rentangAkhir,
     'labelHeader' => $labelHeader, 'navPrev' => $navPrev, 'navNext' => $navNext, 'paramNav' => $paramNav] = $rangeKalender;

    $reservasiKalender = $pemesan->reservasi()
        ->with('tarifSewa.fasilitas.lantai', 'tarifSewa.jenisSewa')
        ->where('status_reservasi', StatusReservasi::Disetujui->value)
        ->whereBetween('tanggal_mulai', [$rentangAwal->toDateString(), $rentangAkhir->toDateString()])
        ->orderBy('tanggal_mulai')
        ->orderBy('jam_mulai')
        ->get();

    // ── FASILITAS POPULER — diurutkan berdasarkan jumlah reservasi yang benar-benar
    //    terkonfirmasi (Disetujui/Selesai), bukan sekadar 4 baris pertama. ──
    $fasilitasPopuler = \App\Models\Fasilitas::where('status_aktif', \App\Enums\StatusAktif::Aktif->value)
        ->withCount(['reservasi as jumlah_dipesan' => function ($q) {
            $q->whereIn('status_reservasi', [
                \App\Enums\StatusReservasi::Disetujui->value,
                \App\Enums\StatusReservasi::Selesai->value,
            ]);
        }])
        ->with(['lantai', 'tarifSewa.jenisSewa'])
        ->orderByDesc('jumlah_dipesan')
        ->orderBy('nama_fasilitas')
        ->limit(4)
        ->get();

    $terbaru3 = $terbaru->take(3);
@endphp

@section('content')
    <style>
        /* ══════════════════════════════════════════════════════════
           DASHBOARD PEMESAN — Modern, Professional, Clean
           ══════════════════════════════════════════════════════════ */

        /* ─── HERO ─── */
        .db-hero { position:relative; overflow:hidden; border-radius:1.5rem;
            background:linear-gradient(120deg, #0c3648 0%, #0f526b 40%, #176b87 100%);
            color:#fff; padding:2.25rem 2.5rem; margin-bottom:1.75rem;
            box-shadow:0 24px 48px -20px rgba(15,80,73,.55); }
        .db-hero::before { content:''; position:absolute; inset:0;
            background:
                radial-gradient(30rem 18rem at 92% -30%, rgba(20,184,166,.38), transparent 60%),
                radial-gradient(20rem 14rem at -8% 110%, rgba(255,255,255,.12), transparent 55%);
            pointer-events:none; }
        .db-hero::after { content:''; position:absolute; inset:0;
            background-image:radial-gradient(rgba(255,255,255,.14) 1.5px, transparent 1.5px);
            background-size:26px 26px; opacity:.32; pointer-events:none; }
        .db-hero > * { position:relative; z-index:1; }

        .db-hero-inner { display:grid; grid-template-columns:1fr auto; gap:2rem; align-items:center; }
        @media (max-width: 991.98px) { .db-hero-inner { grid-template-columns:1fr; } }

        .db-hero-chips { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1rem; }
        .db-hero-chip { display:inline-flex; align-items:center; gap:.4rem;
            font-size:.68rem; font-weight:800; letter-spacing:.06em; padding:.4rem .8rem;
            border-radius:9999px; background:rgba(255,255,255,.12); color:#dcfce7;
            border:1px solid rgba(255,255,255,.2); }
        .db-hero-chip .dot { width:.45rem; height:.45rem; border-radius:50%; background:#34d399;
            box-shadow:0 0 0 3px rgba(52,211,153,.25); }
        .db-hero h1 { font-size:1.85rem; font-weight:800; color:#fff; margin:0 0 .5rem;
            letter-spacing:-.02em; line-height:1.2; }
        .db-hero .lead { font-size:.92rem; color:#a7f3d0; max-width:38rem; line-height:1.6; margin:0; }

        .db-hero-actions { display:flex; flex-direction:column; gap:.6rem; min-width:18rem; }
        @media (max-width: 991.98px) { .db-hero-actions { min-width:0; } }
        .db-hero-btn { display:inline-flex; align-items:center; gap:.55rem; padding:.85rem 1.25rem;
            font-size:.88rem; font-weight:800; border-radius:.9rem; text-decoration:none;
            transition:transform .18s ease, box-shadow .18s ease, background .18s ease;
            justify-content:center; }
        .db-hero-btn.primary { background:#fff; color:var(--primary-darker);
            box-shadow:0 8px 20px -6px rgba(0,0,0,.25); }
        .db-hero-btn.primary:hover { transform:translateY(-2px); color:var(--primary-darker);
            box-shadow:0 14px 28px -6px rgba(0,0,0,.32); }
        .db-hero-btn.ghost { background:rgba(255,255,255,.1); color:#fff;
            border:1px solid rgba(255,255,255,.22); backdrop-filter:blur(6px); }
        .db-hero-btn.ghost:hover { background:rgba(255,255,255,.2); color:#fff; transform:translateY(-2px); }
        .db-hero-btn i.arrow { margin-left:auto; transition:transform .2s ease; }
        .db-hero-btn:hover i.arrow { transform:translateX(3px); }

        /* ─── STAT CARDS ─── */
        .db-stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(14rem, 1fr));
            gap:1.15rem; margin-bottom:1.75rem; }
        .db-stat { padding:1.25rem 1.35rem; border-radius:1.25rem; background:#fff;
            border:1px solid var(--line);
            box-shadow:0 2px 6px rgba(15,23,42,.03), 0 12px 28px -18px rgba(15,23,42,.08);
            display:flex; align-items:center; gap:1.1rem;
            transition:transform .22s ease, box-shadow .22s ease, border-color .22s ease; }
        .db-stat:hover { transform:translateY(-4px);
            box-shadow:0 4px 8px rgba(15,23,42,.04), 0 20px 40px -18px rgba(15,23,42,.15);
            border-color:var(--primary-softer); }
        .db-stat .ic { display:grid; place-items:center; width:3.25rem; height:3.25rem; border-radius:.95rem;
            font-size:1.25rem; flex:none; border:1px solid; }
        .db-stat .body { min-width:0; flex:1; }
        .db-stat .v { font-size:1.85rem; font-weight:800; color:var(--ink); line-height:1;
            letter-spacing:-.03em; }
        .db-stat .k { font-size:.85rem; color:var(--ink); font-weight:700; margin-top:.2rem; }
        .db-stat .sub { font-size:.7rem; color:var(--muted); font-weight:600; margin-top:.15rem; }
        .db-stat.aktif .ic    { background:var(--primary-soft); color:var(--primary-dark); border-color:var(--primary-softer); }
        .db-stat.menunggu .ic { background:#fef3c7; color:#a16207; border-color:#fde68a; }
        .db-stat.disetujui .ic { background:#d1fae5; color:#047857; border-color:#a7f3d0; }
        .db-stat.selesai .ic  { background:#dbeafe; color:#1d4ed8; border-color:#bfdbfe; }

        /* ─── CARD HEAD (shared) ─── */
        .db-card-head { display:flex; align-items:center; justify-content:space-between;
            padding:1.25rem 1.5rem 1.05rem; border-bottom:1px solid var(--line-soft);
            flex-wrap:wrap; gap:.5rem; }
        .db-card-head h3 { font-size:1rem; font-weight:800; margin:0; color:var(--ink);
            display:flex; align-items:center; gap:.55rem; letter-spacing:-.01em; }
        .db-card-head h3 i { color:var(--primary); font-size:.95em; }
        .db-card-head .lihat-semua { font-size:.75rem; font-weight:800; color:var(--primary-dark);
            text-decoration:none; display:inline-flex; align-items:center; gap:.25rem;
            padding:.35rem .55rem; border-radius:.5rem; transition:background .15s ease; }
        .db-card-head .lihat-semua:hover { color:var(--primary-darker); background:var(--primary-soft); }

        /* ─── BOTTOM ROW ─── */
        .db-bottom-grid { display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; }
        @media (max-width: 991.98px) { .db-bottom-grid { grid-template-columns:1fr; } }

        .db-recent-item { display:flex; align-items:center; gap:.95rem;
            padding:1rem 1.5rem; border-bottom:1px solid var(--line-soft); text-decoration:none;
            color:inherit; transition:background .15s ease; }
        .db-recent-item:last-child { border-bottom:0; }
        .db-recent-item:hover { background:var(--surface); color:inherit; }
        .db-recent-item .thumb { width:3.5rem; height:3.5rem; border-radius:.8rem;
            object-fit:cover; flex:none; border:1px solid var(--line); background:var(--surface); }
        .db-recent-item .body { flex:1; min-width:0; }
        .db-recent-item .nm { font-size:.88rem; font-weight:800; color:var(--ink);
            overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .db-recent-item .meta { font-size:.72rem; color:var(--muted); font-weight:600;
            margin-top:.25rem; display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
        .db-recent-item .meta i { color:var(--soft); font-size:.85em; }

        .db-populer-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(11rem, 1fr));
            gap:.9rem; padding:1rem 1.5rem 1.35rem; }
        .db-populer-card { border:1px solid var(--line); border-radius:.95rem; overflow:hidden;
            background:#fff; text-decoration:none; color:inherit;
            transition:all .2s ease; }
        .db-populer-card:hover { transform:translateY(-3px);
            box-shadow:0 14px 26px -10px rgba(15,60,73,.18);
            border-color:var(--primary-softer); color:inherit; }
        .db-populer-card .img-wrap { position:relative; aspect-ratio:16/10; overflow:hidden; background:var(--surface); }
        .db-populer-card .img-wrap img { width:100%; height:100%; object-fit:cover; display:block;
            transition:transform .35s ease; }
        .db-populer-card:hover .img-wrap img { transform:scale(1.05); }
        .db-populer-card .kat-badge { position:absolute; top:.55rem; left:.55rem;
            display:inline-flex; align-items:center; gap:.25rem; font-size:.58rem; font-weight:800;
            background:rgba(255,255,255,.96); color:var(--primary-dark);
            padding:.25rem .55rem; border-radius:9999px; backdrop-filter:blur(4px);
            box-shadow:0 2px 6px rgba(0,0,0,.12); letter-spacing:.04em; text-transform:uppercase; }
        .db-populer-card .body { padding:.8rem .9rem .9rem; }
        .db-populer-card .nm { font-size:.85rem; font-weight:800; color:var(--ink);
            margin-bottom:.25rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .db-populer-card .info { display:flex; align-items:center; gap:.5rem; font-size:.7rem;
            color:var(--muted); font-weight:600; }
        .db-populer-card .info i { color:var(--primary); font-size:.85em; }

        /* ─── OKUPANSI FASILITAS (bar chart + peringkat top-5) ─── */
        .db-okup-grid { display:grid; grid-template-columns:minmax(0, 1.5fr) minmax(0, 1fr); gap:1.5rem; margin-bottom:1.75rem; align-items:stretch; }
        @media (max-width: 991.98px) { .db-okup-grid { grid-template-columns:1fr; } }
        .db-okup-card { display:flex; flex-direction:column; }
        .db-card-head-trend { flex-wrap:wrap; gap:.6rem; }
        .db-card-head .db-okup-info { font-size:.85em; color:var(--soft); cursor:help; margin-left:.15rem; }

        .db-okup-toggle { display:inline-flex; padding:.2rem; border-radius:2rem; background:var(--line-soft); gap:.15rem; }
        .db-okup-toggle-opt { padding:.32rem .85rem; border-radius:1.6rem; font-size:.76rem; font-weight:700;
            text-decoration:none; color:var(--muted); transition:all .15s ease; white-space:nowrap; }
        .db-okup-toggle-opt:hover { color:var(--primary); }
        .db-okup-toggle-opt.active { background:var(--primary); color:#fff; box-shadow:0 6px 16px -6px rgba(23,107,135,.4); }

        .db-okup-chart { padding:.5rem 0 .3rem; }
        .db-okup-svg { width:100%; max-width:560px; height:auto; aspect-ratio:100 / 72; display:block; margin:0 auto; }
        .db-okup-bar { transition:filter .2s ease; transform-box:fill-box; transform-origin:50% 100%;
            animation:dbOkupGrow .65s cubic-bezier(.2,.8,.3,1) both; animation-delay:calc(var(--i, 0) * 70ms); }
        .db-okup-bar:hover { filter:brightness(1.12); cursor:pointer; }
        @keyframes dbOkupGrow { from { transform:scaleY(0); opacity:.35; } to { transform:scaleY(1); opacity:1; } }
        @media (prefers-reduced-motion: reduce) { .db-okup-bar { animation:none; } }
        .db-okup-foot { display:flex; gap:1.5rem; justify-content:center; margin-top:.8rem; font-size:.78rem; color:var(--muted); font-weight:600; }
        .db-okup-foot .dot { display:inline-block; width:.65rem; height:.65rem; border-radius:.25rem; margin-right:.4rem; vertical-align:middle; }

        .db-okup-pill { background:var(--primary-soft); color:var(--primary-dark); font-size:.72rem; font-weight:700;
            padding:.35rem .75rem; border-radius:2rem; flex:none; }

        .db-okup-rank { display:flex; align-items:center; gap:.9rem; padding:.7rem 0; }
        .db-okup-rank + .db-okup-rank { border-top:1px solid var(--line-soft); padding-top:1rem; }
        .db-okup-rank-num { display:grid; place-items:center; flex:none; width:1.9rem; height:1.9rem; border-radius:50%;
            font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:.85rem; }
        .db-okup-rank-body { flex:1; min-width:0; }
        .db-okup-rank-top { display:flex; justify-content:space-between; align-items:baseline; gap:.5rem; margin-bottom:.4rem; }
        .db-okup-rank-name { font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; color:var(--ink); font-size:.87rem;
            overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .db-okup-rank-pct { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:.95rem; flex:none; }
        .db-okup-rank-track { height:.5rem; border-radius:1rem; background:var(--line-soft); overflow:hidden; }
        .db-okup-rank-fill { height:100%; border-radius:1rem; transition:width .6s cubic-bezier(.2,.7,.3,1); }

        /* Tooltip mengambang, mengikuti kursor — dipakai batang grafik okupansi */
        .db-okup-tip { position:fixed; z-index:2000; pointer-events:none; background:#0f172a; color:#fff;
            border-radius:.75rem; padding:.6rem .85rem; font-size:.78rem; box-shadow:0 14px 30px -10px rgba(0,0,0,.45);
            opacity:0; transform:translateY(4px); transition:opacity .12s ease, transform .12s ease; top:0; left:0; }
        .db-okup-tip.show { opacity:1; transform:none; }
        .db-okup-tip-label { display:flex; align-items:center; gap:.45rem; font-weight:700; margin-bottom:.15rem; }
        .db-okup-tip-label .dot { width:.55rem; height:.55rem; border-radius:50%; flex:none; box-shadow:0 0 0 3px rgba(255,255,255,.08); }
        .db-okup-tip-value { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:.95rem; }
    </style>

    {{-- ══════════════ HERO ══════════════ --}}
    <div class="db-hero" data-reveal>
        <div class="db-hero-chips">
            <span class="db-hero-chip"><span class="dot"></span>Tenant Aktif · Gedung BITC</span>
            <span class="db-hero-chip">ID: BITC-P-{{ str_pad($pemesan->id_pemesan, 5, '0', STR_PAD_LEFT) }}</span>
        </div>
        <div class="db-hero-inner">
            <div>
                <h1>Selamat Datang, {{ $pemesan->nama_lengkap }}</h1>
                <p class="lead">Kelola reservasi ruangan dan jadwal kerja Anda dengan mudah di WADUH BITC Cimahi.</p>
            </div>
            <div class="db-hero-actions">
                <a href="{{ route('reservasi.index') }}" class="db-hero-btn primary">
                    <i class="bi bi-plus-lg"></i>
                    <span>Buat Reservasi</span>
                    <i class="bi bi-arrow-right arrow"></i>
                </a>
                <a href="{{ route('customer.reservasi-saya.index') }}" class="db-hero-btn ghost">
                    <i class="bi bi-journal-text"></i>
                    <span>Reservasi Saya</span>
                </a>
            </div>
        </div>
    </div>

    {{-- ══════════════ STATS ══════════════ --}}
    <div class="db-stats" data-reveal>
        <div class="db-stat aktif">
            <span class="ic"><i class="bi bi-calendar-check-fill"></i></span>
            <div class="body">
                <div class="v">{{ $totalAktif }}</div>
                <div class="k">Reservasi Aktif</div>
                <div class="sub">Sudah disetujui &amp; berjalan</div>
            </div>
        </div>
        <div class="db-stat menunggu">
            <span class="ic"><i class="bi bi-hourglass-split"></i></span>
            <div class="body">
                <div class="v">{{ $totalMenunggu }}</div>
                <div class="k">Menunggu Verifikasi</div>
                <div class="sub">Dalam proses validasi</div>
            </div>
        </div>
        <div class="db-stat disetujui">
            <span class="ic"><i class="bi bi-check-circle-fill"></i></span>
            <div class="body">
                <div class="v">{{ $totalDisetujui }}</div>
                <div class="k">Disetujui</div>
                <div class="sub">Siap digunakan</div>
            </div>
        </div>
        <div class="db-stat selesai">
            <span class="ic"><i class="bi bi-flag-fill"></i></span>
            <div class="body">
                <div class="v">{{ $totalSelesai }}</div>
                <div class="k">Selesai</div>
                <div class="sub">Riwayat pemakaian</div>
            </div>
        </div>
    </div>

    {{-- ══════════════ KALENDER RESERVASI (partial bersama Admin & Pemesan) ══════════════ --}}
    @include('partials.kalender-reservasi', [
        'view' => $view, 'tanggalAcuan' => $tanggalAcuan, 'rentangAwal' => $rentangAwal, 'rentangAkhir' => $rentangAkhir,
        'labelHeader' => $labelHeader, 'navPrev' => $navPrev, 'navNext' => $navNext, 'paramNav' => $paramNav,
        'reservasiKalender' => $reservasiKalender,
        'routeDashboard' => 'customer.dashboard',
        'routeDetail' => 'customer.reservasi-saya.show',
    ])

    {{-- ══════════════ BOTTOM: RESERVASI TERBARU + FASILITAS POPULER ══════════════ --}}
    <div class="db-bottom-grid">
        <div class="xcard" data-reveal>
            <div class="db-card-head">
                <h3><i class="bi bi-clock-history"></i>Reservasi Terbaru</h3>
                <a href="{{ route('customer.reservasi-saya.index') }}" class="lihat-semua">
                    Lihat Semua <i class="bi bi-chevron-right"></i>
                </a>
            </div>
            @if ($terbaru3->isEmpty())
                <div class="p-4 text-center">
                    <div class="icon-tile mx-auto mb-2"><i class="bi bi-inbox"></i></div>
                    <p class="text-muted small mb-0">Belum ada reservasi.</p>
                </div>
            @else
                @foreach ($terbaru3 as $r)
                    @php
                        $fs = $r->tarifSewa->fasilitas;
                        $statusVal = $r->status_reservasi->value;
                        $fotoRes = $fs->fotoUrls()[0] ?? asset('images/lt1.png');
                        $satuan = $r->tarifSewa->jenisSewa->satuan->value ?? '';
                    @endphp
                    <a href="{{ route('customer.reservasi-saya.show', $r->kode_reservasi) }}" class="db-recent-item">
                        <img src="{{ $fotoRes }}" alt="{{ $fs->nama_fasilitas }}" class="thumb">
                        <div class="body">
                            <div class="nm">{{ $fs->nama_fasilitas }}</div>
                            <div class="meta">
                                <span><i class="bi bi-calendar3"></i>{{ $r->tanggal_mulai->translatedFormat('d M Y') }}</span>
                                @if ($r->jam_mulai)
                                    <span>·</span><span><i class="bi bi-clock"></i>{{ substr($r->jam_mulai, 0, 5) }}</span>
                                @else
                                    <span>·</span><span><i class="bi bi-tag"></i>Per {{ $satuan }}</span>
                                @endif
                            </div>
                        </div>
                        <span class="chip {{ $chipClass[$statusVal] ?? '' }}">{{ $chipLabel[$statusVal] ?? $statusVal }}</span>
                    </a>
                @endforeach
            @endif
        </div>

        <div class="xcard" data-reveal>
            <div class="db-card-head">
                <h3><i class="bi bi-star-fill" style="color:#f59e0b !important;"></i>Fasilitas Populer</h3>
                <a href="{{ route('reservasi.index') }}" class="lihat-semua">
                    Lihat Semua <i class="bi bi-chevron-right"></i>
                </a>
            </div>
            <div class="db-populer-grid">
                @foreach ($fasilitasPopuler as $fp)
                    @php
                        $fotoP = $fp->fotoUrls()[0] ?? asset('images/lt1.png');
                        $satuanList = $fp->tarifSewa->pluck('jenisSewa.satuan.value')->filter()->unique();
                        $satuanStr = $satuanList->map(fn ($s) => $s === 'Bulan' ? 'Bul' : $s)->implode('/');
                    @endphp
                    <a href="{{ route('reservasi.fasilitas.show', $fp) }}" class="db-populer-card">
                        <div class="img-wrap">
                            <img src="{{ $fotoP }}" alt="{{ $fp->nama_fasilitas }}">
                            <span class="kat-badge">{{ $fp->kategori_fasilitas }}</span>
                        </div>
                        <div class="body">
                            <div class="nm">{{ $fp->nama_fasilitas }}</div>
                            <div class="info">
                                <span><i class="bi bi-people-fill"></i>{{ $fp->kapasitas }} orang</span>
                                <span>·</span>
                                <span><i class="bi bi-tag-fill"></i>Per {{ $satuanStr ?: 'Jam' }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
    {{-- ══════════════ OKUPANSI FASILITAS — tingkat pemakaian ruangan aktif ══════════════ --}}
    @php
        $okupansiPeriode = in_array(request('okupansi'), ['harian', 'bulanan'], true) ? request('okupansi') : 'harian';
        $okupansiChart = $okupansiPeriode === 'bulanan' ? $okupansiBulanan : $okupansiHarian;
        $okBarCount = count($okupansiChart);
        $okBarW = $okBarCount > 7 ? 5 : 8;
        $okGap = (100 - $okBarW * $okBarCount) / max(1, $okBarCount - 1);
        // Warna peringkat top-5: gradasi dari merah (rank 1) → primer (rank 5), supaya rank
        // teratas paling menonjol — bukan berdasarkan nilai pct itu sendiri.
        $rankColors = ['#ef4444', '#f59e0b', '#eab308', '#14b8a6', 'var(--primary)'];
    @endphp
    <div class="db-okup-grid" data-reveal>
        <div class="xcard db-okup-card">
            <div class="db-card-head db-card-head-trend">
                <h3><i class="bi bi-bar-chart-fill"></i>Tingkat Okupansi Fasilitas
                    <i class="bi bi-info-circle-fill db-okup-info" tabindex="0" data-tip="Persentase ruangan aktif yang punya reservasi Disetujui/Selesai pada tanggal tersebut."></i>
                </h3>
                <div class="db-okup-toggle" role="group" aria-label="Pilih periode okupansi">
                    <a href="{{ route('customer.dashboard', array_merge(request()->except('okupansi'), ['okupansi' => 'harian'])) }}"
                       class="db-okup-toggle-opt {{ $okupansiPeriode === 'harian' ? 'active' : '' }}">Per Hari</a>
                    <a href="{{ route('customer.dashboard', array_merge(request()->except('okupansi'), ['okupansi' => 'bulanan'])) }}"
                       class="db-okup-toggle-opt {{ $okupansiPeriode === 'bulanan' ? 'active' : '' }}">Per Bulan</a>
                </div>
            </div>
            <div class="db-card-body">
                <div class="db-okup-chart">
                    <svg viewBox="0 0 100 72" class="db-okup-svg" role="img" aria-label="Grafik tingkat okupansi fasilitas">
                        <defs>
                            <linearGradient id="okupToday" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="var(--teal)"/>
                                <stop offset="100%" stop-color="var(--primary)"/>
                            </linearGradient>
                            <linearGradient id="okupSoft" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#cbe8ec"/>
                                <stop offset="100%" stop-color="var(--primary-softer)"/>
                            </linearGradient>
                        </defs>
                        @for ($g = 1; $g <= 3; $g++)
                            <line x1="0" y1="{{ $g * 16 }}" x2="100" y2="{{ $g * 16 }}" stroke="#eef2f6" stroke-width=".25"/>
                        @endfor
                        @foreach ($okupansiChart as $i => $d)
                            @php
                                $h = $d['pct'] > 0 ? max(3, round($d['pct'] / 100 * 58)) : 1.5;
                                $x = $i * ($okBarW + $okGap);
                                $y = 64 - $h;
                                $tipLabel = $okupansiPeriode === 'bulanan' ? $d['tanggal']->translatedFormat('F Y') : $d['tanggal']->translatedFormat('l, d F');
                            @endphp
                            <rect x="{{ $x }}" y="{{ $y }}" width="{{ $okBarW }}" height="{{ $h }}"
                                  rx="1.8" ry="1.8"
                                  fill="{{ $d['isAktif'] ? 'url(#okupToday)' : 'url(#okupSoft)' }}"
                                  class="db-okup-bar" style="--i:{{ $i }}"
                                  data-tip-label="{{ $tipLabel }}"
                                  data-tip-value="Okupansi: {{ $d['pct'] }}%"
                                  data-tip-color="{{ $d['isAktif'] ? 'var(--primary)' : 'var(--primary-softer)' }}"></rect>
                            @if ($okBarCount <= 7)
                                <text x="{{ $x + $okBarW / 2 }}" y="{{ $y - 1.8 }}"
                                      text-anchor="middle" font-size="3.4" font-weight="700"
                                      fill="{{ $d['isAktif'] ? 'var(--primary)' : '#64748b' }}"
                                      font-family="'Plus Jakarta Sans',sans-serif">{{ $d['pct'] }}%</text>
                            @endif
                            <text x="{{ $x + $okBarW / 2 }}" y="70.5"
                                  text-anchor="middle" font-size="{{ $okBarCount > 7 ? 2.6 : 2.9 }}" font-weight="600"
                                  fill="{{ $d['isAktif'] ? 'var(--primary)' : '#94a3b8' }}"
                                  font-family="'DM Sans',sans-serif">{{ $d['label'] }}</text>
                        @endforeach
                    </svg>
                </div>
                <div class="db-okup-foot">
                    <span><span class="dot" style="background:var(--primary)"></span>{{ $okupansiPeriode === 'bulanan' ? 'Bulan ini' : 'Hari ini' }}</span>
                    <span><span class="dot" style="background:var(--primary-softer)"></span>{{ $okupansiPeriode === 'bulanan' ? '11 bulan sebelumnya' : '6 hari sebelumnya' }}</span>
                </div>
            </div>
        </div>

        <div class="xcard db-okup-card">
            <div class="db-card-head">
                <h3><i class="bi bi-bookmark-star-fill"></i>Fasilitas Okupansi Tertinggi</h3>
                <span class="db-okup-pill">{{ $totalRuanganAktif }} ruangan aktif</span>
            </div>
            <div class="db-card-body">
                @forelse ($topOkupansiFasilitas as $i => $f)
                    @php $warna = $rankColors[$i] ?? 'var(--primary)'; @endphp
                    <div class="db-okup-rank">
                        <span class="db-okup-rank-num" style="background:{{ $warna }}1a; color:{{ $warna }};">{{ $i + 1 }}</span>
                        <div class="db-okup-rank-body">
                            <div class="db-okup-rank-top">
                                <span class="db-okup-rank-name">{{ $f['nama'] }}</span>
                                <span class="db-okup-rank-pct" style="color:{{ $warna }}">{{ $f['pct'] }}%</span>
                            </div>
                            <div class="db-okup-rank-track">
                                <div class="db-okup-rank-fill" style="width:{{ $f['pct'] }}%; background:{{ $warna }}"></div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center">
                        <div class="icon-tile mx-auto mb-2"><i class="bi bi-bar-chart"></i></div>
                        <p class="text-muted small mb-0">Belum ada data okupansi.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Tooltip mengambang — muncul saat kursor di atas batang grafik okupansi. --}}
    <div class="db-okup-tip" id="dbOkupTip" hidden>
        <div class="db-okup-tip-label"><span class="dot" id="dbOkupTipDot"></span><span id="dbOkupTipLabel"></span></div>
        <div class="db-okup-tip-value"><span id="dbOkupTipValue"></span></div>
    </div>

    <script>
        (function () {
            const tip = document.getElementById('dbOkupTip');
            if (!tip) return;
            const elDot = document.getElementById('dbOkupTipDot');
            const elLabel = document.getElementById('dbOkupTipLabel');
            const elValue = document.getElementById('dbOkupTipValue');

            const posisi = (evt) => {
                const pad = 16;
                let x = evt.clientX + pad;
                let y = evt.clientY + pad;
                const rect = tip.getBoundingClientRect();
                if (x + rect.width > window.innerWidth - 8) x = evt.clientX - rect.width - pad;
                if (y + rect.height > window.innerHeight - 8) y = evt.clientY - rect.height - pad;
                tip.style.left = x + 'px';
                tip.style.top = y + 'px';
            };
            const tampilkan = (el, evt) => {
                elDot.style.background = el.dataset.tipColor || 'var(--primary)';
                elLabel.textContent = el.dataset.tipLabel || '';
                elValue.textContent = el.dataset.tipValue || '';
                tip.hidden = false;
                requestAnimationFrame(() => tip.classList.add('show'));
                posisi(evt);
            };
            const sembunyikan = () => { tip.classList.remove('show'); tip.hidden = true; };

            document.querySelectorAll('[data-tip-value]').forEach((el) => {
                el.addEventListener('mouseenter', (evt) => tampilkan(el, evt));
                el.addEventListener('mousemove', posisi);
                el.addEventListener('mouseleave', sembunyikan);
            });
        })();
    </script>
@endsection