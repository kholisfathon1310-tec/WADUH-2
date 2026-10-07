@extends('layouts.customer')
@section('pantau_status', '1')
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
{{-- Wilayah live: isi dashboard diperbarui realtime — lihat partials/pantau-status. --}}
<div data-live="dashboard">
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

        .db-hero h1 { font-size:1.85rem; font-weight:800; color:#fff; margin:0 0 .5rem;
            letter-spacing:-.02em; line-height:1.2; }
        .db-hero .lead { font-size:.92rem; color:#a7f3d0; max-width:38rem; line-height:1.6; margin:0; }

        .db-hero-actions { display:flex; flex-direction:column; gap:.6rem; min-width:18rem; }
        @media (max-width: 991.98px) { .db-hero-actions { min-width:0; } }
        .db-hero-btn { display:inline-flex; align-items:center; gap:.55rem; padding:.85rem 1.25rem;
            font-size:.88rem; font-weight:800; border-radius:.9rem; text-decoration:none;
            transition:transform .18s ease, box-shadow .18s ease, background .18s ease;
            justify-content:flex-start; }
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

        /* ─── LAYAR KECIL ─── */
        @media (max-width: 575.98px) {
            .db-hero { padding:1.5rem 1.25rem; border-radius:1.15rem; margin-bottom:1.25rem; }
            .db-hero h1 { font-size:1.35rem; }
            .db-hero .lead { font-size:.85rem; }
            .db-hero-inner { gap:1.25rem; }
            .db-stats { gap:.7rem; margin-bottom:1.25rem; grid-template-columns:repeat(2, minmax(0, 1fr)); }
            .db-stat { flex-direction:column; align-items:flex-start; }
            .db-stat .ic { width:2.6rem; height:2.6rem; font-size:1rem; }
            .db-stat .sub { display:none; }
            .db-populer-card .info { flex-direction:column; align-items:flex-start; gap:.2rem; }
            .db-populer-card .info > span:nth-child(2) { display:none; }
            .db-populer-card .info > span { white-space:nowrap; }
            .db-stat { padding:1rem 1.1rem; gap:.85rem; }
            .db-stat .v { font-size:1.55rem; }
            .db-card-head { padding:1rem 1.1rem .9rem; }
            .db-recent-item { padding:.85rem 1.1rem; gap:.75rem; }
            .db-recent-item .thumb { width:3rem; height:3rem; }
            .db-recent-item .chip { font-size:.64rem; padding:.25rem .55rem; }
            .db-populer-grid { padding:.9rem 1.1rem 1.15rem; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.7rem; }
            .db-bottom-grid { gap:1.15rem; }
        }
    </style>

    {{-- ══════════════ HERO ══════════════ --}}
    <div class="db-hero" data-reveal>
        <div class="db-hero-inner">
            <div>
                <h1>Selamat Datang, {{ $pemesan->nama_lengkap }}</h1>
                <p class="lead">Kelola reservasi fasilitas dan pantau jadwal pemakaian Anda di Gedung BITC Cimahi.</p>
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
</div>
@endsection
