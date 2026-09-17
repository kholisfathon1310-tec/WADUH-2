@extends('layouts.customer')
@section('title', 'Reservasi Saya')

@php
    $chipClass = [
        'Menunggu' => 'menunggu', 'Disetujui' => 'disetujui', 'Ditolak' => 'ditolak',
        'Selesai' => 'selesai', 'Dibatalkan' => 'dibatalkan', 'Kadaluwarsa' => 'kadaluwarsa',
    ];
    $chipLabel = [
        'Menunggu' => 'Menunggu Verifikasi', 'Disetujui' => 'Disetujui', 'Ditolak' => 'Ditolak',
        'Selesai' => 'Selesai', 'Dibatalkan' => 'Dibatalkan', 'Kadaluwarsa' => 'Kadaluwarsa',
    ];
    $filterList = [
        'semua'       => 'Semua',
        'Menunggu'    => 'Menunggu',
        'Disetujui'   => 'Disetujui',
        'Selesai'     => 'Selesai',
        'Ditolak'     => 'Ditolak',
        'Dibatalkan'  => 'Dibatalkan',
        'Kadaluwarsa' => 'Kadaluwarsa',
    ];

    $semua = $terbaru->concat($sebelumnya);
    $total = $semua->count();
    $jumlahPerFilter = $semua->countBy(fn ($r) => $r->status_reservasi->value);
@endphp

@section('content')
    <style>
        /* Toolbar filter */
        .rs-toolbar { display:flex; align-items:stretch; gap:.75rem; flex-wrap:wrap; padding:1rem 1.15rem; }
        .rs-filter-box, .rs-search { display:flex; align-items:center; gap:.55rem;
            border:1px solid var(--line); border-radius:.85rem; background:var(--surface);
            padding:0 .95rem; transition:border-color .15s ease, box-shadow .15s ease; }
        .rs-filter-box:focus-within, .rs-search:focus-within { border-color:var(--primary); background:#fff;
            box-shadow:0 0 0 3px rgba(23,107,135,.1); }
        .rs-filter-box i, .rs-search i { color:var(--primary); font-size:.9rem; flex:none; }
        .rs-filter-box select { border:0; background:transparent; outline:none; font-size:.82rem;
            font-weight:700; color:var(--ink); padding:.6rem 0; }
        .rs-search { flex:1 1 18rem; min-width:14rem; }
        .rs-search input { flex:1; min-width:0; border:0; background:transparent; outline:none;
            font-size:.82rem; color:var(--ink); padding:.6rem 0; }
        .rs-search input::placeholder { color:var(--soft); }
        @media (max-width: 575.98px) {
            .rs-toolbar { flex-direction:column; align-items:stretch; }
            .rs-filter-box { width:100%; }
            .rs-filter-box select { flex:1; }
        }

        /* Section heading antar Terbaru/Sebelumnya */
        .rs-section-head { display:flex; align-items:center; gap:.6rem; margin:1.5rem 0 .75rem; }
        .rs-section-head:first-of-type { margin-top:0; }
        .rs-section-head h2 { font-size:.95rem; font-weight:800; color:var(--ink); margin:0; }
        .rs-section-head .cnt { font-size:.68rem; font-weight:800; background:var(--surface-2); color:var(--muted);
            padding:.2rem .55rem; border-radius:9999px; border:1px solid var(--line); }

        /* Kartu reservasi — layout 2 kolom (info + harga/aksi) */
        .rs-card { padding:1.1rem 1.25rem; display:flex; align-items:center; gap:1.15rem; }
        .rs-card .thumb { width:5.2rem; height:5.2rem; border-radius:1rem; object-fit:cover; flex:none; background:var(--surface); border:1px solid var(--line); position:relative; overflow:hidden; }
        .rs-card .info { flex:1; min-width:0; }
        .rs-card .nm-row { display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; margin-bottom:.35rem; }
        .rs-card .nm { font-weight:800; font-size:1rem; color:var(--ink); margin:0; }
        .rs-card .meta { font-size:.76rem; color:var(--muted); display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; }
        .rs-card .meta .code { color:var(--ink); font-weight:700; }
        .rs-card .meta i { color:var(--soft); font-size:.9em; }
        .rs-card .meta .sep { color:#cbd5e1; }
        .rs-card .desc { font-size:.72rem; color:var(--soft); margin-top:.35rem; }
        .rs-card .side { text-align:end; flex:none; min-width:11rem; display:flex; flex-direction:column; align-items:flex-end; gap:.5rem; }
        .rs-card .side .lbl { font-size:.62rem; font-weight:800; color:var(--soft); text-transform:uppercase; letter-spacing:.08em; }
        .rs-card .price { font-weight:800; color:var(--primary-dark); font-size:1.15rem; letter-spacing:-.01em; line-height:1; }
        .rs-card .actions { display:flex; align-items:center; gap:.4rem; }
        .rs-card .btn-detail { font-size:.72rem; padding:.4rem .75rem; }

        @media (max-width: 767.98px) {
            .rs-card { flex-wrap:wrap; }
            .rs-card .side { min-width:0; width:100%; align-items:flex-start; padding-top:.75rem; border-top:1px solid var(--line-soft); }
        }

        /* Pagination bar */
        .rs-paginate { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.15rem; flex-wrap:wrap; }
        .rs-paginate .info { font-size:.78rem; color:var(--muted); font-weight:600; }
        .rs-paginate .info b { color:var(--ink); }

        .rs-empty-filter { display:none; }
    </style>

    <div class="page-head mb-4" data-reveal>
        <p class="eyebrow-sm mb-2">Reservasi Saya</p>
        <h1 class="h3 mb-1">Riwayat &amp; Status Reservasi</h1>
        <p class="text-muted mb-0 lead">Semua reservasi yang pernah Anda ajukan, beserta status terbarunya.</p>
    </div>

    @if ($total === 0)
        <div class="xcard p-5 text-center mx-auto" style="max-width:520px;" data-reveal>
            <div class="icon-tile mx-auto mb-3"><i class="bi bi-journal-x"></i></div>
            <h2 class="h6 mb-2">Belum ada reservasi</h2>
            <p class="text-muted small mb-3">Mulai reservasi fasilitas pertama Anda sekarang.</p>
            <a href="{{ route('reservasi.index') }}" class="btn btn-brand px-4">Jelajahi Fasilitas</a>
        </div>
    @else
        <div class="xcard mb-3 rs-toolbar" data-reveal>
            <div class="rs-filter-box">
                <i class="bi bi-funnel-fill"></i>
                <select id="rsFilterSelect">
                    @foreach ($filterList as $key => $label)
                        @php $n = $key === 'semua' ? $total : ($jumlahPerFilter[$key] ?? 0); @endphp
                        <option value="{{ $key }}">{{ $label }} ({{ $n }})</option>
                    @endforeach
                </select>
            </div>
            <div class="rs-search">
                <i class="bi bi-search"></i>
                <input type="search" id="rsSearch" placeholder="Cari nama fasilitas atau kode reservasi...">
            </div>
        </div>

        <div id="rsList" data-reveal>
            @if ($terbaru->isNotEmpty())
                <div class="rs-section-head">
                    <h2>Reservasi Terbaru</h2>
                    <span class="cnt">{{ $terbaru->count() }}</span>
                </div>
                <div class="d-flex flex-column gap-2 mb-3">
                    @foreach ($terbaru as $r)
                        @include('customer.reservasi-saya.partials.card', ['r' => $r])
                    @endforeach
                </div>
            @endif

            @if ($sebelumnya->isNotEmpty())
                <div class="rs-section-head">
                    <h2>Reservasi Sebelumnya</h2>
                    <span class="cnt">{{ $sebelumnya->count() }}</span>
                </div>
                <div class="d-flex flex-column gap-2">
                    @foreach ($sebelumnya as $r)
                        @include('customer.reservasi-saya.partials.card', ['r' => $r])
                    @endforeach
                </div>
            @endif
        </div>

        <div class="xcard p-5 text-center rs-empty-filter mt-2" id="rsEmptyFilter">
            <div class="icon-tile mx-auto mb-3"><i class="bi bi-search"></i></div>
            <p class="text-muted small mb-0">Tidak ada reservasi yang cocok dengan filter/pencarian ini.</p>
        </div>

        <div class="xcard mt-3 rs-paginate" data-reveal>
            <p class="info mb-0">
                Menampilkan <b>{{ $total }}</b> reservasi
            </p>
            <span class="text-muted small"><i class="bi bi-info-circle me-1"></i>Diurutkan dari terbaru</span>
        </div>

        <script>
            (function () {
                const select = document.getElementById('rsFilterSelect');
                const cards = document.querySelectorAll('#rsList [data-rs-status]');
                const search = document.getElementById('rsSearch');
                const empty = document.getElementById('rsEmptyFilter');

                const terapkan = () => {
                    const statusAktif = select.value;
                    const kw = search.value.trim().toLowerCase();
                    let terlihat = 0;
                    cards.forEach((c) => {
                        const cocokStatus = statusAktif === 'semua' || c.dataset.rsStatus === statusAktif;
                        const cocokCari = ! kw || c.dataset.rsSearch.includes(kw);
                        const tampil = cocokStatus && cocokCari;
                        c.hidden = ! tampil;
                        if (tampil) terlihat++;
                    });
                    empty.style.display = terlihat === 0 ? 'block' : 'none';
                };

                select.addEventListener('change', terapkan);
                search.addEventListener('input', terapkan);
            })();
        </script>
    @endif
@endsection
