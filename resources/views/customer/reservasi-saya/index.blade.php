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
        /* position:relative + z-index SENGAJA dipasang di sini — animasi "muncul saat scroll"
           (atribut data-reveal, dipakai layout) memberi elemen ini transform, dan transform apa
           pun (walau cuma identity/selesai animasi) otomatis membuat stacking context baru.
           Tanpa z-index eksplisit, seluruh kartu toolbar (termasuk dropdown filter di dalamnya)
           ikut tertata berdasarkan urutan DOM biasa, jadi kalah tumpuk oleh daftar reservasi
           (#rsList) yang letaknya SETELAH toolbar ini — dropdown pun tampak "tumpang tindih"
           dengan kartu di belakangnya alih-alih tampil bersih di atasnya. */
        .rs-toolbar { display:flex; align-items:stretch; gap:.75rem; flex-wrap:wrap; padding:1rem 1.15rem;
            position:relative; z-index:5; }
        .rs-search { display:flex; align-items:center; gap:.55rem;
            border:1px solid var(--line); border-radius:.85rem; background:var(--surface);
            padding:0 .95rem; transition:border-color .15s ease, box-shadow .15s ease; }
        .rs-search:focus-within { border-color:var(--primary); background:#fff;
            box-shadow:0 0 0 3px rgba(23,107,135,.1); }
        .rs-search i { color:var(--primary); font-size:.9rem; flex:none; }
        .rs-search { flex:1 1 18rem; min-width:14rem; }
        .rs-search input { flex:1; min-width:0; border:0; background:transparent; outline:none;
            font-size:.82rem; color:var(--ink); padding:.6rem 0; }
        .rs-search input::placeholder { color:var(--soft); }

        /* ─── FILTER STATUS — dropdown kustom (bukan <select> polos) ─── */
        .rs-filter-box { position:relative; flex:none; }
        .rs-filter-btn { display:flex; align-items:center; gap:.55rem; height:100%;
            border:1px solid var(--line); border-radius:.85rem; background:var(--surface);
            padding:0 .95rem; font-size:.82rem; font-weight:700; color:var(--ink); cursor:pointer;
            transition:border-color .15s ease, box-shadow .15s ease, background .15s ease; min-width:11rem; }
        .rs-filter-btn i.bi-funnel-fill { color:var(--primary); font-size:.9rem; flex:none; }
        .rs-filter-btn .lbl { flex:1; text-align:left; white-space:nowrap; }
        .rs-filter-btn .rs-filter-caret { font-size:.7rem; color:var(--soft); transition:transform .18s ease; flex:none; }
        .rs-filter-box.buka .rs-filter-btn { border-color:var(--primary); background:#fff; box-shadow:0 0 0 3px rgba(23,107,135,.1); }
        .rs-filter-box.buka .rs-filter-caret { transform:rotate(180deg); }
        .rs-filter-menu { display:none; position:absolute; top:calc(100% + 8px); left:0; z-index:1040; min-width:15rem;
            background:#fff; border:1px solid var(--line); border-radius:1rem;
            box-shadow:0 18px 40px -12px rgba(15,23,42,.18); padding:.5rem; }
        .rs-filter-box.buka .rs-filter-menu { display:block; animation:rsFilterPop .15s ease; }
        @keyframes rsFilterPop { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }
        .rs-filter-opt { display:flex; align-items:center; gap:.6rem; width:100%; border:0; background:none;
            border-radius:.65rem; padding:.55rem .65rem; font-size:.8rem; font-weight:600; color:var(--muted);
            cursor:pointer; transition:background .12s ease, color .12s ease; text-align:left; }
        .rs-filter-opt:hover { background:var(--surface-2); color:var(--ink); }
        .rs-filter-opt.active { background:var(--primary-soft); color:var(--primary-dark); font-weight:800; }
        .rs-filter-opt .dot { width:.55rem; height:.55rem; border-radius:50%; flex:none; }
        .rs-filter-opt .dot.semua { background:var(--primary); }
        .rs-filter-opt .dot.menunggu { background:#f59e0b; }
        .rs-filter-opt .dot.disetujui { background:var(--emerald); }
        .rs-filter-opt .dot.ditolak { background:var(--rose); }
        .rs-filter-opt .dot.dibatalkan { background:#94a3b8; }
        .rs-filter-opt .dot.selesai { background:#3b82f6; }
        .rs-filter-opt .dot.kadaluwarsa { background:#8b5cf6; }
        .rs-filter-opt .lbl { flex:1; }
        .rs-filter-opt .cnt { font-size:.68rem; font-weight:800; color:var(--soft); background:var(--surface-2);
            border-radius:9999px; padding:.1rem .5rem; }
        .rs-filter-opt.active .cnt { background:#fff; color:var(--primary-dark); }

        @media (max-width: 575.98px) {
            .rs-toolbar { flex-direction:column; align-items:stretch; }
            .rs-filter-box { width:100%; }
            .rs-filter-btn { width:100%; }
            .rs-filter-menu { left:0; right:0; min-width:0; }
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
            <div class="rs-filter-box" id="rsFilterBox">
                <button type="button" class="rs-filter-btn" id="rsFilterBtn" aria-haspopup="listbox" aria-expanded="false">
                    <i class="bi bi-funnel-fill"></i>
                    <span class="lbl" id="rsFilterBtnLabel">Semua ({{ $total }})</span>
                    <i class="bi bi-chevron-down rs-filter-caret"></i>
                </button>
                <div class="rs-filter-menu" role="listbox" id="rsFilterMenu">
                    @foreach ($filterList as $key => $label)
                        @php $n = $key === 'semua' ? $total : ($jumlahPerFilter[$key] ?? 0); @endphp
                        <button type="button" class="rs-filter-opt {{ $key === 'semua' ? 'active' : '' }}" data-value="{{ $key }}" role="option" aria-selected="{{ $key === 'semua' ? 'true' : 'false' }}">
                            <span class="dot {{ $key === 'semua' ? 'semua' : ($chipClass[$key] ?? '') }}"></span>
                            <span class="lbl">{{ $label }}</span>
                            <span class="cnt">{{ $n }}</span>
                        </button>
                    @endforeach
                </div>
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
                const box = document.getElementById('rsFilterBox');
                const btn = document.getElementById('rsFilterBtn');
                const btnLabel = document.getElementById('rsFilterBtnLabel');
                const menu = document.getElementById('rsFilterMenu');
                const opts = menu.querySelectorAll('.rs-filter-opt');
                const cards = document.querySelectorAll('#rsList [data-rs-status]');
                const search = document.getElementById('rsSearch');
                const empty = document.getElementById('rsEmptyFilter');
                let statusAktif = 'semua';

                const terapkan = () => {
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

                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    box.classList.toggle('buka');
                    btn.setAttribute('aria-expanded', box.classList.contains('buka') ? 'true' : 'false');
                });
                document.addEventListener('click', () => box.classList.remove('buka'));
                menu.addEventListener('click', (e) => e.stopPropagation());

                opts.forEach((opt) => {
                    opt.addEventListener('click', () => {
                        statusAktif = opt.dataset.value;
                        opts.forEach((o) => { o.classList.remove('active'); o.setAttribute('aria-selected', 'false'); });
                        opt.classList.add('active');
                        opt.setAttribute('aria-selected', 'true');
                        btnLabel.textContent = opt.querySelector('.lbl').textContent + ' (' + opt.querySelector('.cnt').textContent + ')';
                        box.classList.remove('buka');
                        terapkan();
                    });
                });

                search.addEventListener('input', terapkan);
            })();
        </script>
    @endif
@endsection
