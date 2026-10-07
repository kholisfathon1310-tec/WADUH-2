@extends('admin.layouts.app')
@section('pantau_status', '1')
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

    // Reservasi per kategori — warna monokrom biru (shade berbeda) supaya konsisten palet.
    // Kategori tanpa reservasi tetap tampil (0), bukan hilang dari daftar.
    $kategoriMeta = [
        'Working Space'    => ['ikon' => 'bi-briefcase', 'shade' => '#176b87'],
        'Co-Working Space' => ['ikon' => 'bi-people',    'shade' => '#2f7fd1'],
        'Convention Hall'  => ['ikon' => 'bi-bank',      'shade' => '#0f526b'],
    ];
    $reservasiPerKategoriLengkap = collect($kategoriMeta)->keys()
        ->mapWithKeys(fn ($k) => [$k => (int) ($reservasiPerKategori[$k] ?? 0)]);
    $maxKat = max(1, $reservasiPerKategoriLengkap->max());

    // ── Diagram okupansi per bulan ──
    // Koordinat Y dalam piksel nyata (tinggi SVG tetap), koordinat X dalam persen lebar —
    // batang & teks tidak ikut mengecil/membesar saat kartu berubah lebar.
    $fmtPct = fn (float $p) => ($p > 0 && $p < 10 && floor($p) != $p) ? number_format($p, 1, ',', '.') : (string) round($p);
    $okMaks = collect($okupansiBulanan)->max('pct');
    [$okSkala, $okLangkah] = collect([[10, 2], [20, 5], [40, 10], [60, 20], [100, 25]])
        ->first(fn ($s) => $okMaks <= $s[0], [100, 25]);
    $okTicks = range(0, $okSkala, $okLangkah);
    $okAtas = 30; $okTinggi = 186; $okDasar = $okAtas + $okTinggi; $okSvgTinggi = 252; $okSetengah = 12;
    $okY = fn (float $v) => round($okDasar - ($v / $okSkala) * $okTinggi, 1);
    $okBulanKini = now()->format('Y-m');

    // ── Okupansi per fasilitas (bulan terpilih) ──
    $okTerpilih = collect($okupansiBulanan)->firstWhere('kunci', $okupansiNav['bulanKunci']);
    $okTotalFasilitas = $okupansiFasilitas->count();
    $okTerpakai = $okupansiFasilitas->where('terisi', '>', 0)->count();
    $okTertinggi = $okupansiFasilitas->where('terisi', '>', 0)->sortByDesc('pct')->first();
    $okPerLantai = $okupansiFasilitas->groupBy('lantai');
    $warnaLantaiOk = ['1' => '#2f7fd1', '2' => '#24aa9a', '3A' => '#7c5cd6', '3B' => '#e8833a', '5' => '#d6527c'];
    $qOkupansi = request()->except(['okupansi_bulan', 'okupansi_tahun']);
    $urlOkupansi = fn (array $param) => route('admin.dashboard', array_merge($qOkupansi, $param)).'#okupansi';
    $urlStatus = fn (string $bulan) => route('admin.dashboard', array_merge(request()->except('status_bulan'), ['status_bulan' => $bulan])).'#distribusi-status';
@endphp

@section('content')
{{-- Wilayah live: seluruh isi dashboard (termasuk skripnya) diperbarui realtime — lihat partials/pantau-status. --}}
<div data-live="dashboard">
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
                    @if ($menungguSekarang > 0)
                        Terdapat <strong>{{ $menungguSekarang }} fasilitas</strong> yang menunggu persetujuan Anda.
                    @else
                        Tidak ada fasilitas yang menunggu persetujuan.
                    @endif
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.reservasi.index', ['status' => 'Menunggu']) }}" class="dash-btn dash-btn-light"><i class="bi bi-hourglass-split me-1"></i>Proses Antrean</a>
                <a href="{{ route('admin.monitoring') }}" class="dash-btn dash-btn-ghost"><i class="bi bi-grid-3x3-gap me-1"></i>Monitoring</a>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         KARTU STATUS — jumlah reservasi per status yang diajukan pada bulan terpilih
         (?status_bulan=Y-m, sama dengan donut Distribusi Status di bawah).
         ═══════════════════════════════════════════════════════════ --}}
    <section class="dash-status" id="ringkasan-status" data-reveal>
        <div class="dash-tiles-head">
            <div class="dash-month-nav">
                <a href="{{ route('admin.dashboard', array_merge(request()->except('status_bulan'), ['status_bulan' => $statBulanNav['prev']])) }}#ringkasan-status" class="dash-cal-nav-btn" aria-label="Bulan sebelumnya"><i class="bi bi-chevron-left"></i></a>
                <span class="dash-month-lbl">{{ $statBulanNav['label'] }}</span>
                <a href="{{ route('admin.dashboard', array_merge(request()->except('status_bulan'), ['status_bulan' => $statBulanNav['next']])) }}#ringkasan-status" class="dash-cal-nav-btn" aria-label="Bulan berikutnya"><i class="bi bi-chevron-right"></i></a>
            </div>
        </div>
        <div class="dash-tiles">
            @foreach ($statusMeta as $key => $m)
                <a class="dash-tile" href="{{ route('admin.reservasi.index', ['status' => $m['label']]) }}" title="Lihat data berstatus {{ $m['label'] }}">
                    <div class="dash-tile-head">
                        <span class="dash-tile-ic" style="background:{{ $m['bg'] }}; color:{{ $m['ic'] }}"><i class="bi {{ $m['ikon'] }}"></i></span>
                        <span class="dash-tile-l">{{ $m['label'] }}</span>
                    </div>
                    <div class="dash-tile-v">{{ $statistik[$key] }}</div>
                    <div class="dash-tile-foot">
                        @if ($statistik['total'] > 0)
                            <span class="dash-tile-pct" style="color:{{ $m['ic'] }}">{{ round($statistik[$key] / $statistik['total'] * 100) }}%</span>
                            <span class="dash-tile-frac">dari {{ $statistik['total'] }} fasilitas</span>
                        @else
                            <span class="dash-tile-frac">Belum ada pemesanan</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         KALENDER RESERVASI — partial bersama Admin & Pemesan, hanya yang Disetujui.
         ═══════════════════════════════════════════════════════════ --}}
    @include('partials.kalender-reservasi', [
        ...$rangeKalender,
        'reservasiKalender' => $reservasiKalender,
        'routeDashboard' => 'admin.dashboard',
        'routeDetail' => 'admin.reservasi.show',
    ])

    {{-- ═══════════════════════════════════════════════════════════
         OKUPANSI PER BULAN — satu-satunya diagram batang di dashboard.
         Tiap batang adalah tautan: memilih bulan itu untuk rincian per fasilitas di bawahnya.
         ═══════════════════════════════════════════════════════════ --}}
    <section class="dash-card dash-anchor" id="okupansi" data-reveal>
        <div class="dash-card-head dash-card-head-wrap">
            <span><i class="bi bi-bar-chart-fill"></i> Okupansi per Bulan</span>
            <div class="dash-month-nav" role="group" aria-label="Pilih tahun">
                <a href="{{ $urlOkupansi($okupansiNav['tahunPrev']) }}" class="dash-cal-nav-btn" data-tip="Tahun sebelumnya" aria-label="Tahun sebelumnya"><i class="bi bi-chevron-left"></i></a>
                <span class="dash-month-lbl dash-month-lbl-tahun">{{ $okupansiNav['tahun'] }}</span>
                <a href="{{ $urlOkupansi($okupansiNav['tahunNext']) }}" class="dash-cal-nav-btn" data-tip="Tahun berikutnya" aria-label="Tahun berikutnya"><i class="bi bi-chevron-right"></i></a>
            </div>
        </div>
        <div class="dash-card-body">
            <div class="dash-okup-scroll" data-live-gulir="okupansi">
                <div class="dash-okup-plot">
                    {{-- Sumbu Y --}}
                    <svg class="dash-okup-axis" width="100%" height="{{ $okSvgTinggi }}" aria-hidden="true">
                        @foreach ($okTicks as $t)
                            <text x="100%" dx="-8" y="{{ $okY($t) + 4 }}" text-anchor="end">{{ $t }}%</text>
                        @endforeach
                    </svg>

                    <svg class="dash-okup-svg" width="100%" height="{{ $okSvgTinggi }}" role="img"
                         aria-label="Diagram batang okupansi fasilitas per bulan tahun {{ $okupansiNav['tahun'] }}">
                        @foreach ($okTicks as $t)
                            <line class="dash-okup-grid {{ $t === 0 ? 'is-base' : '' }}" x1="0" x2="100%" y1="{{ $okY($t) }}" y2="{{ $okY($t) }}"/>
                        @endforeach

                        @foreach ($okupansiBulanan as $i => $b)
                            @php
                                $terpilih = $b['kunci'] === $okupansiNav['bulanKunci'];
                                $kosong = $b['terisi'] === 0;
                                $yAtas = $kosong ? $okDasar - 2 : min($okDasar - 3, $okY($b['pct']));
                                $r = min(4, $okDasar - $yAtas);
                                $d = $kosong
                                    ? "M-{$okSetengah},{$okDasar} V{$yAtas} H{$okSetengah} V{$okDasar} Z"
                                    : "M-{$okSetengah},{$okDasar} V".($yAtas + $r)." Q-{$okSetengah},{$yAtas} ".(-$okSetengah + $r).",{$yAtas} H".($okSetengah - $r)." Q{$okSetengah},{$yAtas} {$okSetengah},".($yAtas + $r)." V{$okDasar} Z";
                                $catatan = number_format($b['terisi'], 0, ',', '.').' hari terpakai dari '.number_format($b['kapasitas'], 0, ',', '.').' hari tersedia';
                            @endphp
                            <a href="{{ $urlOkupansi(['okupansi_tahun' => $okupansiNav['tahun'], 'okupansi_bulan' => $b['kunci']]) }}"
                               aria-label="{{ $b['tanggal']->translatedFormat('F Y') }}: okupansi {{ $fmtPct($b['pct']) }}%, {{ $catatan }}"
                               @if ($terpilih) aria-current="true" @endif>
                                <svg class="dash-okup-col {{ $terpilih ? 'is-selected' : '' }} {{ $kosong ? 'is-empty' : '' }}"
                                     x="{{ round($i / 12 * 100, 4) }}%" width="8.3333%" height="{{ $okSvgTinggi }}" overflow="visible"
                                     data-tip-label="{{ $b['tanggal']->translatedFormat('F Y') }}"
                                     data-tip-value="{{ $fmtPct($b['pct']) }}%"
                                     data-tip-note="{{ $catatan }}"
                                     data-tip-color="{{ $terpilih ? '#176b87' : '#8fc3cf' }}">
                                    <rect class="dash-okup-band" x="5%" y="4" width="90%" height="{{ $okSvgTinggi - 6 }}" rx="10"/>
                                    <svg x="50%" overflow="visible">
                                        <path class="dash-okup-bar" style="--i:{{ $i }}" d="{{ $d }}"/>
                                        <text class="dash-okup-val" y="{{ $yAtas - 8 }}" text-anchor="middle">{{ $fmtPct($b['pct']) }}%</text>
                                        <text class="dash-okup-lbl" y="{{ $okDasar + 21 }}" text-anchor="middle">{{ $b['label'] }}</text>
                                        @if ($b['kunci'] === $okBulanKini)
                                            <circle class="dash-okup-now" cx="0" cy="{{ $okDasar + 29 }}" r="2"/>
                                        @endif
                                    </svg>
                                </svg>
                            </a>
                        @endforeach
                    </svg>
                </div>
            </div>

            <p class="dash-okup-geser"><i class="bi bi-arrow-left-right"></i> Geser untuk melihat bulan lainnya</p>

            {{-- Tampilan tabel untuk pembaca layar --}}
            <table class="visually-hidden">
                <caption>Okupansi fasilitas per bulan tahun {{ $okupansiNav['tahun'] }}</caption>
                <thead><tr><th scope="col">Bulan</th><th scope="col">Okupansi</th><th scope="col">Hari-fasilitas terisi</th></tr></thead>
                <tbody>
                    @foreach ($okupansiBulanan as $b)
                        <tr>
                            <th scope="row">{{ $b['tanggal']->translatedFormat('F Y') }}</th>
                            <td>{{ $fmtPct($b['pct']) }}%</td>
                            <td>{{ $b['terisi'] }} dari {{ $b['kapasitas'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         OKUPANSI PER FASILITAS — bulan terpilih, dikelompokkan per lantai.
         ═══════════════════════════════════════════════════════════ --}}
    <section class="dash-card dash-anchor dash-section-gap" data-reveal>
        <div class="dash-card-head dash-card-head-wrap">
            <span><i class="bi bi-door-open-fill"></i> Tingkat Pemakaian Fasilitas</span>
            <div class="dash-month-nav" role="group" aria-label="Pilih bulan">
                <a href="{{ $urlOkupansi($okupansiNav['bulanPrev']) }}" class="dash-cal-nav-btn" data-tip="Bulan sebelumnya" aria-label="Bulan sebelumnya"><i class="bi bi-chevron-left"></i></a>
                <span class="dash-month-lbl">{{ $okupansiNav['bulanLabel'] }}</span>
                <a href="{{ $urlOkupansi($okupansiNav['bulanNext']) }}" class="dash-cal-nav-btn" data-tip="Bulan berikutnya" aria-label="Bulan berikutnya"><i class="bi bi-chevron-right"></i></a>
            </div>
        </div>
        <div class="dash-card-body">
            <p class="dash-okf-intro">Persentase hari kerja yang terisi reservasi pada {{ $okupansiNav['bulanLabel'] }}.</p>
            <div class="dash-kpi">
                <div class="dash-kpi-item">
                    <span class="dash-kpi-l">Rata-rata seluruh gedung</span>
                    <span class="dash-kpi-v">{{ $fmtPct($okTerpilih['pct'] ?? 0) }}%</span>
                    <span class="dash-kpi-s">{{ number_format($okTerpilih['terisi'] ?? 0, 0, ',', '.') }} dari {{ number_format($okTerpilih['kapasitas'] ?? 0, 0, ',', '.') }} hari kerja terisi</span>
                </div>
                <div class="dash-kpi-item">
                    <span class="dash-kpi-l">Fasilitas yang dipakai</span>
                    <span class="dash-kpi-v">{{ $okTerpakai }}<small> / {{ $okTotalFasilitas }}</small></span>
                    <span class="dash-kpi-s">Ada reservasi pada bulan ini</span>
                </div>
                <div class="dash-kpi-item">
                    <span class="dash-kpi-l">Paling sering dipakai</span>
                    @if ($okTertinggi)
                        <span class="dash-kpi-v">{{ $okTertinggi['pct'] }}%</span>
                        <span class="dash-kpi-s">{{ $okTertinggi['nama'] }} · Lantai {{ $okTertinggi['lantai'] }}</span>
                    @else
                        <span class="dash-kpi-v">–</span>
                        <span class="dash-kpi-s">Belum ada fasilitas yang dipakai</span>
                    @endif
                </div>
            </div>

            @if ($okTotalFasilitas === 0)
                <p class="text-muted small mb-0">Belum ada fasilitas aktif.</p>
            @else
                <div class="dash-floor-bar">
                    <span class="dash-floor-bar-l">{{ $okTotalFasilitas }} fasilitas · {{ $okPerLantai->count() }} lantai</span>
                    <button type="button" class="dash-btn dash-btn-outline dash-btn-sm" id="dashFloorToggle" data-mode="buka">
                        <i class="bi bi-arrows-expand me-1"></i><span>Buka semua</span>
                    </button>
                </div>

                @foreach ($okPerLantai as $lantai => $daftar)
                    @php
                        $warna = $warnaLantaiOk[$lantai] ?? '#176b87';
                        $terpakaiLantai = $daftar->where('terisi', '>', 0)->count();
                        $kapLantai = $daftar->sum('hariKerja');
                        $pctLantai = $kapLantai > 0 ? round($daftar->sum('terisi') / $kapLantai * 100, 1) : 0.0;
                    @endphp
                    {{-- Semua lantai tertutup saat halaman dibuka; rincian tampil bila lantai diklik. --}}
                    <details class="dash-floor" style="--fl:{{ $warna }}" data-live-key="lantai-{{ $lantai }}">
                        <summary>
                            <span class="dash-floor-dot"></span>
                            <span class="dash-floor-name">Lantai {{ $lantai }}</span>
                            <span class="dash-floor-meta">{{ $terpakaiLantai }} dari {{ $daftar->count() }} fasilitas dipakai</span>
                            <span class="dash-floor-avg" title="Rata-rata hari kerja terisi di lantai ini">
                                <span class="dash-floor-track"><span class="dash-floor-fill" style="width:{{ min(100, $pctLantai) }}%"></span></span>
                                <b>{{ $fmtPct($pctLantai) }}%<small> terisi</small></b>
                            </span>
                            <i class="bi bi-chevron-down dash-floor-caret"></i>
                        </summary>
                        <div class="dash-fac-grid">
                            @foreach ($daftar as $f)
                                <a href="{{ route('admin.monitoring.detail', $f['id_fasilitas']) }}"
                                   class="dash-fac {{ $f['terisi'] > 0 ? '' : 'is-empty' }}"
                                   title="{{ $f['nama'] }} · {{ $f['kategori'] }} — klik untuk melihat jadwal">
                                    <span class="dash-fac-top">
                                        <span class="dash-fac-kode">{{ $f['kode'] }}</span>
                                        <span class="dash-fac-pct">{{ $f['pct'] }}%</span>
                                    </span>
                                    <span class="dash-fac-track"><span class="dash-fac-fill" style="width:{{ $f['terisi'] > 0 ? max(4, min(100, $f['pct'])) : 0 }}%"></span></span>
                                    <span class="dash-fac-sub">{{ $f['terisi'] > 0 ? 'Terisi '.$f['terisi'].' dari '.$f['hariKerja'].' hari kerja' : 'Belum ada reservasi' }}</span>
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endforeach
            @endif
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════
         Distribusi Status + Reservasi per Kategori (kiri) · Reservasi Terbaru (kanan)
         ═══════════════════════════════════════════════════════════ --}}
    <div class="dash-grid-2">
        <div class="dash-col">
            {{-- Donut Distribusi Status --}}
            <div class="dash-card dash-anchor" id="distribusi-status" data-reveal>
                <div class="dash-card-head dash-card-head-wrap">
                    <span><i class="bi bi-pie-chart-fill"></i> Distribusi Status</span>
                    <div class="dash-month-nav" role="group" aria-label="Pilih bulan pengajuan">
                        <a href="{{ $urlStatus($statBulanNav['prev']) }}" class="dash-cal-nav-btn" data-tip="Bulan sebelumnya" aria-label="Bulan sebelumnya"><i class="bi bi-chevron-left"></i></a>
                        <span class="dash-month-lbl">{{ $statBulanNav['label'] }}</span>
                        <a href="{{ $urlStatus($statBulanNav['next']) }}" class="dash-cal-nav-btn" data-tip="Bulan berikutnya" aria-label="Bulan berikutnya"><i class="bi bi-chevron-right"></i></a>
                    </div>
                </div>
                <div class="dash-card-body dash-card-body-split">
                    <div class="dash-donut-wrap">
                        <svg viewBox="0 0 120 120" class="dash-donut-svg" role="img" aria-label="Grafik distribusi status fasilitas yang dipesan pada {{ $statBulanNav['label'] }}">
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
                            @endif
                        </svg>
                        <div class="dash-donut-hole">
                            <div class="dash-donut-v">{{ $statistik['total'] }}</div>
                            <small>Fasilitas</small>
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

            {{-- Reservasi per Kategori --}}
            <div class="dash-card" data-reveal>
                <div class="dash-card-head">
                    <span><i class="bi bi-building"></i> Reservasi Aktif per Kategori</span>
                    <span class="dash-pill">{{ $reservasiPerKategoriLengkap->sum() }} fasilitas</span>
                </div>
                <div class="dash-card-body">
                    @foreach ($reservasiPerKategoriLengkap as $kategori => $jumlah)
                        @php
                            $meta = $kategoriMeta[$kategori] ?? ['ikon' => 'bi-door-open', 'shade' => 'var(--dash-primary)'];
                            $pct = round($jumlah / $maxKat * 100);
                        @endphp
                        <div class="dash-meter">
                            <span class="dash-meter-ic" style="background:{{ $meta['shade'] }}1a; color:{{ $meta['shade'] }}"><i class="bi {{ $meta['ikon'] }}"></i></span>
                            <div class="dash-meter-body">
                                <div class="dash-meter-top">
                                    <span class="dash-meter-name">{{ $kategori }}</span>
                                    <span class="dash-meter-count">{{ $jumlah }} <small>fasilitas</small></span>
                                </div>
                                <div class="dash-meter-track">
                                    <div class="dash-meter-fill" style="width:{{ $pct }}%; background:{{ $meta['shade'] }}"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <a href="{{ route('admin.laporan') }}" class="dash-btn dash-btn-outline w-100 mt-3 justify-content-center"><i class="bi bi-file-earmark-bar-graph me-1"></i>Lihat Laporan</a>
                </div>
            </div>
        </div>

        {{-- Reservasi Terbaru --}}
        <div class="dash-card" data-reveal>
            <div class="dash-card-head">
                <span><i class="bi bi-clock-history"></i> Reservasi Terbaru</span>
                <a href="{{ route('admin.reservasi.index') }}" class="dash-btn dash-btn-outline dash-btn-sm">Lihat Semua <i class="bi bi-arrow-right ms-1"></i></a>
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

    {{-- Tooltip mengambang — muncul saat kursor di atas batang okupansi, segmen donut, atau legenda. --}}
    <div class="dash-tip" id="dashTip" hidden>
        <div class="dash-tip-label"><span class="dot" id="dashTipDot"></span><span id="dashTipLabel"></span></div>
        <div class="dash-tip-value"><span id="dashTipValue"></span><small id="dashTipPct"></small></div>
        <div class="dash-tip-note" id="dashTipNote" hidden></div>
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
    --dash-primary-soft-2: #8fc3cf;    /* batang bulan yang tidak dipilih */
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

/* Tujuan tautan #okupansi / #distribusi-status tidak tertutup topbar yang menempel. */
.dash-anchor { scroll-margin-top:6.25rem; }
.dash-section-gap { margin:1.25rem 0 1.5rem; }

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
    overflow-wrap:anywhere;
}
.dash-hero-sub { margin:0; opacity:.9; font-size:.95rem; max-width:34rem; line-height:1.6; }

.dash-btn {
    display:inline-flex; align-items:center; justify-content:center;
    padding:.6rem 1.15rem; border-radius:.75rem;
    font-weight:700; font-size:.87rem; text-decoration:none;
    border:1.5px solid transparent; cursor:pointer;
    transition:transform .15s ease, background .15s ease, border-color .15s ease, color .15s ease;
}
.dash-btn-light { background:#fff; color:var(--dash-primary-dark); box-shadow:0 10px 22px -8px rgba(0,0,0,.25); }
.dash-btn-light:hover { color:var(--dash-primary-dark); transform:translateY(-2px); }
.dash-btn-ghost { background:rgba(255,255,255,.1); color:#fff; border-color:rgba(255,255,255,.3); }
.dash-btn-ghost:hover { background:rgba(255,255,255,.18); color:#fff; transform:translateY(-2px); }
.dash-btn-outline { background:#fff; color:var(--dash-primary); border-color:var(--dash-line); }
.dash-btn-outline:hover { border-color:var(--dash-primary); color:var(--dash-primary); background:var(--dash-primary-soft); }
.dash-btn-sm { padding:.4rem .8rem; font-size:.78rem; white-space:nowrap; }

/* ══════════════════════════════════════════════════════════════
   NAVIGASI PERIODE ‹ label › — dipakai kepala kartu okupansi & donut.
   ══════════════════════════════════════════════════════════════ */
.dash-month-nav { display:inline-flex; align-items:center; gap:.35rem;
    background:#fff; border:1px solid var(--dash-line); border-radius:.7rem; padding:.25rem .4rem; }
.dash-month-lbl { font-size:.82rem; font-weight:800; color:var(--dash-ink); min-width:6.75rem; text-align:center;
    font-variant-numeric:tabular-nums; }
.dash-month-lbl-tahun { min-width:3.25rem; }
.dash-cal-nav-btn { display:grid; place-items:center; width:1.9rem; height:1.9rem;
    border-radius:.5rem; background:transparent; border:0; color:var(--dash-muted);
    text-decoration:none; font-size:.85rem; transition:all .15s ease; flex:none; }
.dash-cal-nav-btn:hover { background:var(--dash-primary-soft); color:var(--dash-primary-dark); }

/* ══════════════════════════════════════════════════════════════
   KARTU
   ══════════════════════════════════════════════════════════════ */
.dash-grid-2 { display:grid; grid-template-columns:minmax(0, 1fr) minmax(0, 1.35fr); gap:1.25rem; align-items:start; }
.dash-col { display:flex; flex-direction:column; gap:1.25rem; min-width:0; }

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
.dash-card-head-wrap { flex-wrap:wrap; gap:.6rem; }
.dash-card-head > span:first-child i { color:var(--dash-primary); margin-right:.5rem; }
.dash-card-body { padding:1.3rem; flex:1; }
.dash-card-body-split { display:flex; flex-direction:column; gap:1rem; }
.dash-pill {
    background:var(--dash-primary-soft); color:var(--dash-primary-dark);
    font-size:.72rem; font-weight:700;
    padding:.35rem .75rem; border-radius:2rem; white-space:nowrap;
}

/* ═══ KARTU STATUS RESERVASI (per bulan) ═══ */
.dash-status { margin-bottom:1.5rem; }
/* Hanya navigasi bulan, rata kanan di atas kartu status. */
.dash-tiles-head { display:flex; align-items:center; justify-content:flex-end; margin-bottom:.85rem; }
.dash-tiles { display:grid; grid-template-columns:repeat(6, minmax(0, 1fr)); gap:1rem; }
.dash-tile { display:block; text-decoration:none; color:inherit; background:#fff; border:1px solid var(--dash-line);
    border-radius:var(--dash-radius); padding:1.1rem 1rem; box-shadow:var(--dash-shadow-sm); min-width:0;
    transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
.dash-tile:hover { transform:translateY(-3px); box-shadow:var(--dash-shadow-md); border-color:var(--dash-primary-soft-2, var(--dash-line)); color:inherit; }
.dash-tile:focus-visible { outline:2px solid var(--dash-primary); outline-offset:2px; }
.dash-tile-head { display:flex; align-items:center; gap:.5rem; margin-bottom:.85rem; min-width:0; }
.dash-tile-ic { display:grid; place-items:center; width:2.1rem; height:2.1rem; border-radius:.65rem; font-size:.95rem; flex:none; }
.dash-tile-l { font-size:.82rem; font-weight:700; color:var(--dash-ink); line-height:1.2; min-width:0; }
.dash-tile-v { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:2rem; line-height:1.05; color:var(--dash-ink);
    letter-spacing:-.025em; margin-bottom:.45rem; }
.dash-tile-foot { display:flex; align-items:baseline; gap:.4rem; font-size:.76rem; flex-wrap:wrap; }
.dash-tile-pct { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:.84rem; }
.dash-tile-frac { color:var(--dash-muted); font-weight:500; }
@media (max-width: 1199.98px) { .dash-tiles { grid-template-columns:repeat(3, minmax(0, 1fr)); } }
@media (max-width: 575.98px) {
    .dash-tiles { grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.65rem; }
    .dash-tile { padding:.9rem .95rem; }
    .dash-tile-v { font-size:1.6rem; }
    .dash-tile-head { margin-bottom:.6rem; }
}

/* ══════════════════════════════════════════════════════════════
   DIAGRAM OKUPANSI PER BULAN
   Batang 24px dengan ujung atas membulat, garis bantu tipis, teks memakai warna teks
   (bukan warna batang). Bulan terpilih = teal pekat, bulan lain = teal muda.
   ══════════════════════════════════════════════════════════════ */
.dash-okup-scroll { overflow-x:auto; overflow-y:hidden; margin:0 -.25rem; padding:0 .25rem .25rem; }
.dash-okup-plot { display:grid; grid-template-columns:2.6rem minmax(0, 1fr); min-width:35rem; }
.dash-okup-axis, .dash-okup-svg { display:block; overflow:visible; }
.dash-okup-axis { position:sticky; left:0; z-index:1; background:#fff; }
.dash-okup-axis text { font-family:'Plus Jakarta Sans',sans-serif; font-size:11px; font-weight:600;
    fill:var(--dash-soft); font-variant-numeric:tabular-nums; }
.dash-okup-grid { stroke:var(--dash-line-soft); stroke-width:1; shape-rendering:crispEdges; }
.dash-okup-grid.is-base { stroke:#cbd5e1; }

.dash-okup-svg a { cursor:pointer; outline:none; text-decoration:none; }
.dash-okup-svg text { text-decoration:none; }
.dash-okup-band { fill:var(--dash-primary-soft); opacity:0; transition:opacity .15s ease; }
.dash-okup-col:hover .dash-okup-band { opacity:.75; }
.dash-okup-col.is-selected .dash-okup-band { opacity:1; }
.dash-okup-svg a:focus-visible .dash-okup-band { opacity:1; stroke:var(--dash-primary); stroke-width:1.5; }

.dash-okup-bar {
    fill:var(--dash-primary-soft-2);
    transition:fill .15s ease;
    transform-box:fill-box; transform-origin:50% 100%;
    animation:barGrow .6s cubic-bezier(.2,.8,.3,1) both;
    animation-delay:calc(var(--i, 0) * 45ms);
}
.dash-okup-col:hover .dash-okup-bar { fill:#5fa6b8; }
.dash-okup-col.is-selected .dash-okup-bar,
.dash-okup-col.is-selected:hover .dash-okup-bar { fill:var(--dash-primary); }
.dash-okup-col.is-empty .dash-okup-bar,
.dash-okup-col.is-empty:hover .dash-okup-bar { fill:#d5dde6; animation:none; }
@keyframes barGrow { from { transform:scaleY(0); opacity:.35; } to { transform:scaleY(1); opacity:1; } }
@media (prefers-reduced-motion: reduce) { .dash-okup-bar { animation:none; } }

.dash-okup-val { font-family:'Plus Jakarta Sans',sans-serif; font-size:11.5px; font-weight:700; fill:#475569;
    font-variant-numeric:tabular-nums; }
.dash-okup-col.is-empty .dash-okup-val { fill:var(--dash-soft); font-weight:600; }
.dash-okup-col.is-selected .dash-okup-val { fill:var(--dash-ink); font-weight:800; }
.dash-okup-lbl { font-family:'Plus Jakarta Sans',sans-serif; font-size:12px; font-weight:600; fill:var(--dash-muted); }
.dash-okup-col.is-selected .dash-okup-lbl { fill:var(--dash-ink); font-weight:800; }
.dash-okup-now { fill:var(--dash-primary); }

.dash-okup-geser { display:none; margin:.35rem 0 0; text-align:center; font-size:.7rem; font-weight:600; color:var(--dash-soft); }
@media (max-width: 767.98px) { .dash-okup-geser { display:block; } }

/* ══════════════════════════════════════════════════════════════
   OKUPANSI PER FASILITAS — ringkasan + kartu ringkas per lantai
   ══════════════════════════════════════════════════════════════ */
.dash-kpi { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:.85rem; margin-bottom:1.15rem; }
.dash-kpi-item { display:flex; flex-direction:column; gap:.2rem; min-width:0;
    padding:.95rem 1.1rem; border-radius:.85rem; background:var(--dash-surface); border:1px solid var(--dash-line-soft); }
.dash-kpi-l { font-size:.7rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:var(--dash-muted); }
.dash-kpi-v { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:1.6rem; line-height:1.15;
    color:var(--dash-ink); letter-spacing:-.02em; }
.dash-kpi-v small { font-size:.95rem; font-weight:700; color:var(--dash-soft); letter-spacing:0; }
.dash-kpi-s { font-size:.76rem; color:var(--dash-muted); font-weight:500;
    overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.dash-kpi-s { white-space:normal; overflow:visible; text-overflow:clip; line-height:1.45; }

.dash-okf-intro { margin:0 0 1rem; font-size:.8rem; line-height:1.55; color:var(--dash-muted); }
.dash-floor-avg b small { font-size:.68rem; font-weight:600; color:var(--dash-muted); }
.dash-floor-bar { display:flex; align-items:center; justify-content:space-between; gap:.75rem; flex-wrap:wrap;
    margin-bottom:.7rem; }
.dash-floor-bar-l { font-size:.78rem; font-weight:700; color:var(--dash-muted); }

.dash-floor { border:1px solid var(--dash-line); border-radius:.85rem; background:#fff; overflow:hidden; }
.dash-floor + .dash-floor { margin-top:.6rem; }
.dash-floor > summary { list-style:none; cursor:pointer; user-select:none;
    display:flex; align-items:center; gap:.7rem; padding:.75rem 1rem;
    transition:background .15s ease; }
.dash-floor > summary::-webkit-details-marker { display:none; }
.dash-floor > summary:hover { background:var(--dash-surface); }
.dash-floor > summary:focus-visible { outline:2px solid var(--dash-primary); outline-offset:-2px; }
.dash-floor[open] > summary { border-bottom:1px solid var(--dash-line-soft); }
.dash-floor-dot { width:.7rem; height:.7rem; border-radius:.25rem; background:var(--fl); flex:none; }
.dash-floor-name { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:.9rem; color:var(--dash-ink); white-space:nowrap; }
.dash-floor-meta { font-size:.76rem; color:var(--dash-muted); font-weight:500; flex:1; min-width:0;
    overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.dash-floor-avg { display:inline-flex; align-items:center; gap:.55rem; flex:none; }
.dash-floor-avg b { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:.85rem; color:var(--dash-ink);
    min-width:2.6rem; text-align:right; font-variant-numeric:tabular-nums; }
.dash-floor-track { width:5.5rem; height:.4rem; border-radius:1rem; overflow:hidden;
    background:color-mix(in srgb, var(--fl) 16%, #fff); }
.dash-floor-fill { display:block; height:100%; border-radius:1rem; background:var(--fl); }
.dash-floor-caret { color:var(--dash-soft); font-size:.8rem; transition:transform .2s ease; flex:none; }
.dash-floor[open] .dash-floor-caret { transform:rotate(180deg); }

.dash-fac-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(9.5rem, 1fr)); gap:.55rem; padding:.85rem; }
.dash-fac { display:flex; flex-direction:column; gap:.4rem; min-width:0;
    padding:.65rem .75rem .7rem; border-radius:.7rem; border:1px solid var(--dash-line);
    background:#fff; text-decoration:none; color:inherit;
    transition:border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
.dash-fac:hover { border-color:var(--fl); box-shadow:var(--dash-shadow-sm); transform:translateY(-1px); color:inherit; }
.dash-fac:focus-visible { outline:2px solid var(--dash-primary); outline-offset:1px; }
.dash-fac-top { display:flex; align-items:baseline; justify-content:space-between; gap:.5rem; }
.dash-fac-kode { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:.84rem; color:var(--dash-ink);
    overflow:hidden; text-overflow:ellipsis; white-space:nowrap; min-width:0; }
.dash-fac-pct { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:.84rem; color:var(--dash-ink);
    font-variant-numeric:tabular-nums; flex:none; }
.dash-fac-track { display:block; height:.35rem; border-radius:1rem; overflow:hidden;
    background:color-mix(in srgb, var(--fl) 16%, #fff); }
.dash-fac-fill { display:block; height:100%; border-radius:1rem; background:var(--fl); }
.dash-fac-sub { font-size:.69rem; color:var(--dash-muted); font-weight:500; white-space:nowrap;
    overflow:hidden; text-overflow:ellipsis; }
.dash-fac.is-empty { background:var(--dash-surface); border-color:var(--dash-line-soft); }
.dash-fac.is-empty .dash-fac-kode { color:#475569; }
.dash-fac.is-empty .dash-fac-pct { color:var(--dash-soft); }
.dash-fac.is-empty .dash-fac-track { background:#e8edf2; }

/* ══════════════════════════════════════════════════════════════
   DONUT — cincin SVG animatif (stroke-dasharray), lebih besar & tajam.
   ══════════════════════════════════════════════════════════════ */
.dash-donut-wrap { position:relative; display:grid; place-items:center; padding:.5rem 0 .6rem; }
.dash-donut-svg {
    width:210px; height:210px; max-width:100%;
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
    display:grid; place-content:center; text-align:center;
    box-shadow:0 2px 14px rgba(15,23,42,.1);
    animation:donutPop .5s .3s cubic-bezier(.2,.9,.3,1.3) both;
}
@keyframes donutPop { from { transform:scale(.85); opacity:0; } to { transform:scale(1); opacity:1; } }
.dash-donut-v {
    font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:1.9rem;
    color:var(--dash-ink); line-height:1;
}
.dash-donut-hole small { color:var(--dash-muted); font-size:.72rem; font-weight:600; margin-top:.2rem; }

.dash-legend { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.55rem .6rem; }
.dash-legend-item {
    display:flex; align-items:center; gap:.55rem; min-width:0;
    font-size:.83rem; color:var(--dash-ink); font-weight:600;
    padding:.45rem .65rem; border-radius:.7rem;
}
.dash-legend-item .dot { width:.6rem; height:.6rem; border-radius:50%; flex:none; }
.dash-legend-item .lbl { flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.dash-legend-item .n { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; color:var(--dash-ink); }

/* ══════════════════════════════════════════════════════════════
   RESERVASI AKTIF PER KATEGORI (progress bars)
   ══════════════════════════════════════════════════════════════ */
.dash-meter { display:flex; align-items:center; gap:.9rem; padding:.55rem 0; }
.dash-meter + .dash-meter { border-top:1px solid var(--dash-line-soft); padding-top:.9rem; }
.dash-meter-ic {
    width:2.4rem; height:2.4rem; border-radius:.7rem;
    display:grid; place-items:center; font-size:1rem; flex:none;
}
.dash-meter-body { flex:1; min-width:0; }
.dash-meter-top { display:flex; justify-content:space-between; align-items:baseline; gap:.6rem; margin-bottom:.4rem; }
.dash-meter-name { font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; color:var(--dash-ink); font-size:.9rem;
    min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.dash-meter-count { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:1.05rem; color:var(--dash-ink);
    white-space:nowrap; flex:none; }
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
.dash-feed-main { font-family:'Plus Jakarta Sans',sans-serif; font-weight:700; font-size:.92rem; color:var(--dash-ink);
    overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
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
@media (max-width: 991.98px) {
    .dash-grid-2 { grid-template-columns:1fr; }
    .dash-hero { padding:1.8rem 1.6rem; }
    .dash-hero-title { font-size:1.5rem; }
}
@media (max-width: 767.98px) {
    .dash-kpi { grid-template-columns:1fr; gap:.6rem; }
    .dash-kpi-item { flex-direction:row; flex-wrap:wrap; align-items:baseline; gap:.15rem .6rem; padding:.8rem 1rem; }
    .dash-kpi-l { flex:1 1 100%; }
    .dash-kpi-v { font-size:1.35rem; }
    .dash-kpi-s { flex:1; min-width:0; }
    .dash-floor-track { width:3.5rem; }
}
@media (max-width: 575.98px) {
    .dash-hero { padding:1.4rem 1.2rem; margin-bottom:1.15rem; }
    .dash-hero-title { font-size:1.3rem; }
    .dash-hero-sub { font-size:.88rem; }
    .dash-hero-body { gap:1rem; }
    .dash-hero .dash-btn { flex:1 1 auto; }
    .dash-card-head { font-size:.88rem; padding:.9rem 1.1rem; }
    .dash-card-body { padding:1.1rem; }
    .dash-floor > summary { flex-wrap:wrap; gap:.4rem .6rem; padding:.7rem .85rem; }
    .dash-floor-meta { flex:1 1 auto; }
    .dash-floor-avg { flex:1 1 100%; order:5; }
    .dash-floor-track { flex:1; width:auto; }
    .dash-floor-caret { order:4; }
    .dash-fac-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); padding:.7rem; gap:.5rem; }
    .dash-feed-row { padding:.85rem 1.1rem; gap:.7rem; }
    .dash-feed-badge { font-size:.66rem; padding:.25rem .5rem; gap:.3rem; }
    .dash-meter-count small { display:none; }
}
@media (max-width: 399.98px) {
    .dash-legend { grid-template-columns:1fr; }
    .dash-month-lbl { min-width:5.75rem; font-size:.78rem; }
}

/* ══════════════════════════════════════════════════════════════
   TOOLTIP MENGAMBANG — batang okupansi, donut, & legenda.
   ══════════════════════════════════════════════════════════════ */
.dash-tip {
    position:fixed; z-index:1080; pointer-events:none;
    background:#0f172a; color:#f1f5f9; border-radius:.85rem;
    padding:.7rem .95rem; min-width:9rem;
    font-family:'Plus Jakarta Sans',sans-serif;
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
    font-weight:800; font-size:1.3rem; color:#fff;
    display:flex; align-items:baseline; gap:.4rem; white-space:nowrap;
}
.dash-tip-value small { font-size:.7rem; font-weight:700; color:#5eead4; }
.dash-tip-note { margin-top:.2rem; font-size:.72rem; font-weight:500; color:#cbd5e1; white-space:nowrap; }
</style>
<script>
    // Cincin donut digambar dari 0 lalu ditransisikan ke panjang aslinya (data-dash)
    // supaya terlihat "tumbuh" saat halaman dimuat, alih-alih langsung penuh.
    requestAnimationFrame(() => {
        document.querySelectorAll('.donut-seg').forEach(seg => {
            seg.style.strokeDasharray = seg.dataset.dash;
        });
    });

    // Tooltip mengambang, mengikuti kursor — batang okupansi, donut, & legenda status.
    (function () {
        const tip = document.getElementById('dashTip');
        const elDot = document.getElementById('dashTipDot');
        const elLabel = document.getElementById('dashTipLabel');
        const elValue = document.getElementById('dashTipValue');
        const elPct = document.getElementById('dashTipPct');
        const elNote = document.getElementById('dashTipNote');
        if (!tip) return;

        const posisi = evt => {
            const pad = 16;
            let x = evt.clientX + pad;
            let y = evt.clientY + pad;
            const rect = tip.getBoundingClientRect();
            if (x + rect.width > window.innerWidth - 8) x = evt.clientX - rect.width - pad;
            if (y + rect.height > window.innerHeight - 8) y = evt.clientY - rect.height - pad;
            tip.style.left = Math.max(8, x) + 'px';
            tip.style.top = Math.max(8, y) + 'px';
        };

        const tampilkan = (el, evt) => {
            elDot.style.background = el.dataset.tipColor || '#176b87';
            elLabel.textContent = el.dataset.tipLabel || '';
            elValue.textContent = el.dataset.tipValue || '0';
            elPct.textContent = el.dataset.tipPct ? el.dataset.tipPct + '%' : '';
            elNote.textContent = el.dataset.tipNote || '';
            elNote.hidden = !el.dataset.tipNote;
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

    // Layar sempit: diagram digulir mendatar — posisikan bulan terpilih di tengah saat dimuat.
    (function () {
        const wadah = document.querySelector('.dash-okup-scroll');
        const terpilih = wadah?.querySelector('.dash-okup-col.is-selected');
        if (!wadah || !terpilih || wadah.scrollWidth <= wadah.clientWidth) return;
        const w = wadah.getBoundingClientRect();
        const t = terpilih.getBoundingClientRect();
        wadah.scrollLeft += (t.left + t.width / 2) - (w.left + w.width / 2);
    })();

    // Buka/tutup semua lantai pada rincian okupansi per fasilitas.
    (function () {
        const btn = document.getElementById('dashFloorToggle');
        if (!btn) return;
        const lantai = [...document.querySelectorAll('.dash-floor')];
        const segarkan = () => {
            const semuaTerbuka = lantai.every(d => d.open);
            btn.dataset.mode = semuaTerbuka ? 'tutup' : 'buka';
            btn.querySelector('span').textContent = semuaTerbuka ? 'Tutup semua' : 'Buka semua';
            btn.querySelector('i').className = 'bi me-1 ' + (semuaTerbuka ? 'bi-arrows-collapse' : 'bi-arrows-expand');
        };
        btn.addEventListener('click', () => {
            const buka = btn.dataset.mode === 'buka';
            lantai.forEach(d => { d.open = buka; });
            segarkan();
        });
        lantai.forEach(d => d.addEventListener('toggle', segarkan));
        segarkan();
    })();
</script>
</div>
@endsection
