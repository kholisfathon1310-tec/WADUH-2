<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Area Pemesan') | WADUH</title>
    <link href="{{ asset('vendor/fonts/fonts.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ═══════════════════════════════════════════════════════════════
           WADUH – Area Pemesan (redesign v2)
           Palet: teal 700-800 primary + hint mint di background.
           ═══════════════════════════════════════════════════════════════ */
        :root {
            --ink:#0f172a; --muted:#64748b; --soft:#94a3b8;
            --primary:#176b87; --primary-dark:#0f526b; --primary-darker:#0c3648;
            --primary-soft:#e6f2f4; --primary-softer:#c9e6ea; --primary-tint:#eef7f8;
            --accent:#178f87; --accent-light:#24aa9a;
            --amber-soft:#fef3c7; --amber:#d97706; --amber-tint:#fef9e7;
            --rose:#e11d48; --rose-soft:#fff1f2; --rose-tint:#fef2f4;
            --emerald:#059669; --emerald-soft:#d1fae5;
            --surface:#f4f7f9; --surface-2:#eef3f6;
            --line:#e2e8f0; --line-soft:#eef2f6;
            --card-shadow:0 1px 3px rgba(15,23,42,.05), 0 4px 12px -6px rgba(15,60,73,.08);
            --card-shadow-lg:0 20px 42px -18px rgba(15,60,73,.2), 0 4px 12px -4px rgba(15,60,73,.08);
        }
        * { scrollbar-width:thin; scrollbar-color:#cbd5e1 transparent; }
        *::-webkit-scrollbar { width:6px; height:6px; }
        *::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:9999px; }
        *::-webkit-scrollbar-thumb:hover { background:#94a3b8; }
        *::-webkit-scrollbar-track { background:transparent; }

        body {
            font-family:'Plus Jakarta Sans',sans-serif; color:var(--ink);
            line-height:1.55; -webkit-font-smoothing:antialiased;
            background:
                radial-gradient(48rem 28rem at 108% -8%, #d7efe9 0%, transparent 55%),
                radial-gradient(38rem 24rem at -12% 10%, #dfeaf5 0%, transparent 55%),
                radial-gradient(30rem 20rem at 50% 110%, #e8f4f2 0%, transparent 55%),
                var(--surface);
            background-attachment:fixed;
        }
        h1,h2,h3,h4,h5 { font-family:'Plus Jakarta Sans',sans-serif; letter-spacing:-.015em; font-weight:800; }
        ::selection { background:var(--primary-softer); color:var(--primary-darker); }

        @keyframes riseIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:none; } }
        [data-reveal] { animation:riseIn .45s cubic-bezier(.2,.7,.3,1) both; }
        @media (prefers-reduced-motion: reduce) { [data-reveal] { animation:none; } }

        /* SweetAlert2: overlay masih menangkap klik selama animasi fade-out (~150ms) walau
           popup sudah kelihatan hilang — akibatnya klik SEGERA setelah "Oke" (mis. menekan
           lagi tombol Edit Profil) sering "tertelan" overlay dan perlu diklik 2x. Matikan
           pointer-events begitu class hide dipasang, sebelum animasinya selesai. */
        .swal2-backdrop-hide { pointer-events: none !important; }

        /* ─── FRAME ─────────────────────────────────────────────── */
        .frame { display:flex; min-height:100vh; }
        .sidebar { width:272px; flex:none;
            background:linear-gradient(180deg, #ffffff 0%, #fbfdfd 100%);
            border-right:1px solid var(--line);
            color:var(--ink); display:flex; flex-direction:column;
            position:sticky; top:0; height:100vh; overflow-y:auto; z-index:1046;
            transition:width .22s ease; box-shadow:2px 0 12px -6px rgba(15,60,73,.06); }
        .content { flex:1; min-width:0; min-height:100vh; display:flex; flex-direction:column; }
        .backdrop { display:none; }
        @media (max-width: 991.98px) {
            .sidebar { position:fixed; left:-290px; height:100vh; box-shadow:14px 0 40px -20px rgba(15,23,42,.25); transition:left .25s ease; }
            .sidebar.open { left:0; }
            .backdrop { display:none; position:fixed; inset:0; background:rgba(15,23,42,.55); backdrop-filter:blur(2px); z-index:1044; opacity:0; transition:opacity .25s ease; }
            .backdrop.show { display:block; opacity:1; }
        }
        @media (min-width: 992px) {
            body.sidebar-collapsed .sidebar { width:82px; }
            body.sidebar-collapsed .side-section,
            body.sidebar-collapsed .side-help { display:none; }
            body.sidebar-collapsed .side-link { justify-content:center; padding:.65rem; }
            body.sidebar-collapsed .side-link .lbl,
            body.sidebar-collapsed .side-link .caret,
            body.sidebar-collapsed .side-link .badge { display:none; }
            body.sidebar-collapsed .side-sub { display:none !important; }
        }

        /* ─── BRAND ─────────────────────────────────────────────── */
        .side-brand-row { display:flex; align-items:center; justify-content:center; padding:1.15rem 1.4rem;
            border-bottom:1px solid var(--line-soft);
            background:linear-gradient(135deg, rgba(240,253,250,.6), rgba(255,255,255,0)); }
        .side-brand { display:flex; align-items:center; justify-content:center; gap:.7rem; min-width:0; border:none; background:none;
            padding:.4rem .5rem; border-radius:.85rem; cursor:pointer; transition:background .15s ease; width:100%; }
        .side-brand:hover { background:var(--primary-soft); }
        .side-brand .brand-mark { display:block; max-width:100%; }
        .side-brand .brand-mark img { height:auto; width:100%; max-width:6.5rem; display:block; }
        body.sidebar-collapsed .side-brand-row { padding:1.15rem .5rem; justify-content:center; }
        body.sidebar-collapsed .side-brand .brand-mark img { max-width:2.3rem; }

        /* ─── NAV ───────────────────────────────────────────────── */
        .side-section { padding:1.1rem 1.4rem .55rem; font-size:.62rem; font-weight:800; letter-spacing:.14em; text-transform:uppercase; color:var(--soft); }
        .side-nav { list-style:none; margin:0; padding:0 .8rem; display:flex; flex-direction:column; gap:.15rem; }
        .side-link { display:flex; align-items:center; gap:.7rem; padding:.7rem .85rem; color:#475569; text-decoration:none;
            border-radius:.75rem; font-weight:600; font-size:.87rem;
            transition:background .18s ease, color .18s ease; }
        .side-link .mic { display:grid; place-items:center; width:1.4rem; height:1.4rem; font-size:1rem; color:#94a3b8; flex:none; transition:color .18s ease; }
        .side-link:hover { color:var(--ink); background:var(--primary-soft); }
        .side-link:hover .mic { color:var(--primary); }
        .side-link.active { color:var(--primary-dark);
            background:linear-gradient(90deg, var(--primary-soft), var(--primary-tint));
            font-weight:800; box-shadow:inset 0 0 0 1px var(--primary-softer); }
        .side-link.active .mic { color:var(--primary); }
        .side-link .caret { margin-left:auto; font-size:.7rem; opacity:.5; transition:transform .2s; }
        .side-link[aria-expanded="true"] .caret { transform:rotate(90deg); }
        .side-link .badge { margin-left:auto; font-size:.65rem !important; font-weight:800 !important;
            background:var(--primary) !important; color:#fff !important; border-radius:9999px !important; padding:.15rem .5rem !important; }
        .side-link.active .badge { background:var(--primary-dark) !important; }

        /* Sub-menu — Fasilitas → Kategori → Lantai */
        .side-sub { list-style:none; margin:.15rem 0 .3rem; padding:0 0 0 2.5rem; }
        .side-sub a, .side-sub .side-sub-toggle {
            display:flex; align-items:center; gap:.5rem; padding:.4rem .7rem; margin:.05rem 0;
            color:#64748b; text-decoration:none; border-left:2px solid var(--line);
            border-radius:0 .55rem .55rem 0; font-size:.8rem; font-weight:500;
            transition:all .18s ease; cursor:pointer; background:transparent; }
        .side-sub a:hover, .side-sub .side-sub-toggle:hover { color:var(--primary-dark); background:var(--primary-soft); border-left-color:var(--primary); }
        .side-sub a.active, .side-sub .side-sub-toggle.active {
            color:var(--primary-dark); border-left-color:var(--primary); background:var(--primary-soft); font-weight:700; }
        .side-sub .side-sub-toggle .caret-sm { margin-left:auto; font-size:.6rem; opacity:.55; transition:transform .2s; }
        .side-sub .side-sub-toggle[aria-expanded="true"] .caret-sm { transform:rotate(90deg); }
        .side-subsub { list-style:none; margin:.1rem 0 .25rem; padding:0 0 0 1.2rem; }
        .side-subsub a { display:block; padding:.32rem .6rem; margin:.05rem 0; color:#94a3b8; text-decoration:none;
            border-left:2px dashed var(--line); border-radius:0 .5rem .5rem 0; font-size:.76rem; font-weight:500; transition:all .18s ease; }
        .side-subsub a:hover { color:var(--primary-dark); background:var(--primary-soft); }
        .side-subsub a.active { color:var(--primary-dark); border-left-color:var(--primary); background:var(--primary-soft); font-weight:700; }

        /* ─── SIDEBAR BOTTOM (logout) ───────────────────────────── */
        .side-bottom { margin-top:auto; padding:1rem .8rem 1.25rem; border-top:1px solid var(--line-soft); }
        .side-bottom .side-link { color:var(--rose); font-weight:700; }
        .side-bottom .side-link .mic { color:var(--rose); }
        .side-bottom .side-link:hover { background:var(--rose-soft); color:#be123c; }
        .side-bottom .side-link:hover .mic { color:#be123c; }

        /* ─── TOPBAR ────────────────────────────────────────────── */
        .topbar { background:rgba(255,255,255,.85); backdrop-filter:blur(14px);
            border-bottom:1px solid var(--line);
            padding:.9rem 1.6rem; display:flex; align-items:center; gap:1rem;
            position:sticky; top:0; z-index:1020; min-height:5rem; }
        .topbar .tb-left { display:flex; align-items:center; gap:1rem; min-width:0; }
        .topbar .crumb { font-size:.65rem; color:var(--soft); font-weight:800; letter-spacing:.12em; text-transform:uppercase;
            display:flex; align-items:center; gap:.4rem; }
        .topbar .crumb .sep { color:#cbd5e1; }
        .topbar h1 { font-size:1.1rem; font-weight:800; margin:0; color:var(--ink); line-height:1.2; }
        .icon-btn { display:grid; place-items:center; width:2.5rem; height:2.5rem;
            border:1px solid var(--line); background:#fff; color:#475569;
            border-radius:.8rem; transition:background .15s, color .15s, border-color .15s, transform .15s;
            flex:none; text-decoration:none; box-shadow:0 1px 2px rgba(15,23,42,.04); position:relative; }
        .icon-btn:hover { background:var(--primary-soft); color:var(--primary-dark); border-color:var(--primary-softer); transform:translateY(-1px); }
        .icon-btn .cart-dot { position:absolute; top:-.3rem; right:-.3rem; min-width:1.2rem; height:1.2rem; padding:0 .3rem;
            border-radius:9999px; background:var(--primary); color:#fff; font-size:.62rem; font-weight:800;
            display:grid; place-items:center; border:2px solid #fff; }

        .tb-divider { width:1px; height:1.75rem; background:var(--line); margin:0 .25rem; }

        .profile-btn { display:flex; align-items:center; gap:.65rem; border:1px solid transparent; background:transparent;
            border-radius:.85rem; padding:.3rem .6rem .3rem .3rem; transition:background .15s, border-color .15s; }
        .profile-btn:hover { background:var(--primary-soft); border-color:var(--primary-softer); }
        .profile-btn .avatar,.pm-head .avatar { display:grid; place-items:center;
            width:2.4rem; height:2.4rem; border-radius:.75rem;
            background:linear-gradient(135deg, var(--primary-dark), var(--primary));
            color:#fff; font-weight:800; font-size:.9rem; flex:none;
            box-shadow:0 6px 14px -3px rgba(23,107,135,.45);
            background-size:cover; background-position:center; overflow:hidden; }
        .profile-btn .avatar img,.pm-head .avatar img { width:100%; height:100%; object-fit:cover; border-radius:inherit; }
        .pm-head .avatar { width:2.5rem; height:2.5rem; border-radius:.8rem; }
        .profile-btn .who { text-align:left; line-height:1.15; }
        .profile-btn .who .nm { font-size:.8rem; font-weight:800; color:var(--ink); max-width:9rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .profile-btn .who .rl { font-size:.68rem; color:var(--soft); font-weight:600; margin-top:.1rem; }
        .profile-menu { border:1px solid var(--line); border-radius:1rem; box-shadow:0 18px 40px -14px rgba(15,60,73,.22); padding:.5rem; min-width:230px; }
        .profile-menu .pm-head { display:flex; align-items:center; gap:.65rem; padding:.55rem .6rem .75rem; border-bottom:1px solid var(--line-soft); margin-bottom:.4rem; }
        .profile-menu .pm-head .nm { font-weight:800; font-size:.85rem; color:var(--ink); }
        .profile-menu .pm-head .em { font-size:.72rem; color:var(--muted); word-break:break-all; }
        .profile-menu .dropdown-item { border-radius:.6rem; padding:.5rem .6rem; font-size:.83rem; font-weight:600; color:#475569; }
        .profile-menu .dropdown-item:hover { background:var(--primary-soft); color:var(--primary-dark); }

        /* ─── MAIN + CARDS ─────────────────────────────────────── */
        main.inner { padding:1.75rem 1.6rem 2rem; flex:1; }

        .xcard { background:#fff; border:1px solid var(--line); border-radius:1.25rem;
            box-shadow:var(--card-shadow);
            transition:transform .22s ease, box-shadow .22s ease, border-color .22s ease; }
        a.xcard { text-decoration:none; color:inherit; display:block; }
        .xcard.hover:hover { transform:translateY(-3px); box-shadow:var(--card-shadow-lg); border-color:var(--primary-softer); }

        .icon-tile { display:grid; place-items:center; width:3rem; height:3rem; border-radius:.9rem;
            background:linear-gradient(135deg, var(--primary-soft), var(--primary-tint));
            color:var(--primary-dark); font-size:1.2rem; border:1px solid var(--primary-softer); }

        .btn-brand { background:linear-gradient(135deg, var(--primary-dark), var(--primary));
            border-color:var(--primary-dark); color:#fff; font-weight:700; border-radius:.85rem;
            box-shadow:0 8px 18px -6px rgba(23,107,135,.4);
            transition:background .15s ease, transform .15s ease, box-shadow .15s ease; }
        .btn-brand:hover { background:linear-gradient(135deg, var(--primary-darker), var(--primary-dark));
            border-color:var(--primary-darker); color:#fff; transform:translateY(-1px);
            box-shadow:0 12px 22px -6px rgba(23,107,135,.55); }
        .btn-brand-outline { color:var(--primary-dark); border:1px solid var(--line); border-radius:.85rem; font-weight:700; background:#fff;
            transition:all .15s ease; }
        .btn-brand-outline:hover { color:var(--primary-darker); border-color:var(--primary); background:var(--primary-soft); transform:translateY(-1px); }
        .btn { border-radius:.85rem; font-weight:700; }
        .btn-sm { border-radius:.7rem; font-weight:700; }

        .form-control,.form-select { border-radius:.75rem; border-color:var(--line); padding:.6rem .9rem; font-size:.88rem; background:#fff; }
        .form-control:focus,.form-select:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(23,107,135,.12); }
        .form-label { font-weight:700; font-size:.78rem; color:#334155; text-transform:uppercase; letter-spacing:.06em; }
        .input-group-text { background:var(--primary-soft); border-color:var(--line); color:var(--primary); border-radius:.75rem 0 0 .75rem; }
        .input-group > .form-control { border-radius:0 .75rem .75rem 0; }

        .breadcrumb { font-size:.8rem; }
        .breadcrumb a { color:var(--primary-dark); text-decoration:none; font-weight:700; }
        .breadcrumb a:hover { text-decoration:underline; }
        .breadcrumb-item + .breadcrumb-item::before { color:#cbd5e1; }

        .eyebrow-sm { display:inline-block; color:var(--primary-dark); font-size:.65rem; font-weight:800;
            letter-spacing:.14em; text-transform:uppercase;
            background:linear-gradient(135deg, var(--primary-soft), var(--primary-tint));
            padding:.3rem .7rem; border-radius:.5rem; border:1px solid var(--primary-softer); }
        .page-head h1 { font-weight:800; letter-spacing:-.02em; }
        .page-head .lead { color:var(--muted); font-size:.9rem; font-weight:500; margin-top:.25rem; }
        .page-head.text-white { position:relative; overflow:hidden; border-radius:1.5rem !important;
            background:linear-gradient(135deg,#0c3648 0%, #176b87 60%, #178f87 130%) !important;
            box-shadow:0 20px 42px -18px rgba(15,60,80,.4); }
        .page-head.text-white::before { content:''; position:absolute; inset:0;
            background:radial-gradient(28rem 16rem at 105% -20%, rgba(255,255,255,.16), transparent 55%);
            pointer-events:none; }
        .page-head.text-white > * { position:relative; }

        .alert { border-radius:1rem; border:1px solid; font-size:.85rem; }
        .alert-warning { background:var(--amber-tint); border-color:#fde68a; color:#854d0e; }
        .alert-danger { background:var(--rose-tint); border-color:#fecdd3; color:#9f1239; }
        .alert-success { background:var(--emerald-soft); border-color:#a7f3d0; color:#065f46; }
        .alert-info { background:var(--primary-soft); border-color:var(--primary-softer); color:var(--primary-darker); }

        @keyframes pulse { 0%,100% { opacity:1; transform:scale(1); } 50% { opacity:.4; transform:scale(1.15); } }

        /* Chip status */
        .chip { display:inline-flex; align-items:center; gap:.4rem; font-size:.7rem; font-weight:800;
            padding:.3rem .7rem; border-radius:9999px; white-space:nowrap; letter-spacing:.01em;
            border:1px solid; }
        .chip::before { content:''; width:.42rem; height:.42rem; border-radius:50%; background:currentColor; flex:none; }
        .chip.menunggu { background:var(--amber-tint); color:#a16207; border-color:#fde68a; }
        .chip.menunggu::before { background:#f59e0b; animation:pulse 1.6s infinite; }
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

        .avail { display:inline-flex; align-items:center; gap:.35rem; font-size:.72rem; font-weight:700;
            padding:.28rem .6rem; border-radius:9999px; border:1px solid; }
        .avail.hijau { background:var(--emerald-soft); color:#047857; border-color:#a7f3d0; }
        .avail.kuning { background:var(--amber-tint); color:#a16207; border-color:#fde68a; }
        .avail.merah { background:var(--rose-tint); color:#be123c; border-color:#fecdd3; }

        .stat-tile { border-radius:1.25rem; border:none; color:#fff; padding:1.2rem 1.3rem;
            position:relative; overflow:hidden; box-shadow:0 12px 28px -10px rgba(15,60,73,.28);
            transition:transform .2s ease, box-shadow .2s ease; }
        .stat-tile:hover { transform:translateY(-3px); box-shadow:0 18px 34px -10px rgba(15,60,73,.35); }
        .stat-tile::after { content:''; position:absolute; right:-2.2rem; bottom:-2.6rem; width:8rem; height:8rem;
            border-radius:50%; background:rgba(255,255,255,.12); }
        .stat-tile .ic { position:absolute; right:1rem; top:1rem; font-size:1.35rem; opacity:.85; }
        .stat-tile .v { font-size:1.75rem; font-weight:800; line-height:1.1; position:relative; }
        .stat-tile small { opacity:.9; font-weight:600; letter-spacing:.01em; position:relative; display:block; margin-top:.15rem; }

        .site-footer { margin-top:auto; padding:1.5rem 1.6rem; color:var(--muted); font-size:.8rem;
            background:transparent; border-top:1px solid var(--line-soft); }

        .is-salah { border-color:var(--rose) !important; background:var(--rose-tint) !important;
            box-shadow:0 0 0 3px rgba(225,29,72,.12) !important; animation:goyang .3s; }
        @keyframes goyang { 25% { transform:translateX(-4px); } 75% { transform:translateX(4px); } }
        .catatan-salah { display:flex; align-items:center; gap:.3rem; color:#be123c; font-size:.76rem; font-weight:600; margin-top:.35rem; }
    </style>
</head>
<body>
<script>
    if (localStorage.getItem('pemesanSidebarCollapsed') === '1') document.body.classList.add('sidebar-collapsed');
</script>
@php
    $pemesan = auth('customer')->user();
    $cartN = app(\App\Services\CartService::class)->count();
    $inisialPemesan = strtoupper(substr($pemesan->nama_lengkap ?? '?', 0, 1));
    // Foto profil (opsional — kolom nullable di tabel pemesan)
    $fotoPemesan = ($pemesan->foto ?? null) ? asset('storage/'.$pemesan->foto) : null;

    // Submenu sidebar Fasilitas: Kategori -> Lantai.
    $sideFasilitas = \App\Models\Fasilitas::where('status_aktif', \App\Enums\StatusAktif::Aktif->value)
        ->with('lantai')
        ->get()
        ->groupBy('kategori_fasilitas')
        ->map(fn ($items) => $items->pluck('lantai')->filter()->unique('id_lantai')->sortBy('nomor_lantai'))
        ->sortKeys();
    $fasilitasAktifDiSidebar = request()->routeIs('reservasi.*') && ! request()->routeIs('reservasi.checkout*');
    $kategoriAktifDiSidebar = request()->route('kategori');
@endphp
<div class="frame">
    <aside class="sidebar" id="sidebar">
        <div class="side-brand-row">
            <button type="button" class="side-brand" id="collapseBtn" title="Ciutkan/lebarkan sidebar">
                <span class="brand-mark"><img src="{{ asset('images/logo_bitc_crop.png') }}" alt="Logo BITC"></span>
            </button>
        </div>

        <div class="side-section">Menu Utama</div>
        <ul class="side-nav">
            <li>
                <a class="side-link {{ request()->routeIs('customer.dashboard') ? 'active' : '' }}" href="{{ route('customer.dashboard') }}" title="Dashboard">
                    <span class="mic"><i class="bi bi-grid-1x2"></i></span> <span class="lbl">Dashboard</span>
                </a>
            </li>
            <li>
                <a class="side-link {{ $fasilitasAktifDiSidebar ? 'active' : '' }}" data-bs-toggle="collapse" href="#subFasilitas"
                   aria-expanded="{{ $fasilitasAktifDiSidebar ? 'true' : 'false' }}" title="Fasilitas">
                    <span class="mic"><i class="bi bi-building"></i></span>
                    <span class="lbl">Fasilitas</span> <i class="bi bi-chevron-right caret"></i>
                </a>
                <ul class="side-sub collapse {{ $fasilitasAktifDiSidebar ? 'show' : '' }}" id="subFasilitas">
                    @foreach ($sideFasilitas as $kat => $lantaiList)
                        <li>
                            <a class="side-sub-toggle {{ $kategoriAktifDiSidebar === $kat ? 'active' : '' }}" data-bs-toggle="collapse" href="#subKat{{ $loop->index }}"
                               aria-expanded="{{ $kategoriAktifDiSidebar === $kat ? 'true' : 'false' }}">
                                {{ $kat }} <i class="bi bi-chevron-right caret-sm"></i>
                            </a>
                            <ul class="side-subsub collapse {{ $kategoriAktifDiSidebar === $kat ? 'show' : '' }}" id="subKat{{ $loop->index }}">
                                @foreach ($lantaiList as $l)
                                    <li>
                                        <a href="{{ route('reservasi.denah', ['kategori' => $kat, 'lantai' => $l->id_lantai]) }}"
                                           class="{{ request()->routeIs('reservasi.denah') && (string) $kategoriAktifDiSidebar === (string) $kat && (int) request()->route('lantai')?->id_lantai === $l->id_lantai ? 'active' : '' }}">
                                            Lantai {{ $l->nomor_lantai }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endforeach
                </ul>
            </li>
            <li>
                <a class="side-link {{ request()->routeIs('reservasi.checkout*') ? 'active' : '' }}" href="{{ route('reservasi.checkout.form') }}" title="Keranjang">
                    <span class="mic"><i class="bi bi-bag"></i></span> <span class="lbl">Keranjang</span>
                    @if ($cartN > 0)<span class="badge">{{ $cartN }}</span>@endif
                </a>
            </li>
            <li>
                <a class="side-link {{ request()->routeIs('customer.reservasi-saya.*') ? 'active' : '' }}" href="{{ route('customer.reservasi-saya.index') }}" title="Reservasi Saya">
                    <span class="mic"><i class="bi bi-calendar2-check"></i></span> <span class="lbl">Reservasi Saya</span>
                </a>
            </li>
            <li>
                <a class="side-link {{ request()->routeIs('customer.akun.*') ? 'active' : '' }}" href="{{ route('customer.akun.profil') }}" title="Profil">
                    <span class="mic"><i class="bi bi-person"></i></span> <span class="lbl">Profil</span>
                </a>
            </li>
        </ul>

        <div class="side-bottom">
            <form method="POST" action="{{ route('customer.logout') }}" data-confirm="Anda akan keluar dari akun Pemesan." data-confirm-title="Keluar dari akun?" data-icon="question" data-confirm-text="Ya, keluar">
                @csrf
                <button type="submit" class="side-link w-100 text-start border-0" title="Keluar">
                    <span class="mic"><i class="bi bi-box-arrow-right"></i></span>
                    <span class="lbl">Keluar</span>
                </button>
            </form>
        </div>
    </aside>
    <div class="backdrop" id="backdrop"></div>

    <div class="content">
        <div class="topbar">
            <div class="tb-left">
                <button class="icon-btn d-lg-none" id="burgerBtn" title="Menu"><i class="bi bi-list"></i></button>
                <button class="icon-btn" onclick="history.back()" title="Kembali"><i class="bi bi-arrow-left"></i></button>
                <div>
                    <div class="crumb"><span>WADUH</span><span class="sep">•</span><span>Area Pemesan</span></div>
                    <h1>@yield('title', 'Dashboard')</h1>
                </div>
            </div>
            <div class="ms-auto d-flex align-items-center gap-2">
                @yield('actions')

                <a href="{{ route('reservasi.checkout.form') }}" class="icon-btn" title="Keranjang">
                    <i class="bi bi-bag"></i>
                    @if ($cartN > 0)<span class="cart-dot">{{ $cartN > 99 ? '99+' : $cartN }}</span>@endif
                </a>

                <div class="tb-divider"></div>

                <div class="dropdown">
                    <button class="profile-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar">
                            @if ($fotoPemesan)
                                <img src="{{ $fotoPemesan }}" alt="{{ $pemesan->nama_lengkap }}">
                            @else
                                {{ $inisialPemesan }}
                            @endif
                        </span>
                        <span class="who d-none d-md-block">
                            <div class="nm">{{ $pemesan->nama_lengkap }}</div>
                            <div class="rl">Pemesan</div>
                        </span>
                        <i class="bi bi-chevron-down small d-none d-md-inline" style="color:var(--muted)"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end profile-menu">
                        <div class="pm-head">
                            <span class="avatar">
                                @if ($fotoPemesan)
                                    <img src="{{ $fotoPemesan }}" alt="{{ $pemesan->nama_lengkap }}">
                                @else
                                    {{ $inisialPemesan }}
                                @endif
                            </span>
                            <div><div class="nm">{{ $pemesan->nama_lengkap }}</div><div class="em">{{ $pemesan->email }}</div></div>
                        </div>
                        <a href="{{ route('customer.akun.profil') }}" class="dropdown-item w-100 text-start"><i class="bi bi-person-circle me-2"></i>Lihat Profil</a>
                    </div>
                </div>
            </div>
        </div>

        <main class="inner">
            {{-- Profil, Ubah Password, dan Detail Fasilitas (form Isi Jadwal) punya validasi
                 inline per-field sendiri (lihat @error di masing-masing form) — banner umum ini
                 disembunyikan di halaman-halaman itu supaya error tidak tampil dobel (banner + inline). --}}
            @if ($errors->any() && ! request()->routeIs('customer.akun.profil', 'customer.akun.password', 'reservasi.fasilitas.show'))
                <div class="err-card mb-3">
                    <span class="err-ic"><i class="bi bi-emoji-frown"></i></span>
                    <div>
                        <div class="fw-bold" style="color:#a12c2c">Ups, ada {{ $errors->count() }} hal yang perlu diperbaiki</div>
                        <div class="small text-muted mb-1">Lengkapi dulu ya, biar prosesnya bisa lanjut:</div>
                        <ul class="err-list">
                            @foreach ($errors->all() as $e)<li><i class="bi bi-arrow-right-short"></i>{{ $e }}</li>@endforeach
                        </ul>
                    </div>
                </div>
                <style>
                    .err-card { display:flex; gap:.9rem; align-items:flex-start; padding:1rem 1.15rem; background:var(--rose-tint); border:1px solid #fecdd3; border-left:4px solid var(--rose); border-radius:1rem; box-shadow:0 8px 22px rgba(180,60,60,.08); }
                    .err-ic { display:grid; place-items:center; flex:none; width:2.6rem; height:2.6rem; border-radius:.85rem; background:#fecdd3; color:#be123c; font-size:1.3rem; }
                    .err-list { list-style:none; margin:0; padding:0; font-size:.85rem; color:#9f1239; }
                    .err-list li { padding:.12rem 0; }
                    .err-list i { color:var(--rose); }
                </style>
            @endif

            @yield('content')
        </main>

        <footer class="site-footer">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="d-flex align-items-center gap-2"><span class="fw-bold">WADUH</span> · Wadah Akses Digital Unit Hunian BITC</span>
                <span>&copy; {{ now()->year }} WADUH · BITC Cimahi</span>
            </div>
        </footer>
    </div>
</div>
<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script>
    (() => {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('backdrop');
        const burger = document.getElementById('burgerBtn');
        if (!burger) return;
        const buka = () => { sidebar.classList.add('open'); backdrop.classList.add('show'); };
        const tutup = () => { sidebar.classList.remove('open'); backdrop.classList.remove('show'); };
        burger.addEventListener('click', () => sidebar.classList.contains('open') ? tutup() : buka());
        backdrop.addEventListener('click', tutup);
        sidebar.querySelectorAll('a:not([data-bs-toggle])').forEach(a => a.addEventListener('click', tutup));
    })();

    (() => {
        const collapseBtn = document.getElementById('collapseBtn');
        const setCollapsed = (on) => {
            document.body.classList.toggle('sidebar-collapsed', on);
            localStorage.setItem('pemesanSidebarCollapsed', on ? '1' : '0');
        };
        collapseBtn.addEventListener('click', () => setCollapsed(! document.body.classList.contains('sidebar-collapsed')));
        document.querySelectorAll('#sidebar [data-bs-toggle="collapse"]').forEach(a => {
            a.addEventListener('click', () => {
                if (document.body.classList.contains('sidebar-collapsed')) setCollapsed(false);
            });
        });
    })();

    @if (session('success'))
        Swal.fire({ icon: 'success', title: 'Berhasil!', text: @json(session('success')), confirmButtonColor: '#176b87', confirmButtonText: 'Oke' });
    @endif
    @if (session('error'))
        Swal.fire({ icon: 'error', title: 'Maaf, ada kendala', text: @json(session('error')), confirmButtonColor: '#176b87', confirmButtonText: 'Oke, mengerti' });
    @endif
    @if (session('checkout'))
        Swal.fire({
            icon: 'success',
            title: 'Reservasi Terkirim',
            html: 'Kode reservasi Anda:<br>'
                + '<div style="display:flex;align-items:center;justify-content:center;gap:.5rem;flex-wrap:wrap;margin-top:.3rem">'
                + '<strong style="font-size:1.6rem;color:#176b87;letter-spacing:.08em">{{ session('checkout')['kode_transaksi'] ?? '' }}</strong>'
                + '<button type="button" class="btn btn-sm btn-brand-outline" data-salin="{{ session('checkout')['kode_transaksi'] ?? '' }}"><i class="bi bi-clipboard me-1"></i>Salin</button>'
                + '</div>'
                + '<small class="text-muted">Simpan kode ini untuk mengecek status reservasi.</small>',
            confirmButtonColor: '#176b87',
            confirmButtonText: 'Oke, Mengerti',
        });
    @endif

    document.addEventListener('click', e => {
        const btn = e.target.closest('[data-salin]');
        if (!btn) return;
        const teks = btn.dataset.salin || '';
        const sukses = () => { const asli = btn.innerHTML; btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Tersalin!'; btn.disabled = true; setTimeout(() => { btn.innerHTML = asli; btn.disabled = false; }, 1800); };
        const gagal = () => { const asli = btn.innerHTML; btn.innerHTML = '<i class="bi bi-x-lg me-1"></i>Gagal, salin manual'; setTimeout(() => { btn.innerHTML = asli; }, 1800); };
        const salinFallback = () => { const ta = document.createElement('textarea'); ta.value = teks; ta.style.position = 'fixed'; ta.style.opacity = '0'; document.body.appendChild(ta); ta.focus(); ta.select(); let ok = false; try { ok = document.execCommand('copy'); } catch (err) { ok = false; } document.body.removeChild(ta); ok ? sukses() : gagal(); };
        if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(teks).then(sukses).catch(salinFallback); } else { salinFallback(); }
    });

    const labelDari = el => { const wadah = el.closest('.mb-3, .mb-2, [class*="col-"]') || el.parentElement; const lbl = wadah?.querySelector('.form-label, .pf-modal-lbl, .pw-modal-lbl, .aj-sublabel'); return lbl ? lbl.textContent.replace('*', '').trim() : 'Kolom ini'; };
    // Pesan singkat & to the point (bukan kalimat panjang) — konsisten dengan lang/id/validation.php.
    const pesanSalah = el => { const v = el.validity; if (v.valueMissing) { if (el.type === 'file') return 'Dokumen wajib dilampirkan.'; if (el.tagName === 'SELECT') return labelDari(el) + ' wajib dipilih.'; return labelDari(el) + ' wajib diisi.'; } if (v.typeMismatch && el.type === 'email') return 'Format email tidak valid.'; if (v.patternMismatch && el.type === 'tel') return labelDari(el) + ' wajib angka.'; if (v.patternMismatch) return labelDari(el) + ' formatnya salah.'; if (v.rangeUnderflow) return labelDari(el) + ' minimal ' + el.min + '.'; if (v.rangeOverflow) return labelDari(el) + ' maksimal ' + el.max + '.'; if (v.tooShort) return labelDari(el) + ' minimal ' + el.minLength + ' karakter.'; if (v.tooLong) return labelDari(el) + ' terlalu panjang.'; return labelDari(el) + ' formatnya salah.'; };
    const wakilTerlihat = el => { if (el.type === 'hidden') return el.closest('[data-jampicker]')?.querySelector('.jam-btn') || el; return el; };
    // .input-group (Bootstrap) / .pf-input-group / .pw-input-group (modal Edit Profil & Ubah Kata
    // Sandi) — WAJIB dicari sampai ke pembungkus terluarnya, bukan cuma <input>-nya sendiri, supaya
    // pesan error disisipkan SETELAH kotak ikon+input (baris baru, di bawah), bukan ikut jadi flex
    // item DI DALAM kotak itu (yang membuatnya tampil di samping, bukan di bawah).
    const induk = el => el.closest('.input-group, .pf-input-group, .pw-input-group, [data-jampicker]') || el;
    const tandai = el => { const target = wakilTerlihat(el); target.classList.add('is-salah'); const wadah = induk(target); wadah.parentElement.querySelector(':scope > .catatan-salah')?.remove(); const note = document.createElement('div'); note.className = 'catatan-salah'; note.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i>' + pesanSalah(el); wadah.insertAdjacentElement('afterend', note); };
    const bersihkan = el => { const target = wakilTerlihat(el); target.classList.remove('is-salah'); induk(target).parentElement.querySelector(':scope > .catatan-salah')?.remove(); };
    document.querySelectorAll('form').forEach(f => {
        f.setAttribute('novalidate', '');
        f.addEventListener('submit', e => {
            const salah = [...f.querySelectorAll('input, select, textarea')].filter(el => ! el.disabled && ! el.checkValidity());
            if (! salah.length) return;
            e.preventDefault(); e.stopImmediatePropagation();
            // Pesan error tampil DI BAWAH tiap field (tandai()) — sengaja tidak ada pop-up
            // lagi di sini, supaya tidak menutupi form dan pemesan tidak terasa "keluar"
            // dari form (mis. modal) saat validasi gagal.
            salah.forEach(tandai);
            const pertama = wakilTerlihat(salah[0]);
            pertama.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => pertama.focus({ preventScroll: true }), 350);
        }, true);
        f.addEventListener('input', e => bersihkan(e.target), true);
        f.addEventListener('change', e => bersihkan(e.target), true);
    });

    document.addEventListener('submit', e => {
        const f = e.target.closest('form[data-confirm]');
        if (!f || f.dataset.confirmed) return;
        e.preventDefault();
        Swal.fire({
            title: f.dataset.confirmTitle || 'Yakin?', text: f.dataset.confirm, icon: f.dataset.icon || 'question',
            showCancelButton: true, confirmButtonText: f.dataset.confirmText || 'Ya, lanjutkan', cancelButtonText: 'Batal',
            confirmButtonColor: f.dataset.confirmColor || '#176b87', cancelButtonColor: '#94a3b8', reverseButtons: true,
        }).then(r => { if (r.isConfirmed) { f.dataset.confirmed = 1; f.submit(); } });
    });

    document.addEventListener('click', e => {
        const a = e.target.closest('a[data-confirm]');
        if (!a) return;
        e.preventDefault();
        Swal.fire({
            title: a.dataset.confirmTitle || 'Yakin?', text: a.dataset.confirm, icon: a.dataset.icon || 'question',
            showCancelButton: true, confirmButtonText: a.dataset.confirmText || 'Ya, lanjutkan', cancelButtonText: 'Batal',
            confirmButtonColor: a.dataset.confirmColor || '#176b87', cancelButtonColor: '#94a3b8', reverseButtons: true,
        }).then(r => { if (r.isConfirmed) window.location.href = a.href; });
    });
</script>
</body>
</html>