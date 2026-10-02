@extends('layouts.reservasi')
@section('pantau_status', '1')
@section('title', 'Hasil Cek Status')

@php
    use Illuminate\Support\Str;

    $chipClass = [
        'Menunggu' => 'menunggu', 'Disetujui' => 'disetujui', 'Ditolak' => 'ditolak',
        'Selesai' => 'selesai', 'Dibatalkan' => 'dibatalkan', 'Kadaluwarsa' => 'kadaluwarsa',
    ];
    $chipLabel = [
        'Menunggu' => 'Menunggu Verifikasi', 'Disetujui' => 'Disetujui', 'Ditolak' => 'Ditolak',
        'Selesai' => 'Selesai', 'Dibatalkan' => 'Dibatalkan', 'Kadaluwarsa' => 'Kadaluwarsa',
    ];
@endphp

@section('content')
<style>
    /* Token warna tambahan — SAMA seperti yang dipakai halaman Reservasi Saya milik Pemesan
       (layouts.customer), tapi layout publik (layouts.reservasi) belum mendefinisikannya.
       Ditaruh lokal di sini (bukan di layout bersama) supaya tidak mengubah halaman publik lain
       yang belum tentu sudah disesuaikan. */
    :root {
        --primary-darker:#0c3648;
        --rose:#e11d48; --rose-tint:#fef2f4;
        --emerald:#059669; --emerald-soft:#d1fae5;
        --surface-2:#eef3f6; --line-soft:#eef2f6;
        --amber-tint:#fef9e7;
    }

    /* Chip status — SAMA seperti dipakai halaman Reservasi Saya milik Pemesan (belum ada di
       layout publik ini), dipakai untuk label status & dokumen. */
    .chip { display:inline-flex; align-items:center; gap:.4rem; font-size:.7rem; font-weight:800;
        padding:.3rem .7rem; border-radius:9999px; white-space:nowrap; letter-spacing:.01em; border:1px solid; }
    .chip::before { content:''; width:.42rem; height:.42rem; border-radius:50%; background:currentColor; flex:none; }
    .chip.menunggu { background:var(--amber-tint); color:#a16207; border-color:#fde68a; }
    .chip.menunggu::before { background:#f59e0b; }
    .chip.disetujui { background:var(--emerald-soft); color:#047857; border-color:#a7f3d0; }
    .chip.disetujui::before { background:var(--emerald); }
    .chip.ditolak { background:var(--rose-tint); color:#be123c; border-color:#fecdd3; }
    .chip.ditolak::before { background:var(--rose); }
    .chip.dibatalkan { background:#f1f5f9; color:#475569; border-color:#e2e8f0; }
    .chip.dibatalkan::before { background:#94a3b8; }
    .chip.selesai { background:#dbeafe; color:#1d4ed8; border-color:#bfdbfe; }
    .chip.selesai::before { background:#3b82f6; }
    .chip.kadaluwarsa { background:#f3e8ff; color:#7c3aed; border-color:#e9d5ff; }
    .chip.kadaluwarsa::before { background:#8b5cf6; }

    /* ══════ BREADCRUMB ══════ */
    .rs-crumb { display:flex; align-items:center; gap:.35rem; font-size:.75rem; font-weight:700; margin-bottom:.85rem; }
    .rs-crumb a { color:var(--primary-dark); text-decoration:none; }
    .rs-crumb a:hover { text-decoration:underline; }
    .rs-crumb i { color:var(--soft); font-size:.7em; }
    .rs-crumb .cur { color:var(--muted); font-weight:600; }

    /* ══════ HERO RINGKASAN PENCARIAN — gaya SAMA dengan page-head Pemesan ══════ */
    .rs-hero-stats { display:flex; gap:.6rem; flex-wrap:wrap; }
    .rs-hero-stat { background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.22);
        border-radius:1rem; padding:.6rem 1.1rem; min-width:8rem; backdrop-filter:blur(6px); }
    .rs-hero-stat .v { font-size:1.15rem; font-weight:800; }
    .rs-hero-stat .k { font-size:.68rem; color:#d3ecf1; }
    .btn-salin { background:rgba(255,255,255,.16); border:1px solid rgba(255,255,255,.28); color:#fff;
        border-radius:.7rem; font-size:.78rem; font-weight:700; transition:background .15s ease; }
    .btn-salin:hover { background:rgba(255,255,255,.26); color:#fff; }
    .rs-kode { font-family:'Plus Jakarta Sans',sans-serif; letter-spacing:.06em; }

    /* ══════ DETAIL CARD — identik dengan kartu detail reservasi milik Pemesan ══════ */
    .rs-detail { padding:0; overflow:hidden; margin-bottom:1.25rem; }
    .rs-detail-head { display:grid; grid-template-columns:38% 1fr; gap:0; }
    @media (max-width: 767.98px) { .rs-detail-head { grid-template-columns:1fr; } }

    .rs-detail-img { position:relative; min-height:16rem; background:var(--surface); overflow:hidden; }
    .rs-detail-img img, .rs-detail-img .rs-carousel, .rs-detail-img .rs-carousel .carousel-inner,
    .rs-detail-img .rs-carousel .carousel-item { width:100%; height:100%; position:absolute; inset:0; }
    .rs-detail-img img { object-fit:cover; }
    .rs-detail-img .kat-badge { position:absolute; top:1rem; left:1rem; z-index:5;
        display:inline-flex; align-items:center; gap:.4rem; font-size:.7rem; font-weight:800;
        background:rgba(255,255,255,.95); padding:.4rem .75rem; border-radius:9999px;
        color:var(--primary-dark); backdrop-filter:blur(6px); box-shadow:0 4px 10px rgba(0,0,0,.15);
        pointer-events:none; }
    .rs-detail-img .lt-badge { position:absolute; bottom:1rem; left:1rem; z-index:5;
        display:inline-flex; align-items:center; gap:.35rem; font-size:.65rem; font-weight:800;
        background:rgba(15,23,42,.75); color:#fff; padding:.35rem .65rem; border-radius:.5rem;
        backdrop-filter:blur(6px); text-transform:uppercase; letter-spacing:.06em; pointer-events:none; }
    .rs-carousel .carousel-indicators { margin-bottom:.5rem; z-index:6; }
    .rs-carousel .carousel-indicators [data-bs-target] { width:.45rem; height:.45rem; border-radius:50%; background:#fff; opacity:.6; }
    .rs-carousel .carousel-indicators .active { opacity:1; }
    .rs-carousel .carousel-control-prev, .rs-carousel .carousel-control-next { width:2.4rem; opacity:0; transition:opacity .15s ease; z-index:6; }
    .rs-carousel:hover .carousel-control-prev, .rs-carousel:hover .carousel-control-next { opacity:1; }
    /* Layar sentuh tidak punya hover — kontrol galeri harus selalu terlihat. */
    @media (hover: none) { .rs-carousel .carousel-control-prev, .rs-carousel .carousel-control-next { opacity:.9; } }
    .fp-zoomable { cursor:zoom-in; transition:filter .15s ease; }
    .fp-zoomable:hover { filter:brightness(.92); }

    .rs-detail-info { padding:1.5rem 1.75rem; display:flex; flex-direction:column; gap:1rem; }
    .rs-detail-info-top { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
    .rs-detail-info h1 { font-size:1.6rem; font-weight:800; color:var(--ink); margin:0 0 .35rem; letter-spacing:-.02em; line-height:1.15; }
    .rs-detail-info .kode-row { display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; margin-top:.35rem; }
    .rs-detail-info .kode-row .kode { font-size:.7rem; color:var(--muted); font-weight:700;
        display:inline-flex; align-items:center; gap:.3rem; }
    .rs-detail-info .kode-row .kode i { color:var(--soft); font-size:.9em; }
    .rs-detail-info .price-col { text-align:right; flex:none; }
    .rs-detail-info h1 .chip { margin-left:.4rem; vertical-align:middle; }
    .rs-detail-info .kode-row .kode { overflow-wrap:anywhere; }
    .rs-detail-info .price-col .lbl { font-size:.62rem; font-weight:800; letter-spacing:.08em;
        text-transform:uppercase; color:var(--soft); }
    .rs-detail-info .price-col .val { font-size:1.5rem; font-weight:800; color:var(--primary-dark);
        letter-spacing:-.02em; line-height:1.1; }
    .rs-detail-info .price-col .kalk { font-size:.7rem; color:var(--muted); margin-top:.15rem; }

    /* Tracker horizontal */
    .rs-tracker { display:flex; align-items:flex-start; margin:.75rem 0; padding:1rem 0; }
    .rs-tstep { flex:1 1 0; text-align:center; position:relative; min-width:0; }
    .rs-tstep .tdot { width:2.4rem; height:2.4rem; border-radius:50%; display:grid; place-items:center;
        margin:0 auto .4rem; background:#e6edf3; color:#8a97a5; font-size:1rem; position:relative; z-index:1;
        border:3px solid #fff; box-shadow:0 0 0 1px #e0e8ef; transition:all .25s ease; }
    .rs-tstep .tlabel { font-size:.72rem; font-weight:800; color:#8a97a5; overflow-wrap:anywhere; padding:0 .15rem; }
    .rs-tstep::before { content:''; position:absolute; top:1.2rem; left:-50%; width:100%; height:3px; background:#e2e9f0; z-index:0; }
    .rs-tstep:first-child::before { display:none; }
    .rs-tstep.done .tdot { background:linear-gradient(135deg, var(--emerald), #10b981); color:#fff;
        box-shadow:0 6px 14px rgba(5,150,105,.4); }
    .rs-tstep.done .tlabel { color:var(--emerald); }
    .rs-tstep.done::before { background:var(--emerald); }
    .rs-tstep.now .tdot { background:linear-gradient(135deg, var(--primary-dark), var(--primary)); color:#fff;
        box-shadow:0 6px 16px rgba(23,107,135,.45); animation:pulse-now 1.6s infinite; }
    .rs-tstep.now .tlabel { color:var(--primary-dark); }
    .rs-tstep.now::before { background:var(--primary); }
    .rs-tstep.bad .tdot { background:linear-gradient(135deg, var(--rose), #f43f5e); color:#fff;
        box-shadow:0 6px 14px rgba(225,29,72,.4); }
    .rs-tstep.bad .tlabel { color:var(--rose); }
    .rs-tstep.bad::before { background:var(--rose); }
    .rs-tstep.off .tdot { background:#94a3b8; color:#fff; }
    .rs-tstep.off .tlabel { color:#64748b; }
    .rs-tstep.off::before { background:#94a3b8; }
    @keyframes pulse-now { 0%,100% { transform:scale(1); } 50% { transform:scale(1.08); } }

    .rs-meta-chips { display:flex; flex-wrap:wrap; gap:.4rem; }
    .rs-meta-chip { display:inline-flex; align-items:center; gap:.35rem;
        background:var(--surface); border:1px solid var(--line); color:#475569;
        font-size:.72rem; font-weight:700; padding:.35rem .7rem; border-radius:.55rem; }
    .rs-meta-chip i { color:var(--primary); font-size:.85em; }

    /* ══════ KUITANSI ══════ */
    .rs-kuitansi { padding:1.5rem 1.75rem; border-top:1px solid var(--line-soft);
        background:linear-gradient(180deg, var(--surface), #fff); }
    .rs-kuitansi-head { display:flex; align-items:flex-start; gap:.75rem; margin-bottom:1rem; flex-wrap:wrap; }
    .rs-kuitansi-head .body { flex:1 1 10rem; min-width:0; }
    .rs-kuitansi-head .ic { display:grid; place-items:center; width:2.65rem; height:2.65rem; border-radius:.75rem;
        background:linear-gradient(135deg, var(--primary-dark), var(--primary));
        color:#fff; font-size:1rem; flex:none; box-shadow:0 6px 14px -3px rgba(23,107,135,.45); }
    .rs-kuitansi-head .body h3 { font-size:.95rem; font-weight:800; color:var(--ink); margin:0 0 .1rem; }
    .rs-kuitansi-head .body p { font-size:.7rem; color:var(--muted); margin:0; letter-spacing:.02em; }
    .rs-kuitansi-head .side { margin-left:auto; text-align:right; }
    .rs-kuitansi-head .side small { display:block; font-size:.6rem; font-weight:800;
        letter-spacing:.08em; text-transform:uppercase; color:var(--soft); }
    .rs-kuitansi-head .side .kode-tx { font-size:.85rem; font-weight:800; color:var(--ink); }

    .rs-line { display:flex; justify-content:space-between; gap:.75rem; padding:.75rem 0;
        border-bottom:1px dashed var(--line); align-items:flex-start; }
    .rs-line:last-child { border-bottom:0; }
    .rs-line .lhs { flex:1; min-width:0; }
    .rs-line .lhs .nm { font-size:.85rem; font-weight:700; color:var(--ink); margin-bottom:.15rem; }
    .rs-line .lhs .sub { font-size:.7rem; color:var(--muted); }
    .rs-line .rhs { text-align:right; flex:none; }
    .rs-line .rhs .val { font-size:.9rem; font-weight:800; color:var(--ink); }

    .rs-total-row { display:flex; align-items:center; justify-content:space-between; gap:1rem;
        margin-top:1rem; padding:1rem 1.25rem; border-radius:1rem;
        background:linear-gradient(135deg, var(--primary-darker), var(--primary));
        color:#fff; box-shadow:0 12px 24px -8px rgba(23,107,135,.4); flex-wrap:wrap; }
    .rs-total-row .lbl-t { font-size:.7rem; font-weight:800; letter-spacing:.08em;
        text-transform:uppercase; color:#a7f3d0; display:block; margin-bottom:.15rem; }
    .rs-total-row .val-t { font-size:1.6rem; font-weight:800; color:#fff; letter-spacing:-.02em; line-height:1.1; }
    .rs-total-row .chip-t { font-size:.7rem; font-weight:800; padding:.4rem .8rem;
        border-radius:9999px; background:rgba(255,255,255,.15);
        border:1px solid rgba(255,255,255,.25); color:#fff;
        display:inline-flex; align-items:center; gap:.35rem; }

    .rs-actions { display:flex; align-items:center; gap:.65rem; margin-top:1.15rem; flex-wrap:wrap; }
    .rs-actions .btn { font-size:.82rem; padding:.65rem 1.15rem; }

    .rs-alasan-box { border-radius:1rem; padding:.85rem 1rem; font-size:.82rem;
        display:flex; gap:.65rem; align-items:flex-start; margin-top:1rem; }
    .rs-alasan-box.tolak { background:var(--rose-tint); border:1px solid #fecdd3; color:#9f1239; }
    .rs-alasan-box.batal { background:var(--surface-2); border:1px solid var(--line); color:#4a5568; }
    .rs-alasan-box.approved { background:var(--emerald-soft); border:1px solid #a7f3d0; color:#065f46; }

    /* ══════ LAYAR KECIL ══════ */
    @media (max-width: 575.98px) {
        .rs-detail-img { min-height:12rem; }
        .rs-detail-info, .rs-kuitansi { padding:1.1rem 1rem; }
        .rs-detail-info h1 { font-size:1.25rem; }
        .rs-detail-info h1 .chip { margin-left:0; margin-top:.35rem; }
        .rs-detail-info .price-col { text-align:left; width:100%; }
        .rs-detail-info .price-col .val { font-size:1.3rem; }
        .rs-tracker { padding:.6rem 0; }
        .rs-tstep .tdot { width:2rem; height:2rem; font-size:.85rem; }
        .rs-tstep::before { top:1rem; }
        .rs-tstep .tlabel { font-size:.62rem; }
        .rs-kuitansi-head .side { margin-left:0; text-align:left; width:100%; }
        .rs-total-row { padding:.9rem 1rem; }
        .rs-total-row .val-t { font-size:1.25rem; }
        .rs-actions > .btn, .rs-actions > form, .rs-actions > form .btn { width:100%; }
        .rs-hero-stat { flex:1 1 7rem; min-width:0; }
        .fp-lightbox { padding:1rem; }
    }

    /* Riwayat timeline */
    .rs-hist-btn { background:none; border:0; color:var(--primary-dark);
        font-size:.75rem; font-weight:800; padding:0; display:inline-flex; align-items:center; gap:.35rem;
        margin-top:1rem; cursor:pointer; }
    .rs-hist-btn:hover { color:var(--primary-darker); }
    .rs-hist-btn[aria-expanded="true"] .bi-chevron-down { transform:rotate(180deg); }
    .rs-hist-btn .bi-chevron-down { transition:transform .2s ease; }
    .rs-timeline { list-style:none; margin:1rem 0 0; padding:0 0 0 1.15rem; border-left:2px solid var(--line); }
    .rs-timeline li { position:relative; padding:0 0 .85rem .95rem; }
    .rs-timeline li:last-child { padding-bottom:0; }
    .rs-timeline li::before { content:''; position:absolute; left:-1.52rem; top:.28rem;
        width:.72rem; height:.72rem; border-radius:50%; background:#cbd5e1;
        border:2px solid #fff; box-shadow:0 0 0 1px #cbd5e1; }
    .rs-timeline li.hijau::before { background:var(--emerald); box-shadow:0 0 0 1px var(--emerald); }
    .rs-timeline li.merah::before { background:var(--rose); box-shadow:0 0 0 1px var(--rose); }
    .rs-timeline .tgl { font-size:.7rem; color:var(--muted); }
    .rs-timeline .fw-semibold { font-weight:700; }

    /* Lightbox foto */
    .fp-lightbox { position:fixed; inset:0; z-index:2000; background:rgba(8,15,25,.9);
        display:none; align-items:center; justify-content:center; padding:2.5rem; cursor:zoom-out; }
    .fp-lightbox.show { display:flex; }
    .fp-lightbox img { max-width:100%; max-height:100%; border-radius:.9rem; box-shadow:0 24px 60px rgba(0,0,0,.5); cursor:default; }
    .fp-lightbox .fp-lightbox-close { position:absolute; top:1.2rem; right:1.4rem; width:2.6rem; height:2.6rem; border-radius:50%;
        border:none; background:rgba(255,255,255,.15); color:#fff; font-size:1.3rem;
        display:grid; place-items:center; cursor:pointer; transition:background .15s ease; }
    .fp-lightbox .fp-lightbox-close:hover { background:rgba(255,255,255,.28); }
</style>

    <div class="rs-crumb" data-reveal>
        <a href="{{ route('cek-status.form') }}">Cek Status</a>
        <i class="bi bi-chevron-right"></i>
        <span class="cur">{{ $kode }}</span>
    </div>

    @if ($reservasi->isEmpty())
        {{-- ===== Tidak ditemukan ===== --}}
        <div class="xcard p-5 text-center mx-auto" style="max-width:560px;">
            <div class="icon-tile mx-auto mb-3" style="background:#fff4d6; color:#9a6b00;"><i class="bi bi-search"></i></div>
            <h1 class="h5 mb-2">Kode <span style="color:var(--primary)">{{ $kode }}</span> tidak ditemukan</h1>
            <p class="text-muted small mb-4">Pastikan kode ditulis lengkap, yaitu 2 huruf diikuti 3 angka, contoh <strong>RS186</strong>. Huruf besar dan huruf kecil tidak berpengaruh.</p>
            <div class="d-flex justify-content-center gap-2 flex-wrap">
                <a href="{{ route('cek-status.form') }}" class="btn btn-brand px-4"><i class="bi bi-arrow-counterclockwise me-1"></i>Cari Kembali</a>
                <a href="{{ route('reservasi.index') }}" class="btn btn-brand-outline px-4">Buat Reservasi Baru</a>
            </div>
        </div>
    @else
        @php
            $pemesan = $reservasi->first()->pemesan;
            $totalSemua = $reservasi->sum('total_biaya');
            $diajukanPada = $reservasi->min('created_at');
        @endphp

        {{-- ===== Hero ringkasan pencarian ===== --}}
        <div class="page-head mb-4 p-4 p-md-5 rounded-4 text-white" style="background:linear-gradient(115deg,var(--primary-dark),var(--primary) 60%,var(--teal) 130%)" data-reveal>
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
                <div>
                    <p class="eyebrow-sm mb-2" style="color:#a9e6dd">Hasil Pencarian</p>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h1 class="h3 fw-bold mb-0 rs-kode">{{ $kode }}</h1>
                        <button type="button" class="btn btn-sm btn-salin" data-salin="{{ $kode }}">
                            <i class="bi bi-clipboard me-1"></i>Salin
                        </button>
                    </div>
                    <p class="mb-0 mt-2 small" style="color:#d3ecf1">
                        <i class="bi bi-person me-1"></i>{{ $pemesan->nama_lengkap }}
                        @if ($diajukanPada)
                            <span class="mx-1">·</span><i class="bi bi-calendar-plus me-1"></i>Diajukan {{ $diajukanPada->translatedFormat('d F Y, H:i') }} WIB
                        @endif
                    </p>
                </div>
                <div class="rs-hero-stats">
                    <div class="rs-hero-stat text-center">
                        <div class="v">{{ $reservasi->count() }}</div>
                        <div class="k">Ruangan Dipesan</div>
                    </div>
                    <div class="rs-hero-stat text-center">
                        <div class="v">Rp {{ number_format($totalSemua, 0, ',', '.') }}</div>
                        <div class="k">Total Biaya</div>
                    </div>
                </div>
            </div>
        </div>

        @foreach ($reservasi as $r)
            @php
                $status = $r->status_reservasi->value;
                $meta = \App\Support\KategoriMeta::get($r->tarifSewa->fasilitas->kategori_fasilitas);
                $bolehBatal = $status === 'Menunggu' && $r->tanggal_mulai->startOfDay()->gte(\Illuminate\Support\Carbon::today());
                $labelStatus = $chipLabel[$status] ?? $status;

                $steps = match ($status) {
                    'Ditolak'    => [['Diajukan','done','send'], ['Diverifikasi','done','hourglass-split'], ['Ditolak','bad','x-lg']],
                    'Dibatalkan' => [['Diajukan','done','send'], ['Diverifikasi','done','hourglass-split'], ['Dibatalkan','off','slash-circle']],
                    'Kadaluwarsa' => [['Diajukan','done','send'], ['Diverifikasi','bad','hourglass-split'], ['Kadaluwarsa','bad','x-lg']],
                    'Menunggu'   => [['Diajukan','done','send'], ['Diverifikasi','now','hourglass-split'], ['Disetujui','','check-lg']],
                    'Selesai'    => [['Diajukan','done','send'], ['Diverifikasi','done','hourglass-split'], ['Disetujui','done','check-lg'], ['Selesai','done','flag-fill']],
                    default      => [['Diajukan','done','send'], ['Diverifikasi','done','hourglass-split'], ['Disetujui','now','check-lg']],
                };

                $alasan = in_array($status, ['Ditolak', 'Dibatalkan', 'Kadaluwarsa'], true)
                    ? $r->riwayatStatus->last(fn ($h) => $h->status_baru->value === $status)?->keterangan
                    : null;

                $fotoList = $r->tarifSewa->fasilitas->fotoUrls();
                $fs = $r->tarifSewa->fasilitas;
                $satuanNama = $r->tarifSewa->jenisSewa->satuan->value ?? '';
            @endphp

            <div class="xcard rs-detail" data-reveal>
                {{-- HEAD: foto + info + tracker --}}
                <div class="rs-detail-head">
                    <div class="rs-detail-img">
                        @if (count($fotoList) > 1)
                            <div id="rsCarousel{{ $r->id_reservasi }}" class="carousel slide rs-carousel">
                                <div class="carousel-inner">
                                    @foreach ($fotoList as $i => $src)
                                        <div class="carousel-item @if ($i === 0) active @endif">
                                            <img src="{{ $src }}" class="fp-zoomable" data-zoom-src="{{ $src }}" alt="{{ $fs->nama_fasilitas }} — foto {{ $i + 1 }}">
                                        </div>
                                    @endforeach
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#rsCarousel{{ $r->id_reservasi }}" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Sebelumnya</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#rsCarousel{{ $r->id_reservasi }}" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Berikutnya</span>
                                </button>
                                <div class="carousel-indicators">
                                    @foreach ($fotoList as $i => $src)
                                        <button type="button" data-bs-target="#rsCarousel{{ $r->id_reservasi }}" data-bs-slide-to="{{ $i }}" @if ($i === 0) class="active" @endif aria-label="Foto {{ $i + 1 }}"></button>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <img src="{{ $fotoList[0] }}" class="fp-zoomable" data-zoom-src="{{ $fotoList[0] }}" alt="{{ $fs->nama_fasilitas }}">
                        @endif
                        <span class="kat-badge">
                            <i class="bi {{ $meta['ikon'] ?? 'bi-door-open' }}"></i>
                            {{ $fs->kategori_fasilitas }}
                        </span>
                        @if ($fs->lantai)
                            <span class="lt-badge">Lantai {{ $fs->lantai->nomor_lantai }}</span>
                        @endif
                    </div>
                    <div class="rs-detail-info">
                        <div class="rs-detail-info-top">
                            <div style="min-width:0;">
                                <h1>{{ $fs->nama_fasilitas }}
                                    <span class="chip {{ $chipClass[$status] ?? '' }}">{{ $labelStatus }}</span>
                                </h1>
                                <div class="kode-row">
                                    <span class="kode"><i class="bi bi-upc"></i>{{ $r->kode_reservasi }}</span>
                                    <span class="kode"><i class="bi bi-receipt"></i>{{ $r->kode_transaksi }}</span>
                                    @if ($r->keperluan)<span class="kode"><i class="bi bi-chat-left-text"></i>{{ Str::limit($r->keperluan, 60) }}</span>@endif
                                </div>
                            </div>
                            <div class="price-col">
                                <span class="lbl">Total Biaya</span>
                                <div class="val">Rp {{ number_format($r->total_biaya, 0, ',', '.') }}</div>
                                <div class="kalk">
                                    {{ $r->durasi }} {{ strtolower($satuanNama) }} × Rp {{ number_format($r->harga_satuan, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>

                        {{-- Tracker --}}
                        <div class="rs-tracker">
                            @foreach ($steps as [$lbl, $state, $ic])
                                <div class="rs-tstep {{ $state }}">
                                    <div class="tdot"><i class="bi bi-{{ $ic }}"></i></div>
                                    <div class="tlabel">{{ $lbl }}</div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Meta chips --}}
                        <div class="rs-meta-chips">
                            <span class="rs-meta-chip">
                                <i class="bi bi-calendar3"></i>
                                {{ $r->tanggal_mulai->translatedFormat('d F Y') }}
                                @if($r->tanggal_selesai->ne($r->tanggal_mulai)) s.d. {{ $r->tanggal_selesai->translatedFormat('d F Y') }}@endif
                            </span>
                            @if ($r->jam_mulai)
                                <span class="rs-meta-chip">
                                    <i class="bi bi-clock"></i>
                                    {{ Str::substr($r->jam_mulai,0,5) }}–{{ Str::substr($r->jam_selesai,0,5) }} WIB
                                </span>
                            @endif
                            <span class="rs-meta-chip"><i class="bi bi-tag"></i>Sewa per {{ strtolower($satuanNama) }}</span>
                            <span class="rs-meta-chip"><i class="bi bi-hourglass-split"></i>Durasi {{ $r->durasi }} {{ strtolower($satuanNama) }}</span>
                            @if ($r->jumlah_pengguna)
                                <span class="rs-meta-chip"><i class="bi bi-people"></i>{{ $r->jumlah_pengguna }} orang</span>
                            @endif
                        </div>

                        {{-- Alasan / catatan --}}
                        @if ($status === 'Ditolak')
                            <div class="rs-alasan-box tolak">
                                <i class="bi bi-exclamation-octagon-fill flex-shrink-0 mt-1"></i>
                                <div><strong>Alasan penolakan:</strong> {{ $alasan ?: 'Tidak dicantumkan. Silakan hubungi pengelola gedung untuk informasi lebih lanjut.' }}</div>
                            </div>
                        @elseif ($status === 'Dibatalkan')
                            <div class="rs-alasan-box batal">
                                <i class="bi bi-slash-circle-fill flex-shrink-0 mt-1"></i>
                                <div>Reservasi ini telah dibatalkan{{ $alasan ? '. '.$alasan : ' oleh pemesan.' }}</div>
                            </div>
                        @elseif ($status === 'Kadaluwarsa')
                            <div class="rs-alasan-box tolak">
                                <i class="bi bi-clock-history flex-shrink-0 mt-1"></i>
                                <div>Reservasi ini kedaluwarsa karena belum diproses hingga melewati jadwal pemakaian.</div>
                            </div>
                        @elseif ($status === 'Disetujui' && $r->tanggal_diproses)
                            <div class="rs-alasan-box approved">
                                <i class="bi bi-patch-check-fill flex-shrink-0 mt-1"></i>
                                <div>Disetujui pada {{ $r->tanggal_diproses->translatedFormat('d M Y H:i') }}. Mohon tunjukkan kode reservasi saat kedatangan.</div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- RINGKASAN BIAYA --}}
                <div class="rs-kuitansi">
                    <div class="rs-kuitansi-head">
                        <span class="ic"><i class="bi bi-file-earmark-text-fill"></i></span>
                        <div class="body">
                            <h3>Ringkasan Biaya</h3>
                            <p>RINCIAN BIAYA RESERVASI</p>
                        </div>
                        <div class="side">
                            <small>Nomor</small>
                            <div class="kode-tx">{{ $r->kode_transaksi }}</div>
                        </div>
                    </div>

                    <div class="rs-line">
                        <div class="lhs">
                            <div class="nm">Sewa {{ $fs->kategori_fasilitas }} — {{ $fs->nama_fasilitas }}</div>
                            <div class="sub">Tarif dasar {{ $r->durasi }} {{ strtolower($satuanNama) }}
                                @if ($r->jam_mulai) ({{ Str::substr($r->jam_mulai,0,5) }}–{{ Str::substr($r->jam_selesai,0,5) }} WIB) @endif
                            </div>
                        </div>
                        <div class="rhs">
                            <div class="val">Rp {{ number_format($r->total_biaya, 0, ',', '.') }}</div>
                        </div>
                    </div>

                    <div class="rs-total-row">
                        <div>
                            <span class="lbl-t">Total Biaya</span>
                            <span class="val-t">Rp {{ number_format($r->total_biaya, 0, ',', '.') }}</span>
                        </div>
                        <span class="chip-t">
                            <i class="bi {{ in_array($status, ['Disetujui','Selesai']) ? 'bi-check-circle-fill' : 'bi-info-circle-fill' }}"></i>
                            {{ $labelStatus }}
                        </span>
                    </div>

                    {{-- Dokumen persyaratan --}}
                    @if ($r->dokumenPersyaratan->isNotEmpty())
                        <div class="mt-3 pt-3" style="border-top:1px dashed var(--line);">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="small text-muted fw-bold"><i class="bi bi-folder2-open me-1"></i>Dokumen:</span>
                                @foreach ($r->dokumenPersyaratan as $dok)
                                    @php
                                        $vs = $dok->status_verifikasi->value;
                                        $vClass = ['Menunggu' => 'menunggu', 'Valid' => 'disetujui', 'Tidak Valid' => 'ditolak'][$vs] ?? 'menunggu';
                                    @endphp
                                    <span class="chip {{ $vClass }}" title="{{ $dok->nama_file }} ({{ $vs }})">{{ $dok->jenis_dokumen }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Aksi bawah --}}
                    <div class="rs-actions">
                        @if ($r->buktiTersedia())
                            <a href="{{ route('cek-status.bukti-reservasi', $r->kode_reservasi) }}" class="btn btn-brand">
                                <i class="bi bi-file-earmark-arrow-down me-1"></i>Unduh Bukti Reservasi
                            </a>
                        @endif
                        @if ($bolehBatal)
                            <form method="POST" action="{{ route('reservasi.batalkan', $r->kode_reservasi) }}"
                                  data-confirm="Reservasi {{ $r->kode_reservasi }} akan dibatalkan dan tidak dapat dikembalikan."
                                  data-confirm-title="Batalkan reservasi ini?" data-icon="warning"
                                  data-confirm-text="Ya, batalkan" data-confirm-color="#e11d48">
                                @csrf
                                <input type="hidden" name="kode" value="{{ $kode }}">
                                <button class="btn btn-brand-outline" style="color:var(--rose); border-color:var(--rose);">
                                    <i class="bi bi-x-circle me-1"></i>Batalkan
                                </button>
                            </form>
                        @endif
                    </div>

                    {{-- Riwayat status --}}
                    @if ($r->riwayatStatus->isNotEmpty())
                        <button class="rs-hist-btn" type="button" data-bs-toggle="collapse" data-bs-target="#riwayat-{{ $r->id_reservasi }}" aria-expanded="false">
                            <i class="bi bi-clock-history"></i>Lihat Riwayat Status <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="collapse" id="riwayat-{{ $r->id_reservasi }}">
                            <ul class="rs-timeline">
                                <li class="hijau">
                                    <div class="fw-semibold small">Reservasi diajukan</div>
                                    <div class="tgl">{{ $r->created_at?->translatedFormat('d M Y H:i') }}</div>
                                </li>
                                @foreach ($r->riwayatStatus as $h)
                                    @php
                                        $sb = $h->status_baru->value;
                                        $liClass = $sb === 'Ditolak' ? 'merah' : (in_array($sb, ['Disetujui', 'Selesai'], true) ? 'hijau' : '');
                                    @endphp
                                    <li class="{{ $liClass }}">
                                        <div class="fw-semibold small">{{ $sb === 'Menunggu' ? 'Menunggu Verifikasi' : $sb }}</div>
                                        @if ($h->keterangan)<div class="small text-muted">{{ $h->keterangan }}</div>@endif
                                        <div class="tgl">{{ $h->tanggal_perubahan?->translatedFormat('d M Y H:i') }}</div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        <div class="text-center mt-4">
            <a href="{{ route('cek-status.form') }}" class="btn btn-brand-outline btn-sm px-4"><i class="bi bi-search me-1"></i>Cek Kode Lain</a>
        </div>

        {{-- Lightbox foto — dipakai bersama semua kartu di atas --}}
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
                    el.addEventListener('click', () => buka(el.dataset.zoomSrc, el.alt));
                });
                overlay.addEventListener('click', (e) => { if (e.target === overlay) tutup(); });
                overlay.querySelector('.fp-lightbox-close').addEventListener('click', tutup);
                document.addEventListener('keydown', (e) => { if (e.key === 'Escape') tutup(); });
            })();
        </script>
    @endif
@endsection
