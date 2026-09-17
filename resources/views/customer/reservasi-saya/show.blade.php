@extends('layouts.customer')
@section('title', 'Detail Reservasi')

@php
    use Illuminate\Support\Str;

    $chipClass = [
        'Menunggu' => 'menunggu', 'Disetujui' => 'disetujui', 'Ditolak' => 'ditolak',
        'Selesai' => 'selesai', 'Dibatalkan' => 'dibatalkan', 'Kadaluwarsa' => 'kadaluwarsa',
    ];
    $chipLabel = [
        'Menunggu' => 'Diverifikasi', 'Disetujui' => 'Disetujui', 'Ditolak' => 'Ditolak',
        'Selesai' => 'Selesai', 'Dibatalkan' => 'Dibatalkan', 'Kadaluwarsa' => 'Kadaluwarsa',
    ];

    $totalSemua = $reservasi->sum('total_biaya');
    $diajukanPada = $reservasi->min('created_at');
@endphp

@section('content')
<style>
    /* ══════ TOP BREADCRUMB ══════ */
    .rs-crumb { display:flex; align-items:center; gap:.35rem; font-size:.75rem; font-weight:700; margin-bottom:.85rem; }
    .rs-crumb a { color:var(--primary-dark); text-decoration:none; }
    .rs-crumb a:hover { text-decoration:underline; }
    .rs-crumb i { color:var(--soft); font-size:.7em; }
    .rs-crumb .cur { color:var(--muted); font-weight:600; }

    /* ══════ DETAIL CARD ══════ */
    .rs-detail { padding:0; overflow:hidden; margin-bottom:1.25rem; }

    /* Header banner: image (kiri) + info (kanan) */
    .rs-detail-head { display:grid; grid-template-columns:38% 1fr; gap:0; }
    @media (max-width: 767.98px) { .rs-detail-head { grid-template-columns:1fr; } }

    .rs-detail-img { position:relative; min-height:16rem; background:var(--surface); overflow:hidden; }
    .rs-detail-img img { width:100%; height:100%; object-fit:cover; position:absolute; inset:0; }
    .rs-detail-img .kat-badge { position:absolute; top:1rem; left:1rem;
        display:inline-flex; align-items:center; gap:.4rem; font-size:.7rem; font-weight:800;
        background:rgba(255,255,255,.95); padding:.4rem .75rem; border-radius:9999px;
        color:var(--primary-dark); backdrop-filter:blur(6px); box-shadow:0 4px 10px rgba(0,0,0,.15); }
    .rs-detail-img .lt-badge { position:absolute; bottom:1rem; left:1rem;
        display:inline-flex; align-items:center; gap:.35rem; font-size:.65rem; font-weight:800;
        background:rgba(15,23,42,.75); color:#fff; padding:.35rem .65rem; border-radius:.5rem;
        backdrop-filter:blur(6px); text-transform:uppercase; letter-spacing:.06em; }

    .rs-detail-info { padding:1.5rem 1.75rem; display:flex; flex-direction:column; gap:1rem; }
    .rs-detail-info-top { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
    .rs-detail-info h1 { font-size:1.6rem; font-weight:800; color:var(--ink); margin:0 0 .35rem; letter-spacing:-.02em; line-height:1.15; }
    .rs-detail-info .kode-row { display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; margin-top:.35rem; }
    .rs-detail-info .kode-row .kode { font-size:.7rem; color:var(--muted); font-weight:700;
        display:inline-flex; align-items:center; gap:.3rem; }
    .rs-detail-info .kode-row .kode i { color:var(--soft); font-size:.9em; }
    .rs-detail-info .price-col { text-align:right; flex:none; }
    .rs-detail-info .price-col .lbl { font-size:.62rem; font-weight:800; letter-spacing:.08em;
        text-transform:uppercase; color:var(--soft); }
    .rs-detail-info .price-col .val { font-size:1.5rem; font-weight:800; color:var(--primary-dark);
        letter-spacing:-.02em; line-height:1.1; }
    .rs-detail-info .price-col .kalk { font-size:.7rem; color:var(--muted); margin-top:.15rem; }

    /* Tracker horizontal */
    .rs-tracker { display:flex; align-items:flex-start; margin:.75rem 0; padding:1rem 0; }
    .rs-tstep { flex:1 1 0; text-align:center; position:relative; min-width:70px; }
    .rs-tstep .tdot { width:2.4rem; height:2.4rem; border-radius:50%; display:grid; place-items:center;
        margin:0 auto .4rem; background:#e6edf3; color:#8a97a5; font-size:1rem; position:relative; z-index:1;
        border:3px solid #fff; box-shadow:0 0 0 1px #e0e8ef;
        transition:all .25s ease; }
    .rs-tstep .tlabel { font-size:.72rem; font-weight:800; color:#8a97a5; }
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
    .rs-kuitansi-head { display:flex; align-items:flex-start; gap:.75rem; margin-bottom:1rem; }
    .rs-kuitansi-head .ic { display:grid; place-items:center; width:2.65rem; height:2.65rem; border-radius:.75rem;
        background:linear-gradient(135deg, var(--primary-dark), var(--primary));
        color:#fff; font-size:1rem; flex:none;
        box-shadow:0 6px 14px -3px rgba(23,107,135,.45); }
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
    .rs-line .rhs.disc .val { color:var(--emerald); }
    .rs-line .rhs.disc small { font-size:.65rem; color:var(--emerald); font-weight:700; display:block; }

    .rs-total-row { display:flex; align-items:center; justify-content:space-between; gap:1rem;
        margin-top:1rem; padding:1rem 1.25rem; border-radius:1rem;
        background:linear-gradient(135deg, var(--primary-darker), var(--primary));
        color:#fff; box-shadow:0 12px 24px -8px rgba(23,107,135,.4); flex-wrap:wrap; }
    .rs-total-row .lbl-t { font-size:.7rem; font-weight:800; letter-spacing:.08em;
        text-transform:uppercase; color:#a7f3d0; display:block; margin-bottom:.15rem; }
    .rs-total-row .val-t { font-size:1.6rem; font-weight:800; color:#fff; letter-spacing:-.02em; line-height:1.1; }
    .rs-total-row .val-t .sub-t { font-size:.68rem; font-weight:600; color:#a7f3d0; text-transform:uppercase; letter-spacing:.06em; margin-left:.5rem; }
    .rs-total-row .chip-t { font-size:.7rem; font-weight:800; padding:.4rem .8rem;
        border-radius:9999px; background:rgba(255,255,255,.15);
        border:1px solid rgba(255,255,255,.25); color:#fff;
        display:inline-flex; align-items:center; gap:.35rem; }

    /* Action buttons */
    .rs-actions { display:flex; align-items:center; gap:.65rem; margin-top:1.15rem; flex-wrap:wrap; }
    .rs-actions .btn { font-size:.82rem; padding:.65rem 1.15rem; }

    .rs-alasan-box { border-radius:1rem; padding:.85rem 1rem; font-size:.82rem;
        display:flex; gap:.65rem; align-items:flex-start; margin-top:1rem; }
    .rs-alasan-box.tolak { background:var(--rose-tint); border:1px solid #fecdd3; color:#9f1239; }
    .rs-alasan-box.batal { background:var(--surface-2); border:1px solid var(--line); color:#4a5568; }
    .rs-alasan-box.approved { background:var(--emerald-soft); border:1px solid #a7f3d0; color:#065f46; }

    /* Riwayat timeline */
    .rs-hist-btn { background:none; border:0; color:var(--primary-dark);
        font-size:.75rem; font-weight:800; padding:0; display:inline-flex; align-items:center; gap:.35rem;
        margin-top:1rem; cursor:pointer; }
    .rs-hist-btn:hover { color:var(--primary-darker); }
    .rs-hist-btn[aria-expanded="true"] .bi-chevron-down { transform:rotate(180deg); }
    .rs-hist-btn .bi-chevron-down { transition:transform .2s ease; }
    .rs-timeline { list-style:none; margin:1rem 0 0; padding:0 0 0 1.15rem;
        border-left:2px solid var(--line); }
    .rs-timeline li { position:relative; padding:0 0 .85rem .95rem; }
    .rs-timeline li:last-child { padding-bottom:0; }
    .rs-timeline li::before { content:''; position:absolute; left:-1.52rem; top:.28rem;
        width:.72rem; height:.72rem; border-radius:50%; background:#cbd5e1;
        border:2px solid #fff; box-shadow:0 0 0 1px #cbd5e1; }
    .rs-timeline li.hijau::before { background:var(--emerald); box-shadow:0 0 0 1px var(--emerald); }
    .rs-timeline li.merah::before { background:var(--rose); box-shadow:0 0 0 1px var(--rose); }
    .rs-timeline .tgl { font-size:.7rem; color:var(--muted); }
    .rs-timeline .fw-semibold { font-weight:700; }
</style>

    {{-- Breadcrumb --}}
    <div class="rs-crumb" data-reveal>
        <a href="{{ route('customer.reservasi-saya.index') }}">Reservasi Saya</a>
        <i class="bi bi-chevron-right"></i>
        <span class="cur">{{ $kode }}</span>
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
            {{-- HEAD: image + info + tracker --}}
            <div class="rs-detail-head">
                <div class="rs-detail-img">
                    <img src="{{ $fotoList[0] }}" alt="{{ $fs->nama_fasilitas }}">
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
                        <div class="min-w-0" style="min-width:0;">
                            <h1>{{ $fs->nama_fasilitas }}
                                <span class="chip {{ $chipClass[$status] ?? '' }}" style="margin-left:.4rem; vertical-align:middle;">{{ $labelStatus }}</span>
                            </h1>
                            <div class="kode-row">
                                <span class="kode"><i class="bi bi-upc"></i>{{ $r->kode_reservasi }}</span>
                                <span class="kode"><i class="bi bi-receipt"></i>{{ $r->kode_transaksi }}</span>
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
                            {{ $r->tanggal_mulai->translatedFormat('d M Y') }}
                            @if($r->tanggal_selesai->ne($r->tanggal_mulai)) → {{ $r->tanggal_selesai->translatedFormat('d M Y') }}@endif
                        </span>
                        @if ($r->jam_mulai)
                            <span class="rs-meta-chip">
                                <i class="bi bi-clock"></i>
                                {{ Str::substr($r->jam_mulai,0,5) }}–{{ Str::substr($r->jam_selesai,0,5) }} WIB
                            </span>
                        @endif
                        <span class="rs-meta-chip">
                            <i class="bi bi-tag"></i>Per {{ $satuanNama }}
                        </span>
                        @if ($r->jumlah_pengguna)
                            <span class="rs-meta-chip">
                                <i class="bi bi-people"></i>{{ $r->jumlah_pengguna }} orang
                            </span>
                        @endif
                    </div>

                    {{-- Alasan / catatan --}}
                    @if ($status === 'Ditolak')
                        <div class="rs-alasan-box tolak">
                            <i class="bi bi-exclamation-octagon-fill flex-shrink-0 mt-1"></i>
                            <div><strong>Alasan penolakan:</strong> {{ $alasan ?: 'Tidak dicantumkan.' }}</div>
                        </div>
                    @elseif ($status === 'Dibatalkan')
                        <div class="rs-alasan-box batal">
                            <i class="bi bi-slash-circle-fill flex-shrink-0 mt-1"></i>
                            <div>Reservasi ini telah dibatalkan{{ $alasan ? '. '.$alasan : ' oleh pemesan.' }}</div>
                        </div>
                    @elseif ($status === 'Kadaluwarsa')
                        <div class="rs-alasan-box tolak">
                            <i class="bi bi-clock-history flex-shrink-0 mt-1"></i>
                            <div>Reservasi ini kadaluwarsa karena tidak diproses admin hingga melewati waktu penggunaan.</div>
                        </div>
                    @elseif ($status === 'Disetujui' && $r->tanggal_diproses)
                        <div class="rs-alasan-box approved">
                            <i class="bi bi-patch-check-fill flex-shrink-0 mt-1"></i>
                            <div>Disetujui pada {{ $r->tanggal_diproses->translatedFormat('d M Y H:i') }}. Tunjukkan kode reservasi Anda saat datang.</div>
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

                <div>
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
                </div>

                <div class="rs-total-row">
                    <div>
                        <span class="lbl-t">Total Biaya</span>
                        <span class="val-t">Rp {{ number_format($r->total_biaya, 0, ',', '.') }}</span>
                    </div>
                    <span class="chip-t">
                        <i class="bi bi-check-circle-fill"></i>
                        {{ in_array($status, ['Disetujui','Selesai']) ? 'Lunas & Terbayar' : 'Menunggu Verifikasi' }}
                    </span>
                </div>

                {{-- Dokumen persyaratan (kalau ada) --}}
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
                    <a href="{{ route('cek-status.bukti-reservasi', $r->kode_reservasi) }}" class="btn btn-brand">
                        <i class="bi bi-file-earmark-arrow-down me-1"></i>Unduh Bukti Reservasi
                    </a>
                    @if ($bolehBatal)
                        <form method="POST" action="{{ route('reservasi.batalkan', $r->kode_reservasi) }}"
                              data-confirm="Reservasi {{ $r->kode_reservasi }} akan dibatalkan dan tidak dapat dikembalikan."
                              data-confirm-title="Batalkan reservasi ini?" data-icon="warning"
                              data-confirm-text="Ya, batalkan" data-confirm-color="#e11d48">
                            @csrf
                            <input type="hidden" name="kembali" value="{{ route('customer.reservasi-saya.show', $kode, absolute: false) }}">
                            <button class="btn btn-brand-outline" style="color:var(--rose); border-color:var(--rose);">
                                <i class="bi bi-x-circle me-1"></i>Batalkan
                            </button>
                        </form>
                    @endif
                </div>

                {{-- Riwayat status collapse --}}
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
                                    <div class="fw-semibold small">{{ $sb === 'Menunggu' ? 'Diverifikasi' : $sb }}</div>
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
        <a href="{{ route('customer.reservasi-saya.index') }}" class="btn btn-brand-outline">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Reservasi Saya
        </a>
    </div>
@endsection