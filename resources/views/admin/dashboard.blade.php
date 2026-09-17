@extends('admin.layouts.app')
@section('title', 'Dashboard')

@php
    $me = Auth::guard('admin')->user();

    // Palet SEMANTIC status — dipertahankan (biar Menunggu ≠ Disetujui ≠ Ditolak
    // mudah dibedakan), tapi lebih calm: bg soft + icon berwarna.
    $statusMeta = [
        'menunggu'   => ['label' => 'Menunggu',   'ikon' => 'bi-hourglass-split', 'ic' => '#b45309', 'bg' => '#fef3c7', 'dot' => '#f59e0b'],
        'disetujui'  => ['label' => 'Disetujui',  'ikon' => 'bi-check-circle',    'ic' => '#047857', 'bg' => '#d1fae5', 'dot' => '#10b981'],
        'selesai'    => ['label' => 'Selesai',    'ikon' => 'bi-check2-all',      'ic' => '#1d4ed8', 'bg' => '#dbeafe', 'dot' => '#3b82f6'],
        'ditolak'    => ['label' => 'Ditolak',    'ikon' => 'bi-x-circle',        'ic' => '#b91c1c', 'bg' => '#fee2e2', 'dot' => '#ef4444'],
        'dibatalkan' => ['label' => 'Dibatalkan', 'ikon' => 'bi-slash-circle',    'ic' => '#475569', 'bg' => '#f1f5f9', 'dot' => '#94a3b8'],
        'kadaluwarsa' => ['label' => 'Kadaluwarsa', 'ikon' => 'bi-clock-history',   'ic' => '#7c3aed', 'bg' => '#ede9fe', 'dot' => '#8b5cf6'],
    ];

    // Segmen donut (pakai `dot` color, soft tapi masih terbaca).
    // Dirender sebagai cincin SVG (stroke-dasharray per segmen) — lebih tajam & bisa dianimasikan
    // dibanding conic-gradient CSS (yang sering meninggalkan celah antisipasi di sudut segmen).
    $total = max(1, $statistik['total']);
    $donutR = 50;
    $donutKeliling = 2 * M_PI * $donutR;
    $segmen = []; $accPanjang = 0;
    foreach ($statusMeta as $key => $m) {
        $jumlah = $statistik[$key];
        $panjang = $total > 0 ? ($jumlah / $total) * $donutKeliling : 0;
        $segmen[] = [
            'label'  => $m['label'],
            'jumlah' => $jumlah,
            'warna'  => $m['dot'],
            'panjang' => $panjang,
            'offset'  => $accPanjang,
        ];
        $accPanjang += $panjang;
    }

    // Reservasi per kategori — warna monokrom (shade teal berbeda) supaya konsisten palet.
    // Kategori tanpa reservasi tetap tampil (0), bukan hilang dari daftar.
    $kategoriMeta = [
        'Working Space'    => ['ikon' => 'bi-briefcase', 'shade' => '#176b87'],
        'Co-Working Space' => ['ikon' => 'bi-people',    'shade' => '#24aa9a'],
        'Convention Hall'  => ['ikon' => 'bi-bank',      'shade' => '#178f87'],
    ];
    $reservasiPerKategoriLengkap = collect($kategoriMeta)->keys()
        ->mapWithKeys(fn ($k) => [$k => (int) ($reservasiPerKategori[$k] ?? 0)]);
    $maxKat = max(1, $reservasiPerKategoriLengkap->max());

    // Data untuk bar chart tren — jumlah bar berubah sesuai periode (7 harian / 12 bulanan),
    // jadi lebar bar disesuaikan otomatis biar tetap proporsional & tidak terlalu rapat.
    $maxTrend = max(1, collect($trendChart)->max('jumlah'));
    $trendChartWidth = 100;   // percent-based SVG viewBox
    $barCount = count($trendChart);
    $barW = $barCount > 7 ? 5 : 8;   // width bar dalam viewBox unit
    $gap  = (100 - $barW * $barCount) / max(1, $barCount - 1);
@endphp

@section('content')
<div class="dash">

    {{-- ═══════════════════════════════════════════════════════════
         HERO — sapaan & shortcut aksi
         ═══════════════════════════════════════════════════════════ --}}
    <div class="dash-hero" data-reveal>
        <div class="dash-hero-mesh"></div>
        <div class="dash-hero-body">
            <div>
                <span class="dash-hero-eyebrow"><i class="bi bi-calendar3 me-1"></i>{{ now()->translatedFormat('l, d F Y') }}</span>
                <h2 class="dash-hero-title">Selamat datang, {{ $me?->nama_admin }}</h2>
                <p class="dash-hero-sub">
                    @if ($statistik['menunggu'] > 0)
                        Ada <strong>{{ $statistik['menunggu'] }} reservasi menunggu</strong> persetujuan Anda hari ini.
                    @else
                        Tidak ada antrean persetujuan — semua reservasi sudah ditangani.
                    @endif
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.reservasi.index', ['status' => 'Menunggu']) }}" class="dash-btn dash-btn-light"><i class="bi bi-hourglass-split me-1"></i>Proses Antrian</a>
                <a href="{{ route('admin.monitoring') }}" class="dash-btn dash-btn-ghost"><i class="bi bi-grid-3x3-gap me-1"></i>Monitoring</a>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         STAT TILES — 4 KPI ringkas
         ═══════════════════════════════════════════════════════════ --}}
    <div class="dash-tiles">
        @foreach ($statusMeta as $key => $m)
            <div class="dash-tile" data-reveal>
                <div class="dash-tile-head">
                    <span class="dash-tile-ic" style="background:{{ $m['bg'] }}; color:{{ $m['ic'] }}"><i class="bi {{ $m['ikon'] }}"></i></span>
                    <span class="dash-tile-l">{{ $m['label'] }}</span>
                </div>
                <div class="dash-tile-v">{{ $statistik[$key] }}</div>
                <div class="dash-tile-foot">
                    @if ($statistik['total'] > 0)
                        <span class="dash-tile-pct" style="color:{{ $m['ic'] }}">
                            {{ round($statistik[$key] / $statistik['total'] * 100) }}%
                        </span>
                        <span class="dash-tile-frac">dari {{ $statistik['total'] }} reservasi</span>
                    @else
                        <span class="dash-tile-frac">Belum ada reservasi</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         ROW 1 — Distribusi Status (donut) + Tren 7 Hari (bar chart BARU)
         ═══════════════════════════════════════════════════════════ --}}
    <div class="dash-grid-2">
        {{-- Donut Distribusi Status --}}
        <div class="dash-card" data-reveal>
            <div class="dash-card-head">
                <span><i class="bi bi-pie-chart-fill"></i> Distribusi Status</span>
                <span class="dash-pill">{{ $statistik['total'] }} total</span>
            </div>
            <div class="dash-card-body dash-card-body-split">
                <div class="dash-donut-wrap">
                    <svg viewBox="0 0 120 120" class="dash-donut-svg" role="img" aria-label="Grafik distribusi status reservasi">
                        <circle class="donut-track" cx="60" cy="60" r="{{ $donutR }}"/>
                        @if ($statistik['total'] > 0)
                            @foreach ($segmen as $i => $s)
                                @if ($s['jumlah'] > 0)
                                    @php $pct = $statistik['total'] > 0 ? round($s['jumlah'] / $statistik['total'] * 100) : 0; @endphp
                                    <circle class="donut-seg" cx="60" cy="60" r="{{ $donutR }}"
                                            stroke="{{ $s['warna'] }}"
                                            stroke-dashoffset="{{ -$s['offset'] }}"
                                            data-dash="{{ $s['panjang'] }} {{ $donutKeliling }}"
                                            data-tip-label="{{ $s['label'] }}" data-tip-value="{{ $s['jumlah'] }}"
                                            data-tip-pct="{{ $pct }}" data-tip-color="{{ $s['warna'] }}"
                                            style="stroke-dasharray:0 {{ $donutKeliling }}; transition-delay:{{ $i * .12 }}s"
                                            transform="rotate(-90 60 60)"></circle>
                                @endif
                            @endforeach
                        @else
                            <circle class="donut-track" cx="60" cy="60" r="{{ $donutR }}" stroke="#e5e9ef" stroke-width="14"/>
                        @endif
                    </svg>
                    <div class="dash-donut-hole">
                        <div class="dash-donut-v">{{ $statistik['total'] }}</div>
                        <small>Reservasi</small>
                    </div>
                </div>
                <div class="dash-legend">
                    @foreach ($segmen as $s)
                        @php $pctLegend = $statistik['total'] > 0 ? round($s['jumlah'] / $statistik['total'] * 100) : 0; @endphp
                        <div class="dash-legend-item" style="background:{{ $s['warna'] }}14"
                             data-tip-label="{{ $s['label'] }}" data-tip-value="{{ $s['jumlah'] }}"
                             data-tip-pct="{{ $pctLegend }}" data-tip-color="{{ $s['warna'] }}">
                            <span class="dot" style="background:{{ $s['warna'] }}"></span>
                            <span class="lbl">{{ $s['label'] }}</span>
                            <span class="n">{{ $s['jumlah'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Bar chart Tren — toggle Harian (7 hari) / Bulanan (12 bulan) --}}
        <div class="dash-card" data-reveal>
            <div class="dash-card-head dash-card-head-trend">
                <span><i class="bi bi-graph-up-arrow"></i> {{ $periode === 'bulanan' ? 'Tren 12 Bulan Terakhir' : 'Tren 7 Hari Terakhir' }}</span>
                <div class="d-flex align-items-center gap-2">
                    <span class="dash-pill">{{ collect($trendChart)->sum('jumlah') }} reservasi</span>
                    <div class="dash-toggle" role="group" aria-label="Pilih periode tren">
                        <a href="{{ route('admin.dashboard', ['periode' => 'harian']) }}"
                           class="dash-toggle-opt {{ $periode === 'harian' ? 'active' : '' }}">Harian</a>
                        <a href="{{ route('admin.dashboard', ['periode' => 'bulanan']) }}"
                           class="dash-toggle-opt {{ $periode === 'bulanan' ? 'active' : '' }}">Bulanan</a>
                    </div>
                </div>
            </div>
            <div class="dash-card-body">
                <div class="dash-chart">
                    {{-- SVG bar chart custom: viewBox 100 unit lebar x 72 unit tinggi + label bawah.
                         preserveAspectRatio default (xMidYMid meet) dipakai — bukan "none" — supaya
                         bar/teks/grid selalu diskalakan proporsional, tidak gepeng saat kartu melebar. --}}
                    <svg viewBox="0 0 100 72" class="dash-chart-svg" role="img" aria-label="Grafik tren reservasi">
                        <defs>
                            <linearGradient id="barToday" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="var(--teal)"/>
                                <stop offset="100%" stop-color="var(--dash-primary)"/>
                            </linearGradient>
                            <linearGradient id="barSoft" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#cbe8ec"/>
                                <stop offset="100%" stop-color="var(--dash-primary-soft-2)"/>
                            </linearGradient>
                        </defs>
                        {{-- Gridlines horizontal --}}
                        @for ($g = 1; $g <= 3; $g++)
                            <line x1="0" y1="{{ $g * 16 }}" x2="100" y2="{{ $g * 16 }}" stroke="#eef2f6" stroke-width=".25"/>
                        @endfor
                        @foreach ($trendChart as $i => $d)
                            @php
                                $h = $d['jumlah'] > 0 ? max(3, round($d['jumlah'] / $maxTrend * 58)) : 1.5;
                                $x = $i * ($barW + $gap);
                                $y = 64 - $h;
                                $isAktif = $d['isAktif'];
                                $tipLabel = $periode === 'bulanan' ? $d['tanggal']->translatedFormat('F Y') : $d['tanggal']->translatedFormat('l, d F');
                            @endphp
                            <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barW }}" height="{{ $h }}"
                                  rx="1.8" ry="1.8"
                                  fill="{{ $isAktif ? 'url(#barToday)' : 'url(#barSoft)' }}"
                                  class="dash-bar" style="--i:{{ $i }}"
                                  data-tip-label="{{ $tipLabel }}"
                                  data-tip-value="{{ $d['jumlah'] }}"
                                  data-tip-color="{{ $isAktif ? 'var(--dash-primary)' : 'var(--dash-primary-soft-2)' }}"></rect>
                            {{-- Angka di atas bar (disembunyikan di mode bulanan kalau bar terlalu rapat) --}}
                            @if ($d['jumlah'] > 0 && $barCount <= 7)
                                <text x="{{ $x + $barW / 2 }}" y="{{ $y - 1.8 }}"
                                      text-anchor="middle" font-size="3.4" font-weight="700"
                                      fill="{{ $isAktif ? 'var(--dash-primary)' : '#64748b' }}"
                                      font-family="'Plus Jakarta Sans',sans-serif">{{ $d['jumlah'] }}</text>
                            @endif
                            {{-- Label hari/bulan di bawah --}}
                            <text x="{{ $x + $barW / 2 }}" y="70.5"
                                  text-anchor="middle" font-size="{{ $barCount > 7 ? 2.6 : 2.9 }}" font-weight="600"
                                  fill="{{ $isAktif ? 'var(--dash-primary)' : '#94a3b8' }}"
                                  font-family="'DM Sans',sans-serif">{{ $d['label'] }}</text>
                        @endforeach
                    </svg>
                </div>
                <div class="dash-chart-foot">
                    <span><span class="dot" style="background:var(--dash-primary)"></span> {{ $periode === 'bulanan' ? 'Bulan ini' : 'Hari ini' }}</span>
                    <span><span class="dot" style="background:var(--dash-primary-soft-2)"></span> {{ $periode === 'bulanan' ? '11 bulan sebelumnya' : '6 hari sebelumnya' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         ROW 2 — Reservasi per Kategori + Reservasi Terbaru
         ═══════════════════════════════════════════════════════════ --}}
    <div class="dash-grid-2b">
        {{-- Reservasi per Kategori --}}
        <div class="dash-card" data-reveal>
            <div class="dash-card-head">
                <span><i class="bi bi-building"></i> Reservasi per Kategori</span>
                <span class="dash-pill">{{ $reservasiPerKategoriLengkap->sum() }} reservasi</span>
            </div>
            <div class="dash-card-body">
                @forelse ($reservasiPerKategoriLengkap as $kategori => $jumlah)
                    @php
                        $meta = $kategoriMeta[$kategori] ?? ['ikon' => 'bi-door-open', 'shade' => 'var(--dash-primary)'];
                        $pct = round($jumlah / $maxKat * 100);
                    @endphp
                    <div class="dash-meter">
                        <span class="dash-meter-ic" style="background:{{ $meta['shade'] }}1a; color:{{ $meta['shade'] }}"><i class="bi {{ $meta['ikon'] }}"></i></span>
                        <div class="dash-meter-body">
                            <div class="dash-meter-top">
                                <span class="dash-meter-name">{{ $kategori }}</span>
                                <span class="dash-meter-count" style="color:{{ $meta['shade'] }}">{{ $jumlah }} <small>reservasi</small></span>
                            </div>
                            <div class="dash-meter-track">
                                <div class="dash-meter-fill" style="width:{{ $pct }}%; background:{{ $meta['shade'] }}"></div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">Belum ada reservasi.</p>
                @endforelse
                <a href="{{ route('admin.laporan') }}" class="dash-btn dash-btn-outline w-100 mt-3 justify-content-center"><i class="bi bi-file-earmark-bar-graph me-1"></i>Lihat Laporan</a>
            </div>
        </div>

        {{-- Reservasi Terbaru --}}
        <div class="dash-card" data-reveal>
            <div class="dash-card-head">
                <span><i class="bi bi-clock-history"></i> Reservasi Terbaru</span>
                <a href="{{ route('admin.reservasi.index') }}" class="dash-btn dash-btn-outline dash-btn-sm">Semua <i class="bi bi-arrow-right ms-1"></i></a>
            </div>
            <div class="dash-feed">
                @forelse ($terbaru as $r)
                    @php
                        $statusKey = strtolower($r->status_reservasi->value);
                        $sm = $statusMeta[$statusKey] ?? null;
                    @endphp
                    <a href="{{ route('admin.reservasi.show', $r->kode_reservasi) }}" class="dash-feed-row">
                        <span class="dash-feed-avatar">{{ strtoupper(substr($r->pemesan->nama_lengkap, 0, 1)) }}</span>
                        <span class="dash-feed-body">
                            <span class="dash-feed-main">{{ $r->pemesan->nama_lengkap }}</span>
                            <span class="dash-feed-sub">{{ $r->tarifSewa->fasilitas->nama_fasilitas }} <span class="sep">·</span> {{ $r->kode_reservasi }}</span>
                        </span>
                        @if ($sm)
                            <span class="dash-feed-badge" style="background:{{ $sm['bg'] }}; color:{{ $sm['ic'] }}">
                                <span class="dot" style="background:{{ $sm['dot'] }}"></span>{{ $sm['label'] }}
                            </span>
                        @endif
                    </a>
                @empty
                    <p class="text-muted small p-4 mb-0 text-center">Belum ada reservasi.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Tooltip mengambang — muncul saat kursor di atas segmen donut, batang tren, atau legenda. --}}
    <div class="dash-tip" id="dashTip" hidden>
        <div class="dash-tip-label"><span class="dot" id="dashTipDot"></span><span id="dashTipLabel"></span></div>
        <div class="dash-tip-value"><span id="dashTipValue"></span><small id="dashTipPct"></small></div>
    </div>
</div>

<style>
/* ══════════════════════════════════════════════════════════════
   PALET LOKAL DASHBOARD — monokrom teal, konsisten homepage.
   ══════════════════════════════════════════════════════════════ */
.dash {
    --dash-primary:        #176b87;
    --dash-primary-dark:   #0f526b;
    --dash-primary-soft:   #eef7f8;
    --dash-primary-soft-2: #b8dde4;    /* untuk bar chart yang lebih pale */
    --dash-ink:            #0f172a;
    --dash-muted:          #64748b;
    --dash-soft:           #94a3b8;
    --dash-line:           #e5e9ef;
    --dash-line-soft:      #eef2f6;
    --dash-surface:        #f7f9fc;
    --dash-radius:         1rem;
    --dash-radius-lg:      1.25rem;
    --dash-shadow-sm:      0 6px 16px -6px rgba(15,23,42,.08);
    --dash-shadow-md:      0 14px 34px -14px rgba(15,23,42,.14);
}

@keyframes dashUp { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:none; } }
.dash [data-reveal] { animation:dashUp .55s cubic-bezier(.2,.7,.3,1) both; }
@media (prefers-reduced-motion: reduce) { .dash [data-reveal] { animation:none; } }

/* ══════════════════════════════════════════════════════════════
   HERO — gradient teal clean (dari palet homepage, bukan biru+hijau).
   ══════════════════════════════════════════════════════════════ */
.dash-hero {
    position:relative; overflow:hidden;
    border-radius:var(--dash-radius-lg);
    padding:2.5rem 2.5rem;
    margin-bottom:1.5rem;
    background:linear-gradient(135deg, var(--dash-primary) 0%, var(--dash-primary-dark) 100%);
    color:#fff;
    box-shadow:0 24px 50px -22px rgba(8,75,88,.5);
}
.dash-hero-mesh {
    position:absolute; inset:0; pointer-events:none;
    background:
        radial-gradient(28rem 18rem at 105% -10%, rgba(255,255,255,.14), transparent 55%),
        radial-gradient(22rem 14rem at -10% 110%, rgba(255,255,255,.08), transparent 55%);
}
.dash-hero-body {
    position:relative; z-index:1;
    display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center;
    gap:1.5rem;
}
.dash-hero-eyebrow {
    display:inline-flex; align-items:center;
    font-size:.78rem; font-weight:700; color:rgba(255,255,255,.85);
    letter-spacing:.02em;
}
.dash-hero-title {
    font-family:'Plus Jakarta Sans',sans-serif; font-weight:800;
    font-size:1.75rem; margin:.4rem 0 .35rem; color:#fff; letter-spacing:-.025em;
}
.dash-hero-sub { margin:0; opacity:.9; font-size:.95rem; max-width:34rem; line-height:1.6; }

.dash-btn {
    display:inline-flex; align-items:center; justify-content:center;
    padding:.6rem 1.15rem; border-radius:.75rem;
    font-weight:700; font-size:.87rem; text-decoration:none;
    border:1.5px solid transparent;
    transition:transform .15s ease, background .15s ease, border-color .15s ease, color .15s ease;
}
.dash-btn-light { background:#fff; color:var(--dash-primary-dark); box-shadow:0 10px 22px -8px rgba(0,0,0,.25); }
.dash-btn-light:hover { color:var(--dash-primary-dark); transform:translateY(-2px); }
.dash-btn-ghost { background:rgba(255,255,255,.1); color:#fff; border-color:rgba(255,255,255,.3); }
.dash-btn-ghost:hover { background:rgba(255,255,255,.18); color:#fff; transform:translateY(-2px); }
.dash-btn-outline { background:#fff; color:var(--dash-primary); border-color:var(--dash-line); }
.dash-btn-outline:hover { border-color:var(--dash-primary); color:var(--dash-primary); background:var(--dash-primary-soft); }
.dash-btn-sm { padding:.4rem .8rem; font-size:.78rem; }

/* ══════════════════════════════════════════════════════════════
   STAT TILES — 4 KPI card putih dengan icon soft.
   ══════════════════════════════════════════════════════════════ */
.dash-tiles { display:grid; grid-template-columns:repeat(6, 1fr); gap:1rem; margin-bottom:1.5rem; }
.dash-tile {
    background:#fff; border:1px solid var(--dash-line);
    border-radius:var(--dash-radius); padding:1.25rem 1.35rem;
    box-shadow:var(--dash-shadow-sm);
    transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease;
}
.dash-tile:hover { transform:translateY(-4px); box-shadow:var(--dash-shadow-md); border-color:transparent; }
.dash-tile-head { display:flex; align-items:center; gap:.7rem; margin-bottom:1rem; }
.dash-tile-ic {
    display:grid; place-items:center;
    width:2.4rem; height:2.4rem;
    border-radius:.7rem; font-size:1.05rem;
    flex:none;
}
.dash-tile-l { font-size:.85rem; font-weight:700; color:var(--dash-ink); }
.dash-tile-v {
    font-family:'Plus Jakarta Sans',sans-serif; font-weight:800;
    font-size:2.1rem; line-height:1.05; color:var(--dash-ink); letter-spacing:-.025em;
    margin-bottom:.5rem;
}
.dash-tile-foot { display:flex; align-items:baseline; gap:.5rem; font-size:.78rem; }
.dash-tile-pct { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:.85rem; }
.dash-tile-frac { color:var(--dash-muted); font-weight:500; }

/* ══════════════════════════════════════════════════════════════
   GRID 2 KOLOM (2 baris)
   ══════════════════════════════════════════════════════════════ */
.dash-grid-2  { display:grid; grid-template-columns:minmax(0, 1fr) minmax(0, 1.5fr); gap:1.25rem; margin-bottom:1.25rem; align-items:stretch; }
.dash-grid-2b { display:grid; grid-template-columns:minmax(0, 1fr) minmax(0, 1.4fr); gap:1.25rem; align-items:start; }

.dash-card {
    background:#fff; border:1px solid var(--dash-line);
    border-radius:var(--dash-radius); overflow:hidden;
    box-shadow:var(--dash-shadow-sm);
    display:flex; flex-direction:column;
}
.dash-card-head {
    padding:1.05rem 1.3rem;
    border-bottom:1px solid var(--dash-line-soft);
    font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; color:var(--dash-ink); font-size:.95rem;
    display:flex; justify-content:space-between; align-items:center; gap:.5rem;
}
.dash-card-head > span:first-child i { color:var(--dash-primary); margin-right:.5rem; }
.dash-card-body { padding:1.3rem; flex:1; }
/* Donut: donut di atas, legenda dipin ke bawah — supaya kartu yang di-stretch setinggi
   kartu sebelahnya (tren 7 hari) tidak menyisakan ruang kosong di bawah legenda. */
.dash-card-body-split { display:flex; flex-direction:column; justify-content:space-between; gap:1rem; }
.dash-pill {
    background:var(--dash-primary-soft); color:var(--dash-primary-dark);
    font-size:.72rem; font-weight:700;
    padding:.35rem .75rem; border-radius:2rem;
}

/* ══════════════════════════════════════════════════════════════
   DONUT — cincin SVG animatif (stroke-dasharray), lebih besar & tajam.
   ══════════════════════════════════════════════════════════════ */
.dash-donut-wrap { position:relative; display:grid; place-items:center; padding:.5rem 0 1.2rem; }
.dash-donut-svg {
    width:210px; height:210px;
    filter:drop-shadow(0 10px 22px rgba(15,23,42,.14));
}
.donut-track { fill:none; stroke:var(--dash-line-soft); stroke-width:14; }
.donut-seg {
    fill:none; stroke-width:14; stroke-linecap:round; cursor:pointer;
    transition:stroke-dasharray 1s cubic-bezier(.16,.84,.44,1), stroke-width .15s ease, opacity .15s ease;
}
.donut-seg:hover { stroke-width:17; }
.dash-donut-svg:has(.donut-seg:hover) .donut-seg:not(:hover) { opacity:.45; }
.dash-donut-hole {
    position:absolute; inset:0; margin:auto;
    width:138px; height:138px; border-radius:50%; background:#fff;
    display:grid; place-items:center; text-align:center;
    box-shadow:0 2px 14px rgba(15,23,42,.1);
    animation:donutPop .5s .3s cubic-bezier(.2,.9,.3,1.3) both;
}
@keyframes donutPop { from { transform:scale(.85); opacity:0; } to { transform:scale(1); opacity:1; } }
.dash-donut-v {
    font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:1.9rem;
    color:var(--dash-primary); line-height:1;
}
.dash-donut-hole small { color:var(--dash-muted); font-size:.72rem; font-weight:600; margin-top:.2rem; }

.dash-legend { display:grid; grid-template-columns:1fr 1fr; gap:.55rem .6rem; }
.dash-legend-item {
    display:flex; align-items:center; gap:.55rem;
    font-size:.83rem; color:var(--dash-ink); font-weight:600;
    padding:.45rem .65rem; border-radius:.7rem;
    transition:transform .15s ease;
}
.dash-legend-item { cursor:pointer; }
.dash-legend-item:hover { transform:translateY(-1px); }
.dash-legend-item .dot { width:.6rem; height:.6rem; border-radius:50%; flex:none; }
.dash-legend-item .lbl { flex:1; }
.dash-legend-item .n { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; color:var(--dash-ink); }

/* ══════════════════════════════════════════════════════════════
   BAR CHART TREND 7 HARI
   ══════════════════════════════════════════════════════════════ */
.dash-chart { padding:.5rem 0 .3rem; }
.dash-chart-svg { width:100%; max-width:560px; height:auto; aspect-ratio:100 / 72; display:block; margin:0 auto; }
.dash-bar {
    transition:filter .2s ease;
    transform-box:fill-box; transform-origin:50% 100%;
    animation:barGrow .65s cubic-bezier(.2,.8,.3,1) both;
    animation-delay:calc(var(--i, 0) * 70ms);
}
.dash-bar:hover { filter:brightness(1.12); cursor:pointer; }
@keyframes barGrow { from { transform:scaleY(0); opacity:.35; } to { transform:scaleY(1); opacity:1; } }
@media (prefers-reduced-motion: reduce) { .dash-bar { animation:none; } }
.dash-chart-foot {
    display:flex; gap:1.5rem; justify-content:center;
    margin-top:.8rem; font-size:.78rem; color:var(--dash-muted); font-weight:600;
}
.dash-chart-foot .dot { display:inline-block; width:.65rem; height:.65rem; border-radius:.25rem; margin-right:.4rem; vertical-align:middle; }

/* ══════════════════════════════════════════════════════════════
   TOGGLE PERIODE (Harian / Bulanan) — di head card Tren
   ══════════════════════════════════════════════════════════════ */
.dash-card-head-trend { flex-wrap:wrap; gap:.6rem; }
.dash-toggle {
    display:inline-flex; padding:.2rem; border-radius:2rem;
    background:var(--dash-line-soft); gap:.15rem;
}
.dash-toggle-opt {
    padding:.32rem .85rem; border-radius:1.6rem;
    font-size:.76rem; font-weight:700; text-decoration:none;
    color:var(--dash-muted); transition:all .15s ease; white-space:nowrap;
}
.dash-toggle-opt:hover { color:var(--dash-primary); }
.dash-toggle-opt.active { background:var(--dash-primary); color:#fff; box-shadow:var(--dash-shadow-sm); }
@media (max-width: 575.98px) {
    .dash-toggle-opt { padding:.28rem .65rem; font-size:.72rem; }
}

/* ══════════════════════════════════════════════════════════════
   FASILITAS AKTIF (progress bars)
   ══════════════════════════════════════════════════════════════ */
.dash-meter { display:flex; align-items:center; gap:.9rem; padding:.55rem 0; }
.dash-meter + .dash-meter { border-top:1px solid var(--dash-line-soft); padding-top:.9rem; }
.dash-meter-ic {
    width:2.4rem; height:2.4rem; border-radius:.7rem;
    display:grid; place-items:center; font-size:1rem; flex:none;
}
.dash-meter-body { flex:1; min-width:0; }
.dash-meter-top { display:flex; justify-content:space-between; align-items:baseline; margin-bottom:.4rem; }
.dash-meter-name { font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; color:var(--dash-ink); font-size:.9rem; }
.dash-meter-count { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:1.05rem; }
.dash-meter-count small { font-size:.65rem; color:var(--dash-muted); font-weight:500; margin-left:.15rem; }
.dash-meter-track { height:.5rem; border-radius:1rem; background:var(--dash-line-soft); overflow:hidden; }
.dash-meter-fill { height:100%; border-radius:1rem; transition:width .6s cubic-bezier(.2,.7,.3,1); }

/* ══════════════════════════════════════════════════════════════
   RESERVASI TERBARU — activity feed
   ══════════════════════════════════════════════════════════════ */
.dash-feed { display:flex; flex-direction:column; }
.dash-feed-row {
    display:flex; align-items:center; gap:.9rem;
    padding:.95rem 1.3rem;
    text-decoration:none; color:inherit;
    border-bottom:1px solid var(--dash-line-soft);
    transition:background .15s ease;
}
.dash-feed-row:last-child { border-bottom:0; }
.dash-feed-row:hover { background:var(--dash-primary-soft); }
.dash-feed-avatar {
    display:grid; place-items:center;
    width:2.4rem; height:2.4rem; border-radius:.7rem;
    background:var(--dash-primary); color:#fff;
    font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:.9rem;
    flex:none;
    box-shadow:inset 0 -2px 0 rgba(0,0,0,.12);
}
.dash-feed-body { flex:1; min-width:0; display:flex; flex-direction:column; gap:.15rem; }
.dash-feed-main { font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:.92rem; color:var(--dash-ink); }
.dash-feed-sub { font-size:.78rem; color:var(--dash-muted); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.dash-feed-sub .sep { margin:0 .35rem; color:var(--dash-soft); }
.dash-feed-badge {
    display:inline-flex; align-items:center; gap:.4rem;
    font-size:.75rem; font-weight:700;
    padding:.35rem .75rem; border-radius:2rem;
    white-space:nowrap; flex:none;
}
.dash-feed-badge .dot { width:.5rem; height:.5rem; border-radius:50%; }

/* ══════════════════════════════════════════════════════════════
   RESPONSIVE
   ══════════════════════════════════════════════════════════════ */
@media (max-width: 1199.98px) {
    .dash-tiles { grid-template-columns:repeat(3, 1fr); }
}
@media (max-width: 991.98px) {
    .dash-grid-2, .dash-grid-2b { grid-template-columns:1fr; }
    .dash-tiles { grid-template-columns:repeat(2, 1fr); }
    .dash-hero { padding:1.8rem 1.6rem; }
    .dash-hero-title { font-size:1.5rem; }
}
@media (max-width: 575.98px) {
    .dash-tiles { grid-template-columns:1fr; }
    .dash-card-head { font-size:.88rem; padding:.9rem 1.1rem; }
    .dash-card-body { padding:1.1rem; }
    .dash-feed-badge { display:none; }
}

/* ══════════════════════════════════════════════════════════════
   TOOLTIP MENGAMBANG — donut, bar tren, & legenda.
   ══════════════════════════════════════════════════════════════ */
.dash-tip {
    position:fixed; z-index:1080; pointer-events:none;
    background:#0f172a; color:#f1f5f9; border-radius:.85rem;
    padding:.7rem .95rem; min-width:9rem;
    font-family:'DM Sans',sans-serif;
    box-shadow:0 16px 34px rgba(2,6,23,.32);
    opacity:0; transform:translateY(4px) scale(.97);
    transition:opacity .12s ease, transform .12s ease;
}
.dash-tip.show { opacity:1; transform:none; }
.dash-tip-label {
    display:flex; align-items:center; gap:.45rem;
    font-size:.68rem; font-weight:700; color:#94a3b8;
    text-transform:uppercase; letter-spacing:.04em; margin-bottom:.3rem;
    white-space:nowrap;
}
.dash-tip-label .dot { width:.55rem; height:.55rem; border-radius:50%; flex:none; box-shadow:0 0 0 3px rgba(255,255,255,.08); }
.dash-tip-value {
    font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:1.3rem; color:#fff;
    display:flex; align-items:baseline; gap:.4rem; white-space:nowrap;
}
.dash-tip-value small { font-size:.7rem; font-weight:700; color:#5eead4; }
</style>
<script>
    // Cincin donut digambar dari 0 lalu ditransisikan ke panjang aslinya (data-dash)
    // supaya terlihat "tumbuh" saat halaman dimuat, alih-alih langsung penuh.
    requestAnimationFrame(() => {
        document.querySelectorAll('.donut-seg').forEach(seg => {
            seg.style.strokeDasharray = seg.dataset.dash;
        });
    });

    // Tooltip mengambang, mengikuti kursor — donut, bar tren 7 hari, & legenda status.
    (function () {
        const tip = document.getElementById('dashTip');
        const elDot = document.getElementById('dashTipDot');
        const elLabel = document.getElementById('dashTipLabel');
        const elValue = document.getElementById('dashTipValue');
        const elPct = document.getElementById('dashTipPct');
        if (!tip) return;

        const posisi = evt => {
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
            elDot.style.background = el.dataset.tipColor || '#0e6b7d';
            elLabel.textContent = el.dataset.tipLabel || '';
            elValue.textContent = el.dataset.tipValue || '0';
            elPct.textContent = el.dataset.tipPct ? el.dataset.tipPct + '%' : '';
            tip.hidden = false;
            requestAnimationFrame(() => tip.classList.add('show'));
            posisi(evt);
        };
        const sembunyikan = () => {
            tip.classList.remove('show');
            tip.hidden = true;
        };

        document.querySelectorAll('[data-tip-value]').forEach(el => {
            el.addEventListener('mouseenter', evt => tampilkan(el, evt));
            el.addEventListener('mousemove', posisi);
            el.addEventListener('mouseleave', sembunyikan);
        });
    })();
</script>
@endsection