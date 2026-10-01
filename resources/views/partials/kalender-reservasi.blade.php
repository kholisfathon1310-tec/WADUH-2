{{--
    Kalender Reservasi — dipakai bersama oleh dashboard Pemesan & Admin.

    Wajib diisi oleh pemanggil:
    - $view, $tanggalAcuan, $rentangAwal, $rentangAkhir, $labelHeader, $navPrev, $navNext, $paramNav
      (hasil App\Support\KalenderReservasi::range())
    - $reservasiKalender  — Collection<Reservasi> sudah difilter (mis. status Disetujui) & di-load
      relasi 'tarifSewa.fasilitas.lantai', 'tarifSewa.jenisSewa'
    - $routeDashboard     — nama route dashboard (nav prev/next/hari-ini/switch view)
    - $routeDetail        — nama route detail reservasi (link tiap event, menerima kode_reservasi)
--}}
@php
    $chipClass = [
        'Menunggu' => 'menunggu', 'Disetujui' => 'disetujui', 'Ditolak' => 'ditolak',
        'Selesai' => 'selesai', 'Dibatalkan' => 'dibatalkan', 'Kadaluwarsa' => 'kadaluwarsa',
    ];
    $chipLabel = $chipClass;

    $reservasiByDate = $reservasiKalender->groupBy(fn ($r) => $r->tanggal_mulai->toDateString());

    $eventsByDate = $reservasiByDate->map(function ($items) use ($chipClass, $chipLabel, $routeDetail) {
        return $items->map(function ($r) use ($chipClass, $chipLabel, $routeDetail) {
            $fs = $r->tarifSewa->fasilitas;
            $statusVal = $r->status_reservasi->value;

            return [
                'kode'        => $r->kode_reservasi,
                'nama'        => $fs->nama_fasilitas,
                'kategori'    => $fs->kategori_fasilitas,
                'lantai'      => $fs->lantai->nomor_lantai ?? '-',
                'jamMulai'    => $r->jam_mulai ? substr($r->jam_mulai, 0, 5) : null,
                'jamSelesai'  => $r->jam_selesai ? substr($r->jam_selesai, 0, 5) : null,
                'tanggal'     => $r->tanggal_mulai->translatedFormat('l, d M Y'),
                'satuan'      => $r->tarifSewa->jenisSewa->satuan->value ?? '',
                'durasi'      => $r->durasi,
                'keperluan'   => $r->keperluan,
                'status'      => $statusVal,
                'statusLabel' => $chipLabel[$statusVal] ?? $statusVal,
                'statusChip'  => $chipClass[$statusVal] ?? '',
                'total'       => number_format($r->total_biaya, 0, ',', '.'),
                'url'         => route($routeDetail, $r->kode_reservasi),
            ];
        })->values();
    });

    // Jam operasional gedung BITC: 08:00 – 17:00 WIB
    $jamMulaiGrid = 8;
    $jamAkhirGrid = 17;
    $tinggiPerJam = 4; // rem — cukup lega

    // Satu palet biru konsisten & profesional untuk semua reservasi (bukan per-kategori)
    // supaya kalender terlihat rapi & tidak ramai warna.
    $warnaEvent = ['bg' => '#eff6ff', 'text' => '#1e40af', 'border' => '#bfdbfe', 'accent' => '#2563eb'];

    // Susun event yang beririsan waktunya (mis. 2 ruangan dipesan di jam yang sama) supaya
    // ditampilkan berdampingan (kolom terpisah), bukan menumpuk/menyatu jadi satu blok.
    $layoutEventsOverlap = function ($events) use ($jamMulaiGrid, $jamAkhirGrid) {
        $items = $events->map(function ($ev) use ($jamMulaiGrid, $jamAkhirGrid) {
            $jmHour = (int) substr($ev->jam_mulai, 0, 2) + ((int) substr($ev->jam_mulai, 3, 2)) / 60;
            $jsHour = (int) substr($ev->jam_selesai, 0, 2) + ((int) substr($ev->jam_selesai, 3, 2)) / 60;
            $jmHour = max($jamMulaiGrid, $jmHour);
            $jsHour = min($jamAkhirGrid, $jsHour);
            if ($jsHour <= $jmHour) {
                $jsHour = $jmHour + 0.5;
            }

            return ['ev' => $ev, 'start' => $jmHour, 'end' => $jsHour];
        })->sortBy('start')->values();

        $columnsEnd = [];
        $placed = [];
        foreach ($items as $item) {
            $colIdx = null;
            foreach ($columnsEnd as $idx => $endTime) {
                if ($endTime <= $item['start']) {
                    $colIdx = $idx;
                    break;
                }
            }
            if ($colIdx === null) {
                $colIdx = count($columnsEnd);
            }
            $columnsEnd[$colIdx] = $item['end'];
            $placed[] = ['ev' => $item['ev'], 'start' => $item['start'], 'end' => $item['end'], 'col' => $colIdx];
        }

        // Total kolom dihitung per klaster irisan waktu, supaya event yang tidak
        // beririsan dengan siapa pun tetap tampil selebar penuh (bukan ikut menyempit).
        foreach ($placed as $i => &$p) {
            $clusterCols = [$p['col']];
            foreach ($placed as $j => $q) {
                if ($i === $j) {
                    continue;
                }
                if ($q['start'] < $p['end'] && $q['end'] > $p['start']) {
                    $clusterCols[] = $q['col'];
                }
            }
            $p['cols'] = count(array_unique($clusterCols));
        }
        unset($p);

        return $placed;
    };
@endphp

<style>
    /* ══════════════════════════════════════════════════════════
       KALENDER GOOGLE-STYLE — modern & clean
       ══════════════════════════════════════════════════════════ */
    .db-cal-wrap { padding:0; margin-bottom:1.75rem; }

    .db-cal-head { display:flex; align-items:center; justify-content:space-between;
        padding:1.1rem 1.5rem; border-bottom:1px solid var(--line-soft);
        flex-wrap:wrap; gap:.85rem; }
    .db-cal-head-left { display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; }
    .db-cal-hari-ini { font-size:.78rem; font-weight:800; padding:.55rem 1.05rem;
        background:#fff; color:var(--ink); border:1px solid var(--line);
        border-radius:.7rem; text-decoration:none; transition:all .15s ease; flex:none; }
    .db-cal-hari-ini:hover { background:var(--primary-soft); color:var(--primary-dark);
        border-color:var(--primary-softer); }
    {{-- Grup navigasi: < [label periode] > — label tanggal/bulan diapit dua panah supaya
         jelas kalau geser tanggal & lihat periode yang sedang aktif ada di satu tempat. --}}
    .db-cal-nav-group { display:inline-flex; align-items:center; gap:.15rem; flex-wrap:wrap; }
    .db-cal-nav-btn { display:grid; place-items:center; width:2.15rem; height:2.15rem;
        border-radius:.6rem; background:transparent; border:0;
        color:var(--muted); text-decoration:none; font-size:.9rem; flex:none;
        transition:all .15s ease; cursor:pointer; }
    .db-cal-nav-btn:hover { background:var(--surface-2); color:var(--ink); }
    .db-cal-title-wrap { position:relative; display:inline-flex; border-radius:.5rem;
        transition:background .15s ease, border-color .15s ease; border:1px solid transparent; }
    .db-cal-title-wrap:hover { background:var(--surface-2); border-color:var(--line); }
    .db-cal-title { font-size:1.1rem; font-weight:800; color:var(--ink);
        padding:.2rem .5rem; letter-spacing:-.01em; text-align:center; white-space:nowrap;
        pointer-events:none; }
    /* Input tanggal asli DITUMPUK TRANSPARAN persis di atas label (position:absolute + inset:0),
       jadi klik di mana pun pada label ini benar-benar mengenai date-input aslinya — kalender
       bawaan browser terbuka lewat perilaku native klik biasa, bukan trik JS. */
    .db-cal-date-overlay { position:absolute; inset:0; width:100%; height:100%;
        opacity:0; border:0; padding:0; margin:0; cursor:pointer; }

    .db-cal-view-switch { display:inline-flex; background:var(--surface-2);
        border-radius:.65rem; padding:.25rem; border:1px solid var(--line); }
    .db-cal-view-switch a { padding:.45rem 1rem; font-size:.78rem; font-weight:700;
        color:var(--muted); text-decoration:none; border-radius:.45rem;
        transition:all .18s ease; }
    .db-cal-view-switch a:hover { color:var(--ink); }
    .db-cal-view-switch a.active { background:#fff; color:var(--primary-dark);
        box-shadow:0 2px 5px rgba(15,23,42,.08); font-weight:800; }

    /* ─── MODE BULAN ─── */
    .db-cal-grid { padding:1rem 1.15rem 1.15rem; }
    .db-cal-weekdays { display:grid; grid-template-columns:repeat(7, 1fr); gap:.4rem; margin-bottom:.5rem; }
    .db-cal-weekday { text-align:center; font-size:.62rem; font-weight:800;
        letter-spacing:.1em; text-transform:uppercase; color:var(--soft); padding:.4rem 0; }
    .db-cal-days { display:grid; grid-template-columns:repeat(7, 1fr); gap:.4rem; }
    .db-cal-day { position:relative; min-height:5.5rem; padding:.45rem .4rem .4rem;
        border-radius:.65rem; background:#fff; border:1px solid var(--line-soft);
        transition:all .15s ease; overflow:hidden;
        display:flex; flex-direction:column; }
    .db-cal-day.clickable { cursor:pointer; }
    .db-cal-day.clickable:hover { background:var(--primary-soft); border-color:var(--primary-softer);
        transform:translateY(-1px); box-shadow:0 4px 10px -4px rgba(15,60,73,.15); }
    .db-cal-day.other-month { background:transparent; border-color:transparent; }
    .db-cal-day.other-month .dnum { color:var(--soft); opacity:.5; }
    .db-cal-day.today { background:linear-gradient(135deg, var(--primary-soft), var(--primary-tint));
        border-color:var(--primary); }
    .db-cal-day.today .dnum { color:#fff; background:var(--primary-dark);
        width:1.7rem; height:1.7rem; border-radius:50%; display:grid; place-items:center;
        font-size:.72rem; box-shadow:0 4px 10px -2px rgba(15,118,110,.5); }
    .db-cal-day .dnum { font-size:.75rem; font-weight:800; color:var(--ink); margin-bottom:.25rem;
        width:1.6rem; height:1.6rem; display:grid; place-items:center; }
    .db-cal-events-month { display:flex; flex-direction:column; gap:.18rem; }
    .db-cal-event-month { font-size:.62rem; font-weight:700; padding:.18rem .4rem;
        border-radius:.35rem; border:1px solid; line-height:1.2;
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap; cursor:pointer; }
    .db-cal-event-month .ev-time { font-weight:800; }

    /* ─── MODE MINGGU & HARI (time grid Google style) ─── */
    .db-cal-time-wrap { overflow-x:auto; overflow-y:hidden; }
    .db-cal-time-inner { min-width:100%; }

    .db-cal-time-head { display:grid; align-items:center;
        padding:0; border-bottom:1px solid var(--line);
        background:#fff; position:sticky; top:0; z-index:5; }
    .db-cal-time-head-cell { text-align:center; padding:.75rem .35rem .85rem;
        border-left:1px solid var(--line-soft); }
    .db-cal-time-head-cell:first-child { border-left:0; padding:.55rem .5rem;
        display:flex; align-items:flex-end; justify-content:flex-end;
        font-size:.58rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--soft); }
    .db-cal-time-head-cell .wd { font-size:.65rem; font-weight:800;
        letter-spacing:.1em; text-transform:uppercase; color:var(--soft); margin-bottom:.35rem; }
    .db-cal-time-head-cell .dnum { font-size:1.5rem; font-weight:800; color:var(--ink);
        line-height:1; }
    .db-cal-time-head-cell.today .wd { color:var(--primary); }
    .db-cal-time-head-cell.today .dnum { color:#fff; background:var(--primary-dark);
        width:2.6rem; height:2.6rem; border-radius:50%; display:inline-grid; place-items:center;
        font-size:1.2rem; box-shadow:0 6px 14px -3px rgba(15,118,110,.55); }

    /* Time grid body */
    .db-cal-time-body { display:grid; position:relative;
        max-height:32rem; overflow-y:auto; background:#fff; }
    .db-cal-time-labels { grid-column:1; }
    .db-cal-time-col { position:relative; border-left:1px solid var(--line-soft); }
    .db-cal-time-col:nth-child(2) { border-left:0; }
    .db-cal-time-col.today { background:linear-gradient(rgba(15,118,110,.035), rgba(15,118,110,.015)); }

    .db-cal-hour-row { border-top:1px solid var(--line-soft); position:relative; }
    .db-cal-hour-row:first-child { border-top:0; }
    .db-cal-hour-label { display:flex; align-items:flex-start; justify-content:flex-end;
        padding:.2rem .65rem 0 0; font-size:.65rem; font-weight:700; color:var(--soft);
        border-right:1px solid var(--line-soft); background:#fff;
        border-top:1px solid var(--line-soft); }
    .db-cal-hour-label:first-child { border-top:0; }

    /* Event yang positioned by time — left & width dihitung per-item (lihat $layoutEventsOverlap
       di atas) supaya 2 reservasi yang jamnya beririsan tampil berdampingan dengan jarak yang
       jelas, bukan menumpuk/menyatu jadi satu blok. */
    .db-cal-time-event { position:absolute;
        border-radius:.55rem; border-left:3px solid; box-sizing:border-box;
        padding:.4rem .55rem; overflow:hidden; text-decoration:none;
        transition:transform .15s ease, box-shadow .15s ease, border-color .15s ease; cursor:pointer;
        box-shadow:0 1px 3px rgba(15,23,42,.06), 0 1px 2px rgba(15,23,42,.08);
        border:1px solid rgba(37,99,235,.16); border-left-width:3px;
        display:flex; flex-direction:column; z-index:2; }
    .db-cal-time-event:hover { transform:translateY(-1px);
        box-shadow:0 10px 20px -6px rgba(37,99,235,.35); z-index:6;
        border-color:var(--primary); }
    .db-cal-time-event .ev-time-label { font-size:.6rem; font-weight:800; opacity:.9; margin-bottom:.15rem;
        white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .db-cal-time-event .ev-nm { font-size:.75rem; font-weight:800; line-height:1.25;
        overflow:hidden; text-overflow:ellipsis;
        display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; }
    .db-cal-time-event .ev-lantai { font-size:.6rem; font-weight:700; opacity:.85;
        margin-top:.15rem; display:flex; align-items:center; gap:.2rem; white-space:nowrap; }

    .db-cal-time-empty { grid-column:1 / -1; text-align:center; padding:3rem 1rem;
        color:var(--muted); font-size:.85rem; }
    .db-cal-time-empty i { font-size:2rem; color:var(--soft); }
    .db-cal-time-empty p { margin:.75rem 0 0; font-weight:600; }

    @media (max-width: 767.98px) { .db-cal-time-inner { min-width:44rem; } }

    .db-cal-more { font-size:.58rem; color:var(--muted); font-weight:700; text-align:center; margin-top:.15rem; white-space:nowrap; }
    .db-cal-wrap { scroll-margin-top:6.25rem; }

    /* Layar kecil: sel tanggal hanya ±2rem lebarnya, nama ruangan tidak akan terbaca —
       reservasi ditampilkan sebagai garis penanda, rinciannya lewat ketuk tanggal. */
    @media (max-width: 575.98px) {
        .db-cal-head { padding:.85rem .9rem; gap:.65rem; }
        .db-cal-head-left { gap:.5rem; width:100%; justify-content:space-between; }
        .db-cal-hari-ini { padding:.5rem .8rem; }
        .db-cal-title { font-size:.92rem; padding:.2rem .3rem; }
        .db-cal-view-switch { display:flex; width:100%; }
        .db-cal-view-switch a { flex:1; text-align:center; padding:.45rem .5rem; }
        .db-cal-grid { padding:.6rem .5rem .75rem; }
        .db-cal-weekdays, .db-cal-days { gap:.2rem; }
        .db-cal-weekday { font-size:.56rem; letter-spacing:.03em; }
        .db-cal-day { min-height:3.6rem; padding:.3rem .1rem; align-items:center; border-radius:.5rem; }
        .db-cal-day .dnum { margin-bottom:.2rem; }
        .db-cal-events-month { width:100%; align-items:center; gap:.17rem; }
        .db-cal-event-month { font-size:0; line-height:0; padding:0; width:68%; height:.28rem;
            border:0 !important; border-radius:1rem; background:#2563eb !important; }
        .db-cal-event-month::after, .db-cal-event-month::before { display:none !important; }
        .db-cal-more { font-size:.52rem; margin-top:.05rem; }
        .db-day-head { padding:1rem 1.1rem; }
        .db-day-body { padding:1rem 1.1rem 1.2rem; }
        .db-day-event { padding:.9rem .95rem; }
    }

    /* ══════════════ FLOATING CARD (detail hari, muncul dekat tanggal yang diklik) ══════════════ */
    .db-floating-card { position:fixed; z-index:1080; width:25rem; max-width:calc(100vw - 1.5rem);
        background:#fff; border:1px solid var(--line); border-radius:1.4rem; overflow:hidden;
        box-shadow:0 30px 60px -14px rgba(15,23,42,.3);
        opacity:0; transform:translateY(8px) scale(.98); pointer-events:none;
        transition:opacity .16s ease, transform .16s ease; }
    .db-floating-card.show { opacity:1; transform:translateY(0) scale(1); pointer-events:auto; }
    .db-day-close { flex:none; background:none; border:0; width:2.1rem; height:2.1rem; border-radius:50%;
        color:var(--muted); font-size:1.05rem; display:grid; place-items:center; transition:background .15s ease, color .15s ease; }
    .db-day-close:hover { background:var(--surface-2); color:var(--ink); }
    .db-day-head { padding:1.4rem 1.85rem; border-bottom:1px solid var(--line-soft);
        background:linear-gradient(135deg, var(--surface), #fff);
        display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; }
    .db-day-head-l { display:flex; align-items:flex-start; gap:.9rem; min-width:0; }
    .db-day-head-ic { display:grid; place-items:center; width:2.85rem; height:2.85rem; border-radius:.9rem;
        background:linear-gradient(135deg, var(--primary-dark), var(--primary));
        color:#fff; font-size:1.2rem; flex:none;
        box-shadow:0 10px 20px -5px rgba(15,118,110,.5); }
    .db-day-head-title { font-size:1.08rem; font-weight:800; color:var(--ink); margin:0; letter-spacing:-.01em; }
    .db-day-head-sub { font-size:.78rem; color:var(--muted); margin:.25rem 0 0; font-weight:600; }
    .db-day-body { padding:1.35rem 1.85rem 1.6rem; max-height:62vh; overflow-y:auto; }
    .db-day-event { padding:1.1rem 1.2rem; border:1px solid var(--line); border-radius:1rem;
        background:linear-gradient(135deg, #fbfdfd, #f7fafa);
        transition:all .18s ease; margin-bottom:.75rem;
        text-decoration:none; color:inherit; display:block; }
    .db-day-event:last-child { margin-bottom:0; }
    .db-day-event:hover { border-color:var(--primary); background:#fff;
        box-shadow:0 10px 24px -8px rgba(15,60,73,.15); color:inherit; transform:translateY(-1px); }
    .db-day-event-top { display:flex; align-items:flex-start; justify-content:space-between;
        gap:.75rem; margin-bottom:.55rem; flex-wrap:wrap; }
    .db-day-event-nm { font-size:.92rem; font-weight:800; color:var(--ink); margin:0;
        display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
    .db-day-event-total { font-size:.92rem; font-weight:800; color:var(--primary-dark); }
    .db-day-event-meta { display:flex; flex-wrap:wrap; gap:.4rem; margin-top:.4rem; }
    .db-day-meta-chip { display:inline-flex; align-items:center; gap:.35rem;
        background:#fff; border:1px solid var(--line); color:#475569;
        font-size:.72rem; font-weight:700; padding:.32rem .65rem; border-radius:.5rem; }
    .db-day-meta-chip i { color:var(--primary); font-size:.85em; }
    .db-day-event-key { font-size:.72rem; color:var(--muted); font-weight:600; margin-top:.5rem;
        display:flex; align-items:flex-start; gap:.4rem; }
    .db-day-event-key i { color:var(--soft); margin-top:.1rem; }
    .db-day-empty { text-align:center; padding:2.5rem 1rem; }
    .db-day-empty .ic { display:grid; place-items:center; width:3.75rem; height:3.75rem;
        margin:0 auto 1.1rem; border-radius:1.1rem;
        background:var(--surface-2); color:var(--soft); font-size:1.45rem; }
    .db-day-empty p { font-size:.85rem; color:var(--muted); margin:0; }
</style>

@php
    // Pertahankan query string lain di halaman ini (mis. status_bulan/okupansi di dashboard
    // Admin) saat navigasi kalender — supaya klik panah kalender tidak me-reset kontrol lain.
    $calQ = request()->except(['view', 'tanggal', 'bulan']);
@endphp
<div class="xcard db-cal-wrap" id="kalender" data-reveal>
    <div class="db-cal-head">
        <div class="db-cal-head-left">
            <a href="{{ route($routeDashboard, array_merge($calQ, ['view' => $view])) }}#kalender" class="db-cal-hari-ini" data-tip="Kembali ke hari ini">Hari ini</a>
            <div class="db-cal-nav-group">
                <a href="{{ route($routeDashboard, array_merge($calQ, ['view' => $view, $paramNav => $navPrev])) }}#kalender" class="db-cal-nav-btn" data-tip="Sebelumnya">
                    <i class="bi bi-chevron-left"></i>
                </a>
                {{-- Input tanggal asli DITUMPUK TRANSPARAN persis di atas label (bukan disembunyikan
                     lalu dipanggil lewat showPicker() dari tombol terpisah) — klik langsung
                     jatuh ke elemen date-input aslinya, jadi kalender bawaan browser terbuka
                     lewat perilaku native biasa, bukan trik JS yang dukungannya tidak seragam
                     di semua browser. --}}
                <div class="db-cal-title-wrap" data-tip="Klik untuk pilih tanggal">
                    <span class="db-cal-title">{{ $labelHeader }}</span>
                    <input type="date" id="dbCalDatePicker" class="db-cal-date-overlay" value="{{ $tanggalAcuan->toDateString() }}" aria-label="Pilih tanggal">
                </div>
                <a href="{{ route($routeDashboard, array_merge($calQ, ['view' => $view, $paramNav => $navNext])) }}#kalender" class="db-cal-nav-btn" data-tip="Berikutnya">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </div>
        </div>
        <div class="db-cal-view-switch">
            <a href="{{ route($routeDashboard, array_merge($calQ, ['view' => 'hari', 'tanggal' => $tanggalAcuan->toDateString()])) }}#kalender"
               class="{{ $view === 'hari' ? 'active' : '' }}">Hari</a>
            <a href="{{ route($routeDashboard, array_merge($calQ, ['view' => 'minggu', 'tanggal' => $tanggalAcuan->toDateString()])) }}#kalender"
               class="{{ $view === 'minggu' ? 'active' : '' }}">Minggu</a>
            <a href="{{ route($routeDashboard, array_merge($calQ, ['view' => 'bulan', 'bulan' => $tanggalAcuan->format('Y-m')])) }}#kalender"
               class="{{ $view === 'bulan' ? 'active' : '' }}">Bulan</a>
        </div>
    </div>

    {{-- ─── MODE BULAN ─── --}}
    @if ($view === 'bulan')
        @php
            $bulanAktifRender = $tanggalAcuan->copy()->startOfMonth();
            $totalHariGrid = $rentangAwal->diffInDays($rentangAkhir) + 1;
        @endphp
        <div class="db-cal-grid">
            <div class="db-cal-weekdays">
                @foreach (['Sen','Sel','Rab','Kam','Jum','Sab','Min'] as $wd)
                    <div class="db-cal-weekday">{{ $wd }}</div>
                @endforeach
            </div>
            <div class="db-cal-days">
                @for ($i = 0; $i < $totalHariGrid; $i++)
                    @php
                        $tgl = $rentangAwal->copy()->addDays($i);
                        $tglKey = $tgl->toDateString();
                        $isCurrentMonth = $tgl->month === $bulanAktifRender->month;
                        $isToday = $tgl->isToday();
                        $eventsHariItu = $reservasiByDate->get($tglKey, collect());
                        $isClickable = $eventsHariItu->count() > 0;
                    @endphp
                    <div class="db-cal-day {{ !$isCurrentMonth ? 'other-month' : '' }} {{ $isToday ? 'today' : '' }} {{ $isClickable ? 'clickable' : '' }}"
                         @if ($isClickable) data-day-key="{{ $tglKey }}" @endif>
                        <span class="dnum">{{ $tgl->format('j') }}</span>
                        @if ($eventsHariItu->count() > 0)
                            <div class="db-cal-events-month">
                                @foreach ($eventsHariItu->take(2) as $ev)
                                    @php
                                        $fs = $ev->tarifSewa->fasilitas;
                                        $namaSingkat = \Illuminate\Support\Str::limit($fs->nama_fasilitas, 10);
                                        $jam = $ev->jam_mulai ? substr($ev->jam_mulai, 0, 5) : '';
                                        $statusEvLabel = $chipLabel[$ev->status_reservasi->value] ?? $ev->status_reservasi->value;
                                        $jamAtauDurasi = $ev->jam_mulai
                                            ? substr($ev->jam_mulai, 0, 5) . '–' . substr($ev->jam_selesai, 0, 5) . ' WIB'
                                            : $ev->durasi . ' ' . strtolower($ev->tarifSewa->jenisSewa->satuan->value ?? '');
                                        $tipMonth = "{$fs->nama_fasilitas} · Lt {$fs->lantai->nomor_lantai} · {$jamAtauDurasi} · {$statusEvLabel}";
                                    @endphp
                                    <span class="db-cal-event-month" data-tip="{{ $tipMonth }}"
                                          style="background:{{ $warnaEvent['bg'] }}; color:{{ $warnaEvent['text'] }}; border-color:{{ $warnaEvent['border'] }};">
                                        @if ($jam)<span class="ev-time">{{ $jam }}</span> @endif{{ $namaSingkat }}
                                    </span>
                                @endforeach
                                @if ($eventsHariItu->count() > 2)
                                    <div class="db-cal-more">+{{ $eventsHariItu->count() - 2 }} lagi</div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endfor
            </div>
        </div>

    {{-- ─── MODE MINGGU / HARI (time grid) ─── --}}
    @else
        @php
            $kolomHari = $view === 'hari'
                ? collect([$tanggalAcuan->copy()])
                : collect(range(0, 6))->map(fn ($i) => $rentangAwal->copy()->addDays($i));

            $gridCols = $view === 'hari'
                ? '4.5rem 1fr'
                : '4.5rem repeat(' . $kolomHari->count() . ', minmax(0, 1fr))';

            $eventsWithTime = $reservasiKalender->filter(fn ($r) => $r->jam_mulai);
        @endphp
        <div class="db-cal-time-wrap">
            <div class="db-cal-time-inner">
                {{-- Header hari --}}
                <div class="db-cal-time-head" style="grid-template-columns:{{ $gridCols }};">
                    <div class="db-cal-time-head-cell">WIB</div>
                    @foreach ($kolomHari as $hari)
                        @php $isToday = $hari->isToday(); @endphp
                        <div class="db-cal-time-head-cell {{ $isToday ? 'today' : '' }}">
                            <div class="wd">{{ $hari->translatedFormat('D') }}</div>
                            <div class="dnum">{{ $hari->format('j') }}</div>
                        </div>
                    @endforeach
                </div>

                {{-- Time grid body --}}
                <div class="db-cal-time-body" style="grid-template-columns:{{ $gridCols }};">
                    {{-- Kolom label jam --}}
                    <div class="db-cal-time-labels">
                        @for ($h = $jamMulaiGrid; $h < $jamAkhirGrid; $h++)
                            <div class="db-cal-hour-label" style="height:{{ $tinggiPerJam }}rem;">
                                {{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00
                            </div>
                        @endfor
                    </div>

                    @foreach ($kolomHari as $hari)
                        @php
                            $isToday = $hari->isToday();
                            $eventsHari = $eventsWithTime->filter(fn ($r) => $r->tanggal_mulai->toDateString() === $hari->toDateString());
                        @endphp
                        <div class="db-cal-time-col {{ $isToday ? 'today' : '' }}">
                            @for ($h = $jamMulaiGrid; $h < $jamAkhirGrid; $h++)
                                <div class="db-cal-hour-row" style="height:{{ $tinggiPerJam }}rem;"></div>
                            @endfor

                            {{-- Event yang jamnya beririsan (mis. 2 ruangan dipesan bersamaan) disusun
                                 berdampingan dalam kolom terpisah (lihat $layoutEventsOverlap) supaya
                                 tetap terlihat sebagai 2 kartu yang jelas, bukan menyatu jadi satu. --}}
                            @foreach ($layoutEventsOverlap($eventsHari) as $p)
                                @php
                                    $ev = $p['ev'];
                                    $fs = $ev->tarifSewa->fasilitas;
                                    $topRem = ($p['start'] - $jamMulaiGrid) * $tinggiPerJam;
                                    $heightRem = max(1.75, ($p['end'] - $p['start']) * $tinggiPerJam);
                                    $widthPct = 100 / $p['cols'];
                                    $leftPct = $p['col'] * $widthPct;
                                @endphp
                                @php
                                    $statusEvLabel = $chipLabel[$ev->status_reservasi->value] ?? $ev->status_reservasi->value;
                                    $tipEvent = "{$fs->nama_fasilitas} · Lt {$fs->lantai->nomor_lantai} · " . substr($ev->jam_mulai, 0, 5) . '–' . substr($ev->jam_selesai, 0, 5) . " WIB · {$statusEvLabel}";
                                @endphp
                                <a href="{{ route($routeDetail, $ev->kode_reservasi) }}"
                                   class="db-cal-time-event"
                                   style="top:{{ $topRem }}rem; height:{{ $heightRem }}rem;
                                          left:calc({{ $leftPct }}% + 3px); width:calc({{ $widthPct }}% - 6px);
                                          background:{{ $warnaEvent['bg'] }}; color:{{ $warnaEvent['text'] }};
                                          border-left-color:{{ $warnaEvent['accent'] }};"
                                   data-tip="{{ $tipEvent }}">
                                    <div class="ev-time-label">{{ substr($ev->jam_mulai,0,5) }} – {{ substr($ev->jam_selesai,0,5) }}</div>
                                    <div class="ev-nm">{{ $fs->nama_fasilitas }}</div>
                                    @if ($p['cols'] > 1)
                                        <div class="ev-lantai"><i class="bi bi-geo-alt"></i>Lt {{ $fs->lantai->nomor_lantai ?? '-' }}</div>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endforeach

                    @if ($eventsWithTime->isEmpty())
                        <div class="db-cal-time-empty">
                            <i class="bi bi-calendar-x"></i>
                            <p>Tidak ada reservasi pada rentang ini.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

{{-- ══════════════ FLOATING CARD (detail hari, muncul dekat tanggal yang diklik) ══════════════ --}}
<div class="db-floating-card" id="dbFloatingCard">
    <div class="db-day-head">
        <div class="db-day-head-l">
            <span class="db-day-head-ic"><i class="bi bi-calendar-check-fill"></i></span>
            <div>
                <h5 class="db-day-head-title">Detail Reservasi</h5>
                <p class="db-day-head-sub" id="dbFcSub">Pilih hari di kalender</p>
            </div>
        </div>
        <button type="button" class="db-day-close" id="dbFcClose" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="db-day-body" id="dbFcBody"></div>
</div>

<script>
    (function () {
        const eventsByDate = @json($eventsByDate);
        const card = document.getElementById('dbFloatingCard');
        if (!card) return;
        const fcSub = document.getElementById('dbFcSub');
        const fcBody = document.getElementById('dbFcBody');
        let lastAnchor = null;

        const escapeHtml = (s) => {
            if (s === null || s === undefined) return '';
            return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        };

        const eventCardHtml = (ev) => {
            const jam = ev.jamMulai ? `${ev.jamMulai}–${ev.jamSelesai} WIB` : `${ev.durasi} ${ev.satuan.toLowerCase()}`;
            return `
                <a href="${ev.url}" class="db-day-event">
                    <div class="db-day-event-top">
                        <div class="db-day-event-nm">${escapeHtml(ev.nama)}<span class="chip ${ev.statusChip}">${escapeHtml(ev.statusLabel)}</span></div>
                        <div class="db-day-event-total">Rp ${ev.total}</div>
                    </div>
                    <div class="db-day-event-meta">
                        <span class="db-day-meta-chip"><i class="bi bi-clock"></i>${escapeHtml(jam)}</span>
                        <span class="db-day-meta-chip"><i class="bi bi-geo-alt"></i>Lantai ${escapeHtml(String(ev.lantai))}</span>
                        <span class="db-day-meta-chip"><i class="bi bi-tag"></i>${escapeHtml(ev.kategori)}</span>
                        <span class="db-day-meta-chip"><i class="bi bi-upc"></i>${escapeHtml(ev.kode)}</span>
                    </div>
                    ${ev.keperluan ? `<div class="db-day-event-key"><i class="bi bi-chat-square-text"></i>${escapeHtml(ev.keperluan)}</div>` : ''}
                </a>`;
        };

        const positionCard = (anchor) => {
            const r = anchor.getBoundingClientRect();
            const margin = 12;
            card.style.visibility = 'hidden';
            card.classList.add('show');
            const cw = card.offsetWidth;
            const ch = card.offsetHeight;
            card.classList.remove('show');

            let left = r.left + r.width / 2 - cw / 2;
            left = Math.max(margin, Math.min(left, window.innerWidth - cw - margin));

            let top = r.bottom + 10;
            if (top + ch > window.innerHeight - margin) {
                top = r.top - ch - 10;
                if (top < margin) top = Math.max(margin, (window.innerHeight - ch) / 2);
            }
            card.style.left = `${left}px`;
            card.style.top = `${top}px`;
            card.style.visibility = '';
        };

        const showCard = (anchor, sub, bodyHtml) => {
            lastAnchor = anchor;
            fcSub.textContent = sub;
            fcBody.innerHTML = bodyHtml;
            positionCard(anchor);
            requestAnimationFrame(() => card.classList.add('show'));
        };

        const hideCard = () => {
            card.classList.remove('show');
            lastAnchor = null;
        };

        document.getElementById('dbFcClose').addEventListener('click', hideCard);
        document.addEventListener('click', (e) => {
            if (!card.classList.contains('show')) return;
            if (card.contains(e.target)) return;
            if (lastAnchor && lastAnchor.contains(e.target)) return;
            hideCard();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') hideCard();
        });
        window.addEventListener('scroll', () => { if (card.classList.contains('show')) hideCard(); }, true);
        window.addEventListener('resize', () => { if (card.classList.contains('show')) hideCard(); });

        document.querySelectorAll('.db-cal-day.clickable').forEach((cell) => {
            cell.addEventListener('click', () => {
                const key = cell.dataset.dayKey;
                const events = eventsByDate[key] || [];
                if (events.length === 0) {
                    showCard(cell, 'Tidak ada reservasi.', `<div class="db-day-empty"><div class="ic"><i class="bi bi-calendar-x"></i></div><p>Tidak ada reservasi pada hari ini.</p></div>`);
                    return;
                }
                const sub = events[0].tanggal + ' · ' + events.length + ' reservasi';
                showCard(cell, sub, events.map(eventCardHtml).join(''));
            });
        });
    })();
</script>

<script>
    {{-- Label tanggal di tengah tombol ‹ › SEKARANG BISA DIKLIK — membuka date-picker bawaan
         browser (lewat input tanggal transparan yang ditumpuk persis di atasnya) supaya bisa
         langsung lompat ke tanggal tertentu, bukan cuma geser satu-satu lewat ‹ ›. --}}
    (function () {
        const picker = document.getElementById('dbCalDatePicker');
        if (!picker) return;

        // Klik pada input date NATIVE cuma memfokuskan field (kursor teks) kalau yang diklik
        // area teksnya — kalender visualnya cuma terbuka kalau klik TEPAT di ikon kalender
        // kecil bawaan browser. Karena overlay ini menutupi SELURUH label (bukan cuma ikon
        // sekecil itu), showPicker() dipanggil manual di sini supaya klik di mana pun pada
        // label tetap membuka kalendernya — dipanggil dari handler klik pada input aslinya
        // sendiri, jadi tetap dihitung gesture terpercaya oleh browser.
        picker.addEventListener('click', () => {
            if (typeof picker.showPicker === 'function') {
                try { picker.showPicker(); } catch (e) { /* browser lama: biarkan fokus teks biasa */ }
            }
        });

        picker.addEventListener('change', () => {
            if (!picker.value) return;
            const url = new URL(@json(route($routeDashboard, $calQ)), window.location.origin);
            @if ($view === 'bulan')
                url.searchParams.set('view', 'bulan');
                url.searchParams.set('bulan', picker.value.slice(0, 7));
            @else
                url.searchParams.set('view', @json($view));
                url.searchParams.set('tanggal', picker.value);
            @endif
            url.hash = 'kalender';
            window.location.href = url.toString();
        });
    })();
</script>
