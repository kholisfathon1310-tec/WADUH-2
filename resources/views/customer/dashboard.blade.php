@extends('layouts.customer')
@section('title', 'Dashboard Reservasi')

@php
    use App\Enums\StatusReservasi;

    $chipClass = [
        'Menunggu' => 'menunggu', 'Disetujui' => 'disetujui', 'Ditolak' => 'ditolak',
        'Selesai' => 'selesai', 'Dibatalkan' => 'dibatalkan', 'Kadaluwarsa' => 'kadaluwarsa',
    ];

    // Jumlah per status (aman kalau statusnya belum ada — default 0)
    $jml = fn ($key) => $jumlahPerStatus[$key] ?? 0;
    $totalMenunggu = $jml(StatusReservasi::Menunggu->value);
    $totalDisetujui = $jml(StatusReservasi::Disetujui->value);
    $totalSelesai = $jml(StatusReservasi::Selesai->value);

    // Sesi aktif = reservasi disetujui pertama yang jadwalnya hari ini
    $hariIni = now()->toDateString();
    $sesiAktif = $berjalan->first(function ($r) use ($hariIni) {
        return $r->status_reservasi->value === 'Disetujui'
            && $r->tanggal_mulai->toDateString() <= $hariIni
            && $r->tanggal_selesai->toDateString() >= $hariIni;
    });

    // Kalender pekan ini (7 hari mulai Senin)
    $awalPekan = now()->startOfWeek();
    $tanggalPekan = collect(range(0, 6))->map(fn ($i) => $awalPekan->copy()->addDays($i));
    $adaReservasi = $berjalan->groupBy(fn ($r) => $r->tanggal_mulai->toDateString());
@endphp

@section('content')
    <style>
        /* ══════ HERO ══════ */
        .db-hero { position:relative; overflow:hidden; border-radius:1.5rem; padding:2rem 2.25rem;
            background:linear-gradient(120deg, #0e4a44 0%, #0f526b 40%, #176b87 100%);
            color:#fff; box-shadow:0 22px 44px -18px rgba(15,80,73,.5); }
        .db-hero::before { content:''; position:absolute; inset:0;
            background:
                radial-gradient(28rem 16rem at 92% -30%, rgba(36,170,154,.35), transparent 60%),
                radial-gradient(18rem 12rem at -8% 110%, rgba(255,255,255,.12), transparent 55%);
            pointer-events:none; }
        .db-hero::after { content:''; position:absolute; inset:0;
            background-image:radial-gradient(rgba(255,255,255,.14) 1.5px, transparent 1.5px);
            background-size:24px 24px; opacity:.35; pointer-events:none; }
        .db-hero > * { position:relative; z-index:1; }

        .db-hero-chips { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:.9rem; }
        .db-hero-chip { display:inline-flex; align-items:center; gap:.4rem;
            font-size:.68rem; font-weight:800; letter-spacing:.06em; padding:.35rem .75rem;
            border-radius:9999px; background:rgba(255,255,255,.12); color:#dcfce7;
            border:1px solid rgba(255,255,255,.18); }
        .db-hero-chip .dot { width:.42rem; height:.42rem; border-radius:50%; background:#34d399; }

        .db-hero h1 { font-size:1.65rem; font-weight:800; color:#fff; margin:0 0 .35rem; letter-spacing:-.02em; }
        .db-hero .lead { font-size:.85rem; color:#a7f3d0; max-width:38rem; line-height:1.55; margin:0; }

        .db-hero-actions { display:flex; flex-wrap:wrap; gap:.6rem; align-items:flex-start; }
        .db-hero-btn { display:inline-flex; align-items:center; gap:.45rem; padding:.7rem 1.1rem;
            font-size:.82rem; font-weight:800; border-radius:.85rem; text-decoration:none;
            transition:transform .15s ease, box-shadow .15s ease; }
        .db-hero-btn.primary { background:#fff; color:var(--primary-darker);
            box-shadow:0 6px 16px -4px rgba(0,0,0,.2); }
        .db-hero-btn.primary:hover { transform:translateY(-1px); box-shadow:0 10px 22px -4px rgba(0,0,0,.28); color:var(--primary-darker); }
        .db-hero-btn.ghost { background:rgba(255,255,255,.12); color:#fff;
            border:1px solid rgba(255,255,255,.25); backdrop-filter:blur(6px); }
        .db-hero-btn.ghost:hover { background:rgba(255,255,255,.22); color:#fff; transform:translateY(-1px); }

        /* ══════ STAT TILES ══════ */
        .db-stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(14rem, 1fr)); gap:1rem; margin-top:1.5rem; }
        .db-stat { padding:1.15rem 1.25rem; border-radius:1.15rem; background:#fff; border:1px solid var(--line);
            box-shadow:var(--card-shadow); position:relative; overflow:hidden;
            transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
        .db-stat:hover { transform:translateY(-3px); box-shadow:var(--card-shadow-lg); border-color:var(--primary-softer); }
        .db-stat .top { display:flex; align-items:center; justify-content:space-between; gap:.5rem; margin-bottom:.6rem; }
        .db-stat small { font-size:.62rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase; color:var(--soft); }
        .db-stat .ic { display:grid; place-items:center; width:2.2rem; height:2.2rem; border-radius:.7rem;
            background:linear-gradient(135deg, var(--primary-soft), var(--primary-tint));
            color:var(--primary-dark); font-size:.95rem; border:1px solid var(--primary-softer); }
        .db-stat .v { font-size:1.85rem; font-weight:800; color:var(--ink); line-height:1; letter-spacing:-.02em; }
        .db-stat .k { font-size:.75rem; color:var(--muted); font-weight:600; margin-top:.3rem; }
        .db-stat .foot { display:flex; align-items:center; gap:.35rem; margin-top:.85rem; padding-top:.7rem;
            border-top:1px dashed var(--line); font-size:.72rem; font-weight:700; }
        .db-stat .foot i { font-size:.85em; }
        .db-stat.stat-menunggu .ic { background:linear-gradient(135deg,#fef3c7,#fde68a); border-color:#fde68a; color:#a16207; }
        .db-stat.stat-siap .ic { background:linear-gradient(135deg,#d1fae5,#a7f3d0); border-color:#a7f3d0; color:#065f46; }
        .db-stat.stat-selesai .ic { background:linear-gradient(135deg,#dbeafe,#bfdbfe); border-color:#bfdbfe; color:#1d4ed8; }

        /* ══════ SESI AKTIF ══════ */
        .db-sesi { padding:1.35rem 1.5rem; margin-top:1.5rem; border-color:var(--primary-softer);
            background:linear-gradient(135deg, #fff, var(--primary-soft)); }
        .db-sesi .hd { display:flex; align-items:center; gap:.6rem; margin-bottom:.85rem; flex-wrap:wrap; }
        .db-sesi .hd .badge-live { display:inline-flex; align-items:center; gap:.4rem;
            background:linear-gradient(135deg, #059669, #10b981); color:#fff;
            padding:.35rem .75rem; border-radius:9999px; font-size:.65rem; font-weight:800;
            letter-spacing:.08em; text-transform:uppercase;
            box-shadow:0 6px 14px -4px rgba(5,150,105,.45); }
        .db-sesi .hd .badge-live .dot { width:.4rem; height:.4rem; border-radius:50%;
            background:#fff; animation:pulse-live 1.4s infinite; }
        @keyframes pulse-live { 0%,100% { opacity:1; transform:scale(1); } 50% { opacity:.4; transform:scale(1.5); } }
        .db-sesi .hd .kode { font-size:.7rem; font-weight:800; color:var(--muted); letter-spacing:.06em; }

        .db-sesi h2 { font-size:1.2rem; font-weight:800; color:var(--ink); margin:0 0 .3rem; letter-spacing:-.01em; }
        .db-sesi .sub { font-size:.78rem; color:var(--muted); margin-bottom:1rem; }

        .db-sesi-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(11rem, 1fr));
            gap:.85rem 1.5rem; padding:1rem; background:#fff; border-radius:1rem;
            border:1px solid var(--line); }
        .db-sesi-grid .cell { display:flex; align-items:flex-start; gap:.65rem; }
        .db-sesi-grid .cell .ic { display:grid; place-items:center; width:2.2rem; height:2.2rem;
            border-radius:.65rem; background:var(--primary-soft); color:var(--primary-dark);
            font-size:.85rem; flex:none; border:1px solid var(--primary-softer); }
        .db-sesi-grid .cell small { display:block; font-size:.6rem; font-weight:800; letter-spacing:.08em;
            text-transform:uppercase; color:var(--soft); margin-bottom:.15rem; }
        .db-sesi-grid .cell .val { font-size:.78rem; font-weight:700; color:var(--ink); }

        /* ══════ LISTS + KALENDER ══════ */
        .db-two-col { display:grid; grid-template-columns:2fr 1fr; gap:1.25rem; margin-top:1.5rem; align-items:flex-start; }
        @media (max-width: 991.98px) { .db-two-col { grid-template-columns:1fr; } }

        .db-list-head { display:flex; align-items:center; justify-content:space-between; padding:1.25rem 1.5rem .95rem;
            border-bottom:1px solid var(--line-soft); flex-wrap:wrap; gap:.5rem; }
        .db-list-head h3 { font-size:.95rem; font-weight:800; margin:0; color:var(--ink);
            display:flex; align-items:center; gap:.5rem; }
        .db-list-head h3 i { color:var(--primary); }
        .db-list-head .tabs { display:flex; gap:.3rem; }
        .db-list-head .tabs .tab { font-size:.7rem; font-weight:700; padding:.35rem .7rem;
            border-radius:.55rem; background:var(--surface-2); color:var(--muted); border:1px solid var(--line); }
        .db-list-head .tabs .tab.active { background:var(--primary-soft); color:var(--primary-dark);
            border-color:var(--primary-softer); }

        .db-item { display:flex; align-items:center; gap:.85rem; padding:.85rem 1.5rem;
            border-bottom:1px solid var(--line-soft); transition:background .15s ease; text-decoration:none; color:inherit; }
        .db-item:last-child { border-bottom:0; }
        .db-item:hover { background:var(--surface); color:inherit; }
        .db-item .ic-tile { display:grid; place-items:center; width:2.9rem; height:2.9rem; border-radius:.85rem;
            background:linear-gradient(135deg, var(--primary-soft), var(--primary-tint));
            color:var(--primary-dark); font-size:1.1rem; flex:none; border:1px solid var(--primary-softer); }
        .db-item .body { flex:1; min-width:0; }
        .db-item .nm-row { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
        .db-item .nm { font-size:.85rem; font-weight:800; color:var(--ink); }
        .db-item .kode { font-size:.65rem; font-weight:700; color:var(--muted); background:var(--surface); padding:.15rem .45rem; border-radius:.4rem; letter-spacing:.04em; }
        .db-item .meta { font-size:.72rem; color:var(--muted); margin-top:.15rem; display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
        .db-item .meta i { color:var(--soft); font-size:.85em; }
        .db-item .side { text-align:end; flex:none; }
        .db-item .side .price { font-size:.9rem; font-weight:800; color:var(--primary-dark); display:block; line-height:1; }
        .db-item .side .chip { margin-top:.35rem; }

        .db-list-foot { padding:.95rem 1.5rem; border-top:1px solid var(--line-soft);
            display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.5rem; }
        .db-list-foot small { font-size:.72rem; color:var(--muted); font-weight:600; }
        .db-list-foot a { font-size:.75rem; font-weight:800; color:var(--primary-dark); text-decoration:none; }
        .db-list-foot a:hover { color:var(--primary-darker); }

        /* Mini calendar */
        .db-cal { padding:1.25rem 1.35rem; }
        .db-cal .cal-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:.85rem; }
        .db-cal .cal-head h3 { font-size:.85rem; font-weight:800; margin:0; color:var(--ink); }
        .db-cal .cal-head .month { font-size:.72rem; color:var(--muted); font-weight:700; }
        .db-cal-grid { display:grid; grid-template-columns:repeat(7, 1fr); gap:.35rem; }
        .db-cal-day { text-align:center; padding:.55rem .3rem; border-radius:.6rem;
            font-size:.75rem; font-weight:700; color:var(--muted); background:var(--surface);
            border:1px solid transparent; transition:all .15s ease; position:relative; }
        .db-cal-day .dn { display:block; font-size:.55rem; color:var(--soft); font-weight:800;
            letter-spacing:.06em; text-transform:uppercase; margin-bottom:.15rem; }
        .db-cal-day .dv { font-size:.9rem; font-weight:800; color:var(--ink); }
        .db-cal-day.today { background:linear-gradient(135deg, var(--primary-dark), var(--primary));
            color:#fff; box-shadow:0 6px 14px -4px rgba(23,107,135,.45); }
        .db-cal-day.today .dn, .db-cal-day.today .dv { color:#fff; }
        .db-cal-day.has-res { background:var(--primary-soft); border-color:var(--primary-softer); }
        .db-cal-day.has-res .dv { color:var(--primary-dark); }
        .db-cal-day.has-res::after { content:''; position:absolute; bottom:.25rem; left:50%; transform:translateX(-50%);
            width:.35rem; height:.35rem; border-radius:50%; background:var(--primary); }
        .db-cal-note { margin-top:1rem; padding-top:.9rem; border-top:1px dashed var(--line);
            font-size:.72rem; color:var(--muted); display:flex; align-items:center; gap:.4rem; }
        .db-cal-note i { color:var(--primary); }
    </style>

    {{-- ══════════════ HERO ══════════════ --}}
    <div class="db-hero mb-4" data-reveal>
        <div class="db-hero-chips">
            <span class="db-hero-chip"><span class="dot"></span>Pemesan Aktif · Gedung BITC</span>
            <span class="db-hero-chip">ID: BITC-P-{{ str_pad($pemesan->id_pemesan, 5, '0', STR_PAD_LEFT) }}</span>
        </div>
        <div class="row align-items-center g-3">
            <div class="col-lg-8">
                <h1>Halo, {{ explode(' ', $pemesan->nama_lengkap)[0] }} {{ collect(explode(' ', $pemesan->nama_lengkap))->slice(1)->implode(' ') }}</h1>
                <p class="lead">Ringkasan reservasi aktif dan status verifikasi berkas Anda di Gedung BITC Cimahi hari ini.</p>
            </div>
            <div class="col-lg-4 d-flex flex-column gap-2 align-items-lg-end">
                <a href="{{ route('reservasi.index') }}" class="db-hero-btn primary">
                    <i class="bi bi-plus-lg"></i>
                    <span>Reservasi Fasilitas</span>
                </a>
                <a href="{{ route('reservasi.index') }}" class="db-hero-btn ghost">
                    <i class="bi bi-map"></i>
                    <span>Denah Interaktif</span>
                </a>
            </div>
        </div>
    </div>

    {{-- ══════════════ STAT TILES ══════════════ --}}
    <div class="db-stats" data-reveal>
        <div class="db-stat">
            <div class="top">
                <small>Total Reservasi</small>
                <span class="ic"><i class="bi bi-journal-text"></i></span>
            </div>
            <div class="v">{{ $totalReservasi }}</div>
            <div class="k">Total Sesi</div>
            <div class="foot" style="color:var(--primary-dark);">
                <i class="bi bi-arrow-up-right"></i>Riwayat penuh
            </div>
        </div>

        <div class="db-stat stat-menunggu">
            <div class="top">
                <small>Menunggu Review</small>
                <span class="ic"><i class="bi bi-hourglass-split"></i></span>
            </div>
            <div class="v">{{ $totalMenunggu }}</div>
            <div class="k">Berkas Antrean</div>
            <div class="foot" style="color:#a16207;">
                <i class="bi bi-clipboard-check"></i>Menunggu tinjauan admin
            </div>
        </div>

        <div class="db-stat stat-siap">
            <div class="top">
                <small>Siap Digunakan</small>
                <span class="ic"><i class="bi bi-shield-check"></i></span>
            </div>
            <div class="v">{{ $totalDisetujui }}</div>
            <div class="k">Disetujui</div>
            <div class="foot" style="color:#065f46;">
                <i class="bi bi-key"></i>Siap dipakai
            </div>
        </div>

        <div class="db-stat stat-selesai">
            <div class="top">
                <small>Riwayat Selesai</small>
                <span class="ic"><i class="bi bi-check2-square"></i></span>
            </div>
            <div class="v">{{ $totalSelesai }}</div>
            <div class="k">Selesai</div>
            <div class="foot" style="color:#1d4ed8;">
                <i class="bi bi-award"></i>Semua reservasi lunas
            </div>
        </div>
    </div>

    {{-- ══════════════ SESI AKTIF ══════════════ --}}
    @if ($sesiAktif)
        @php $fs = $sesiAktif->tarifSewa->fasilitas; @endphp
        <div class="xcard db-sesi" data-reveal>
            <div class="hd">
                <span class="badge-live"><span class="dot"></span>Sesi Aktif Sekarang</span>
                <span class="kode">{{ $sesiAktif->kode_reservasi }}</span>
            </div>
            <h2>{{ $fs->nama_fasilitas }} · {{ $fs->kategori_fasilitas }}</h2>
            <p class="sub">
                Lantai {{ $fs->lantai->nomor_lantai ?? '-' }} · Gedung BITC Cimahi
            </p>

            <div class="db-sesi-grid">
                <div class="cell">
                    <span class="ic"><i class="bi bi-clock"></i></span>
                    <div>
                        <small>Rentang Waktu</small>
                        <div class="val">
                            @if ($sesiAktif->jam_mulai)
                                {{ substr($sesiAktif->jam_mulai,0,5) }} - {{ substr($sesiAktif->jam_selesai,0,5) }} WIB
                            @else
                                {{ $sesiAktif->tanggal_mulai->translatedFormat('d M Y') }} - {{ $sesiAktif->tanggal_selesai->translatedFormat('d M Y') }}
                            @endif
                        </div>
                    </div>
                </div>
                <div class="cell">
                    <span class="ic"><i class="bi bi-calendar-check"></i></span>
                    <div>
                        <small>Tanggal Pemakaian</small>
                        <div class="val">{{ $sesiAktif->tanggal_mulai->translatedFormat('d M Y') }}</div>
                    </div>
                </div>
                <div class="cell">
                    <span class="ic"><i class="bi bi-tag"></i></span>
                    <div>
                        <small>Jenis Sewa</small>
                        <div class="val">Per {{ $sesiAktif->tarifSewa->jenisSewa->nama_jenis_sewa ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════ LISTS ══════════════ --}}
    <div class="db-two-col">
        <div class="xcard" data-reveal>
            <div class="db-list-head">
                <h3><i class="bi bi-clipboard-data"></i>Daftar Reservasi Berjalan</h3>
                <div class="tabs">
                    <span class="tab active">Semua ({{ $berjalan->count() }})</span>
                    <span class="tab">Menunggu ({{ $totalMenunggu }})</span>
                    <span class="tab">Disetujui ({{ $totalDisetujui }})</span>
                </div>
            </div>

            @if ($berjalan->isEmpty())
                <div class="p-4 text-center">
                    <div class="icon-tile mx-auto mb-2"><i class="bi bi-inbox"></i></div>
                    <p class="text-muted small mb-0">Belum ada reservasi berjalan.</p>
                </div>
            @else
                @foreach ($berjalan as $r)
                    @php
                        $fs = $r->tarifSewa->fasilitas;
                        $meta = \App\Support\KategoriMeta::get($fs->kategori_fasilitas);
                        $statusVal = $r->status_reservasi->value;
                    @endphp
                    <a href="{{ route('customer.reservasi-saya.show', $r->kode_reservasi) }}" class="db-item">
                        <span class="ic-tile"><i class="bi {{ $meta['ikon'] ?? 'bi-door-open' }}"></i></span>
                        <div class="body">
                            <div class="nm-row">
                                <span class="nm">{{ $fs->nama_fasilitas }}</span>
                                <span class="kode">{{ $r->kode_reservasi }}</span>
                            </div>
                            <div class="meta">
                                <span><i class="bi bi-calendar3"></i>{{ $r->tanggal_mulai->translatedFormat('d M Y') }}</span>
                                @if ($r->jam_mulai)
                                    <span>·</span>
                                    <span><i class="bi bi-clock"></i>{{ substr($r->jam_mulai,0,5) }} - {{ substr($r->jam_selesai,0,5) }}</span>
                                @else
                                    <span>·</span>
                                    <span><i class="bi bi-hourglass"></i>{{ $r->tarifSewa->jenisSewa->nama_jenis_sewa ?? '' }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="side">
                            <span class="price">Rp {{ number_format($r->total_biaya, 0, ',', '.') }}</span>
                            <span class="chip {{ $chipClass[$statusVal] ?? '' }}">{{ $statusVal }}</span>
                        </div>
                    </a>
                @endforeach
            @endif

            <div class="db-list-foot">
                <small>Menampilkan {{ $berjalan->count() }} dari {{ $totalReservasi }} reservasi</small>
                <a href="{{ route('customer.reservasi-saya.index') }}">Lihat Semua Reservasi <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>

        {{-- Mini calendar --}}
        <div class="xcard db-cal" data-reveal>
            <div class="cal-head">
                <h3><i class="bi bi-calendar-week text-primary" style="color:var(--primary) !important;"></i> Jadwal Pekan Ini</h3>
                <span class="month">{{ $awalPekan->translatedFormat('M Y') }}</span>
            </div>
            <div class="db-cal-grid">
                @foreach ($tanggalPekan as $tgl)
                    @php
                        $isToday = $tgl->isToday();
                        $adaResvHariItu = $adaReservasi->has($tgl->toDateString());
                        $kelas = $isToday ? 'today' : ($adaResvHariItu ? 'has-res' : '');
                    @endphp
                    <div class="db-cal-day {{ $kelas }}" title="{{ $tgl->translatedFormat('l, d F Y') }}">
                        <span class="dn">{{ strtoupper($tgl->translatedFormat('D')) }}</span>
                        <span class="dv">{{ $tgl->format('j') }}</span>
                    </div>
                @endforeach
            </div>
            @if ($berjalan->count() > 0)
                @php $reservasiTerdekat = $berjalan->sortBy('tanggal_mulai')->first(); @endphp
                <div class="db-cal-note">
                    <i class="bi bi-info-circle"></i>
                    Sesi Anda: {{ $reservasiTerdekat->tanggal_mulai->translatedFormat('l, d M') }}
                </div>
            @endif
        </div>
    </div>
@endsection