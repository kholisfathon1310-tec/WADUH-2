<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') | WADUH Admin</title>
    <link href="{{ asset('vendor/fonts/fonts.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ═══════════════════════════════════════════════════════════════
           WADUH – Area Admin. Token & komponen diselaraskan dengan Area
           Pemesan (resources/views/layouts/customer.blade.php) supaya
           bahasa visualnya sama — hanya isi/menu yang beda.
           ═══════════════════════════════════════════════════════════════ */
        :root {
            --ink:#0f172a; --muted:#64748b; --soft:#94a3b8;
            --primary:#176b87; --primary-dark:#0f526b; --primary-darker:#0c3648;
            --primary-soft:#e6f2f4; --primary-softer:#c9e6ea; --primary-tint:#eef7f8;
            --accent:#178f87; --accent-light:#24aa9a; --teal:#24aa9a;
            --amber-soft:#fef3c7; --amber:#d97706; --amber-tint:#fef9e7;
            --rose:#e11d48; --rose-soft:#fff1f2; --rose-tint:#fef2f4;
            --emerald:#059669; --emerald-soft:#d1fae5;
            --surface:#f4f7f9; --surface-2:#eef3f6;
            --line:#e2e8f0; --line-soft:#eef2f6;
            --card-shadow:0 1px 3px rgba(15,23,42,.05), 0 4px 12px -6px rgba(15,60,73,.08);
            --card-shadow-lg:0 20px 42px -18px rgba(15,60,73,.2), 0 4px 12px -4px rgba(15,60,73,.08);
            --side:#0e1e31; --side2:#0a1523;
            --l1:#2f7fd1; --l2:#24aa9a; --l3a:#7c5cd6; --l3b:#e8833a; --l5:#d6527c;
        }
        * { scrollbar-width:thin; scrollbar-color:#cbd5e1 transparent; }
        *::-webkit-scrollbar { width:6px; height:6px; }
        *::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:9999px; }
        *::-webkit-scrollbar-thumb:hover { background:#94a3b8; }
        *::-webkit-scrollbar-track { background:transparent; }
        body {
            font-family:'Plus Jakarta Sans',sans-serif; color:var(--ink); line-height:1.55; -webkit-font-smoothing:antialiased;
            background:
                radial-gradient(48rem 28rem at 108% -8%, #d7efe9 0%, transparent 55%),
                radial-gradient(38rem 24rem at -12% 10%, #dfeaf5 0%, transparent 55%),
                radial-gradient(30rem 20rem at 50% 110%, #e8f4f2 0%, transparent 55%),
                var(--surface);
            background-attachment:fixed;
        }
        h1,h2,h3,h4,h5,.brand-font { font-family:'Plus Jakarta Sans',sans-serif; letter-spacing:-.015em; font-weight:800; }
        ::selection { background:var(--primary-softer); color:var(--primary-darker); }

        @keyframes riseIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:none; } }
        [data-reveal] { animation:riseIn .45s cubic-bezier(.2,.7,.3,1) both; }
        @media (prefers-reduced-motion: reduce) { [data-reveal] { animation:none; } }

        /* Frame — konsep BARU: sidebar terang, topbar flush (bukan pill mengambang) */
        .frame { display:flex; min-height:100vh; }
        .sidebar { width:272px; flex:none;
            background:linear-gradient(180deg, #ffffff 0%, #fbfdfd 100%);
            border-right:1px solid var(--line); color:var(--ink); display:flex; flex-direction:column;
            position:sticky; top:0; height:100vh; overflow-y:auto; z-index:1046;
            transition:width .22s ease; box-shadow:2px 0 12px -6px rgba(15,60,73,.06); }
        .content { flex:1; min-width:0; min-height:100vh; display:flex; flex-direction:column; }
        .backdrop { display:none; }
        @media (max-width: 991.98px) {
            .sidebar { position:fixed; left:-280px; transition:left .25s ease; height:100vh; box-shadow:14px 0 40px -20px rgba(15,23,42,.25); }
            .sidebar.open { left:0; }
            .backdrop { display:none; position:fixed; inset:0; background:rgba(8,15,25,.5); backdrop-filter:blur(2px); z-index:1044; opacity:0; transition:opacity .25s ease; }
            .backdrop.show { display:block; opacity:1; }
        }

        /* Sidebar diciutkan (desktop) — hanya ikon, teks & submenu disembunyikan. */
        @media (min-width: 992px) {
            body.sidebar-collapsed .sidebar { width:84px; }
            body.sidebar-collapsed .side-section { display:none; }
            body.sidebar-collapsed .side-link { justify-content:center; padding:.62rem; }
            body.sidebar-collapsed .side-link .lbl,
            body.sidebar-collapsed .side-link .caret,
            body.sidebar-collapsed .side-link .badge { display:none; }
            body.sidebar-collapsed .side-sub { display:none !important; }
        }
        /* Sidebar */
        /* Logo dobel-fungsi sebagai tombol ciutkan/lebarkan sidebar — klik logo untuk
           toggle, tidak perlu tombol terpisah. */
        .side-brand-row { display:flex; align-items:center; justify-content:center; padding:1.15rem 1.4rem;
            border-bottom:1px solid var(--line-soft);
            background:linear-gradient(135deg, rgba(240,253,250,.6), rgba(255,255,255,0)); }
        .side-brand { display:flex; align-items:center; justify-content:center; gap:.6rem; min-width:0; border:none; background:none; padding:.4rem .5rem; border-radius:.85rem; cursor:pointer; transition:background .15s ease; width:100%; }
        .side-brand:hover { background:var(--primary-soft); }
        .side-brand .brand-mark { display:block; max-width:100%; }
        .side-brand .brand-mark img { height:auto; width:100%; max-width:4.4rem; display:block; }
        /* Sidebar ciut — logo mengecil & tetap terpusat, tetap bisa dipencet untuk melebarkan
           lagi (satu-satunya cara membuka lagi saat sudah ciut di desktop). */
        body.sidebar-collapsed .side-brand-row { padding:1.15rem .5rem; justify-content:center; }
        body.sidebar-collapsed .side-brand .brand-mark img { max-width:2.3rem; }
        .side-section { padding:1.1rem 1.4rem .55rem; font-size:.62rem; font-weight:800; letter-spacing:.14em; text-transform:uppercase; color:var(--soft); }
        .side-nav { list-style:none; margin:0; padding:0 .8rem; display:flex; flex-direction:column; gap:.15rem; }
        .side-link { position:relative; display:flex; align-items:center; gap:.7rem; padding:.7rem .85rem; color:#475569; text-decoration:none; border-radius:.75rem; font-weight:600; font-size:.87rem; transition:background .18s ease, color .18s ease; }
        .side-link .mic { display:grid; place-items:center; width:1.4rem; height:1.4rem; font-size:1rem; color:#94a3b8; flex:none; transition:color .18s ease; }
        .side-link:hover { color:var(--ink); background:var(--primary-soft); }
        .side-link:hover .mic { color:var(--primary); }
        .side-link.active { color:var(--primary-dark);
            background:linear-gradient(90deg, var(--primary-soft), var(--primary-tint));
            font-weight:800; box-shadow:inset 0 0 0 1px var(--primary-softer); }
        .side-link.active .mic { color:var(--primary); }
        .side-link .caret { margin-left:auto; transition:transform .2s; font-size:.7rem; opacity:.5; }
        .side-link[aria-expanded="true"] .caret { transform:rotate(90deg); }
        .side-link .badge { margin-left:auto; font-size:.65rem !important; font-weight:800 !important;
            background:var(--primary) !important; color:#fff !important; border-radius:9999px !important; padding:.15rem .5rem !important; }
        .side-link.active .badge { background:var(--primary-dark) !important; }
        .side-sub { list-style:none; margin:.15rem 0 .3rem; padding:0 0 0 2.5rem; }
        .side-sub a { display:flex; align-items:center; gap:.5rem; padding:.4rem .7rem; margin:.05rem 0; color:#64748b; text-decoration:none; border-left:2px solid var(--line); border-radius:0 .55rem .55rem 0; font-size:.8rem; font-weight:500; transition:all .18s ease; }
        .side-sub a:hover { color:var(--primary-dark); background:var(--primary-soft); border-left-color:var(--primary); }
        .side-sub a.active { color:var(--primary-dark); border-left-color:var(--primary); background:var(--primary-soft); font-weight:700; }
        .side-sub .badge { margin-left:auto; }
        .side-bottom { margin-top:auto; padding:1rem .8rem 1.25rem; border-top:1px solid var(--line-soft); }
        .side-bottom .side-link { color:var(--rose); font-weight:700; }
        .side-bottom .side-link .mic { color:var(--rose); }
        .side-bottom .side-link:hover { background:var(--rose-soft); color:#be123c; }
        .side-bottom .side-link:hover .mic { color:#be123c; }

        /* Topbar — flush, tanpa margin/rounded, menyatu dengan tepi konten */
        .topbar { background:rgba(255,255,255,.85); backdrop-filter:blur(14px); border-bottom:1px solid var(--line);
            padding:.9rem 1.6rem; display:flex; align-items:center; gap:1rem; position:sticky; top:0; z-index:1020; min-height:5rem; }
        .topbar h1 { font-size:1.1rem; font-weight:800; margin:0; color:var(--ink); line-height:1.2; }
        .topbar .crumb { font-size:.65rem; color:var(--soft); font-weight:800; letter-spacing:.12em; text-transform:uppercase; }
        .burger { display:grid; place-items:center; width:2.5rem; height:2.5rem; border:1px solid var(--line); background:#fff; color:#475569;
            border-radius:.8rem; transition:background .15s, color .15s, border-color .15s, transform .15s; flex:none; box-shadow:0 1px 2px rgba(15,23,42,.04); }
        .burger:hover { background:var(--primary-soft); color:var(--primary-dark); border-color:var(--primary-softer); transform:translateY(-1px); }

        /* Bel notifikasi */
        .bell-btn { position:relative; border:1px solid var(--line); background:#fff; border-radius:.8rem; width:2.5rem; height:2.5rem; display:grid; place-items:center; color:#475569; transition:background .15s, color .15s, border-color .15s, transform .15s; flex:none; box-shadow:0 1px 2px rgba(15,23,42,.04); }
        .bell-btn:hover { background:var(--primary-soft); color:var(--primary); border-color:var(--primary-softer); transform:translateY(-1px); }
        .bell-btn .dot { position:absolute; top:-.3rem; right:-.3rem; min-width:1.2rem; height:1.2rem; padding:0 .3rem; border-radius:9999px; background:var(--amber); color:#fff; font-size:.62rem; font-weight:800; display:grid; place-items:center; border:2px solid #fff; }

        .day-chip { align-items:center; background:#fff; border:1px solid var(--line); color:#475569; font-size:.78rem; font-weight:700; padding:.45rem .85rem; border-radius:.8rem; box-shadow:0 1px 2px rgba(15,23,42,.04); }

        /* Profil admin (dropdown) */
        .tb-divider { width:1px; height:1.75rem; background:var(--line); margin:0 .25rem; }
        .profile-btn { display:flex; align-items:center; gap:.65rem; border:1px solid transparent; background:transparent; border-radius:.85rem; padding:.3rem .6rem .3rem .3rem; transition:background .15s, border-color .15s; }
        .profile-btn:hover { background:var(--primary-soft); border-color:var(--primary-softer); }
        .profile-btn .avatar { display:grid; place-items:center; width:2.4rem; height:2.4rem; border-radius:.75rem; background:linear-gradient(135deg,var(--primary-dark),var(--primary)); color:#fff; font-weight:800; font-size:.9rem; flex:none; box-shadow:0 6px 14px -3px rgba(23,107,135,.45); overflow:hidden; }
        .profile-btn .who { text-align:left; line-height:1.15; }
        .profile-btn .who .nm { font-size:.8rem; font-weight:800; color:var(--ink); max-width:9rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .profile-btn .who .rl { font-size:.68rem; color:var(--soft); font-weight:600; margin-top:.1rem; }
        .profile-menu { border:1px solid var(--line); border-radius:1rem; box-shadow:0 18px 40px -14px rgba(15,60,73,.22); padding:.5rem; min-width:230px; }
        .profile-menu .pm-head { display:flex; align-items:center; gap:.65rem; padding:.55rem .6rem .75rem; border-bottom:1px solid var(--line-soft); margin-bottom:.4rem; }
        .profile-menu .pm-head .avatar { display:grid; place-items:center; width:2.5rem; height:2.5rem; border-radius:.8rem; background:linear-gradient(135deg,var(--primary-dark),var(--primary)); color:#fff; font-weight:800; flex:none; overflow:hidden; }
        .profile-menu .pm-head .nm { font-weight:800; font-size:.85rem; color:var(--ink); }
        .profile-menu .pm-head .em { font-size:.72rem; color:var(--muted); word-break:break-all; }
        .profile-menu .dropdown-item { border-radius:.6rem; padding:.5rem .6rem; font-size:.83rem; font-weight:600; color:#475569; }
        .profile-menu .dropdown-item:hover { background:var(--primary-soft); color:var(--primary-dark); }
        .profile-menu .dropdown-item.text-danger:hover { background:var(--rose-soft); color:#be123c !important; }

        main.inner { padding:1.75rem 1.6rem 2rem; }

        /* Components — sengaja disamakan dengan .xcard/.stat-tile/.btn-brand dst di Area Pemesan */
        .xcard { background:#fff; border:1px solid var(--line); border-radius:1.25rem; box-shadow:var(--card-shadow); transition:transform .22s ease, box-shadow .22s ease, border-color .22s ease; }
        .xcard.hover:hover, a.xcard:hover { transform:translateY(-3px); box-shadow:var(--card-shadow-lg); border-color:var(--primary-softer); }
        .xcard .xhead { padding:1.05rem 1.3rem; border-bottom:1px solid var(--line); font-weight:700; display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:.5rem; background:#fbfdfe; border-radius:1.25rem 1.25rem 0 0; }
        .stat-card { border-radius:1.25rem; border:none; color:#fff; padding:1.2rem 1.3rem; position:relative; overflow:hidden; box-shadow:0 12px 28px -10px rgba(15,60,73,.28); transition:transform .2s ease, box-shadow .2s ease; }
        .stat-card:hover { transform:translateY(-3px); box-shadow:0 18px 34px -10px rgba(15,60,73,.35); }
        .stat-card::after { content:''; position:absolute; right:-2.2rem; bottom:-2.6rem; width:8rem; height:8rem; border-radius:50%; background:rgba(255,255,255,.12); }
        .stat-card .ic { position:absolute; right:1rem; top:1rem; font-size:1.35rem; opacity:.85; }
        .stat-card .v { font-size:1.75rem; font-weight:800; font-family:'Plus Jakarta Sans',sans-serif; line-height:1.1; }
        .stat-card small { opacity:.9; font-weight:600; letter-spacing:.01em; }
        /* Tabel modern — kontras jelas antara header, baris, dan latar */
        .table { --bs-table-hover-bg:var(--primary-soft); margin-bottom:0; }
        .table thead th { background:linear-gradient(180deg,var(--surface),var(--surface-2)) !important; color:var(--ink); font-size:.73rem; font-weight:800; text-transform:uppercase; letter-spacing:.07em; padding:.85rem 1.1rem; border-bottom:2px solid var(--line); white-space:nowrap; }
        .table td { vertical-align:middle; padding:1rem 1.1rem; border-color:var(--line-soft); background:#fff; font-size:.885rem; border-left:0; border-right:0; }
        .table tbody tr:nth-child(even) td { background:var(--surface-2); }
        .table tbody tr:hover td { background:var(--bs-table-hover-bg); }
        .table tbody tr:last-child td { border-bottom:0; }
        .table tbody tr { transition:background .15s ease; }

        /* Chip status — persis palet & bentuk chip di Area Pemesan */
        .chip { display:inline-flex; align-items:center; gap:.4rem; font-size:.7rem; font-weight:800; padding:.3rem .7rem; border-radius:9999px; white-space:nowrap; letter-spacing:.01em; border:1px solid; }
        .chip::before { content:''; width:.42rem; height:.42rem; border-radius:50%; background:currentColor; flex:none; }
        .chip.menunggu { background:var(--amber-tint); color:#a16207; border-color:#fde68a; }
        .chip.menunggu::before { background:#f59e0b; animation:chipPulse 1.6s infinite; }
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
        @keyframes chipPulse { 0%,100% { opacity:1; transform:scale(1); } 50% { opacity:.4; transform:scale(1.15); } }
        .initial-chip { display:inline-grid; place-items:center; width:2.2rem; height:2.2rem; border-radius:.75rem; color:#fff; font-weight:700; font-size:.85rem; background:linear-gradient(135deg,var(--primary-dark),var(--primary)); flex:none; box-shadow:0 6px 14px -4px rgba(23,107,135,.4); }
        .cell-main { font-weight:600; }
        .cell-sub { font-size:.78rem; color:var(--muted); }
        .btn-brand { background:linear-gradient(135deg,var(--primary-dark),var(--primary)); border-color:var(--primary-dark); color:#fff; font-weight:700; border-radius:.85rem;
            box-shadow:0 8px 18px -6px rgba(23,107,135,.4); transition:background .15s ease, border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
        .btn-brand:hover { background:linear-gradient(135deg,var(--primary-darker),var(--primary-dark)); border-color:var(--primary-darker); color:#fff; transform:translateY(-1px); box-shadow:0 12px 22px -6px rgba(23,107,135,.55); }
        .btn-brand-outline { color:var(--primary-dark); border:1px solid var(--line); border-radius:.85rem; font-weight:700; background:#fff; transition:all .15s ease; }
        .btn-brand-outline:hover { color:var(--primary-darker); border-color:var(--primary); background:var(--primary-soft); transform:translateY(-1px); }
        .btn { border-radius:.85rem; font-weight:700; }
        .btn-sm { border-radius:.7rem; font-weight:700; }
        .form-control,.form-select { border-radius:.75rem; border-color:var(--line); padding:.6rem .9rem; font-size:.88rem; background:#fff; }
        .form-control-sm,.form-select-sm { border-radius:.65rem; padding:.4rem .65rem; font-size:.82rem; }
        .form-control:focus,.form-select:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(23,107,135,.12); }
        .form-label { font-weight:700; font-size:.78rem; color:#334155; text-transform:uppercase; letter-spacing:.06em; }
        .input-group-text { background:var(--primary-soft); border-color:var(--line); color:var(--primary); border-radius:.75rem 0 0 .75rem; }
        .input-group > .form-control,.input-group > .form-select { border-radius:0 .75rem .75rem 0; }
        .avail { display:inline-flex; align-items:center; gap:.35rem; font-size:.72rem; font-weight:700; padding:.28rem .6rem; border-radius:9999px; border:1px solid; }
        .avail.hijau { background:var(--emerald-soft); color:#047857; border-color:#a7f3d0; }
        .avail.kuning { background:var(--amber-tint); color:#a16207; border-color:#fde68a; }
        .avail.merah { background:var(--rose-tint); color:#be123c; border-color:#fecdd3; }

        .err-card { display:flex; gap:.9rem; align-items:flex-start; padding:1rem 1.15rem; background:var(--rose-tint); border:1px solid #fecdd3; border-left:4px solid var(--rose); border-radius:1rem; box-shadow:0 8px 22px rgba(180,60,60,.08); }
        .err-ic { display:grid; place-items:center; flex:none; width:2.6rem; height:2.6rem; border-radius:.85rem; background:#fecdd3; color:#be123c; font-size:1.3rem; }
        .err-list { list-style:none; margin:0; padding:0; font-size:.85rem; color:#9f1239; }
        .err-list li { padding:.12rem 0; }
        .err-list i { color:var(--rose); }

        /* Hero banner halaman — dipakai di halaman non-dashboard (mis. Monitoring) supaya
           konsisten dengan dash-hero, tanpa perlu inline style berulang per halaman. */
        .page-hero { position:relative; overflow:hidden; border-radius:1.35rem; padding:1.6rem 1.8rem;
            background:linear-gradient(135deg, var(--primary), var(--primary-dark)); color:#fff;
            box-shadow:0 20px 42px -18px rgba(15,60,73,.35); }
        .page-hero::before { content:''; position:absolute; inset:0; pointer-events:none;
            background:radial-gradient(26rem 16rem at 105% -10%, rgba(255,255,255,.16), transparent 55%); }
        .page-hero > * { position:relative; z-index:1; }

        .alert { border-radius:1rem; border:1px solid; font-size:.85rem; }
        .alert-warning { background:var(--amber-tint); border-color:#fde68a; color:#854d0e; }
        .alert-danger { background:var(--rose-tint); border-color:#fecdd3; color:#9f1239; }
        .alert-success { background:var(--emerald-soft); border-color:#a7f3d0; color:#065f46; }
        .alert-info { background:var(--primary-soft); border-color:var(--primary-softer); color:var(--primary-darker); }

        /* Validasi klien ramah */
        .is-salah { border-color:var(--rose) !important; background:var(--rose-tint) !important; box-shadow:0 0 0 3px rgba(225,29,72,.12) !important; animation:goyang .3s; }
        @keyframes goyang { 25% { transform:translateX(-4px); } 75% { transform:translateX(4px); } }
        .catatan-salah { display:flex; align-items:center; gap:.3rem; color:#be123c; font-size:.76rem; font-weight:600; margin-top:.35rem; }
        /* SweetAlert2: matikan pointer-events overlay begitu animasi fade-out mulai, supaya klik berikutnya (mis. buka modal lagi) tidak tertelan. */
        .swal2-backdrop-hide { pointer-events: none !important; }
    </style>
</head>
<body>
<script>
    // Terapkan status ciut/lebar sidebar sebelum konten dirender, biar tidak "kedip" saat load.
    if (localStorage.getItem('adminSidebarCollapsed') === '1') document.body.classList.add('sidebar-collapsed');
</script>
@php
    $me = Auth::guard('admin')->user();
    $sideLantai = \App\Models\Lantai::orderBy('id_lantai')->get(['id_lantai', 'nomor_lantai']);
    $menungguN = \App\Models\Reservasi::where('status_reservasi', 'Menunggu')->count();
    $warnaLantai = ['1' => '#2f7fd1', '2' => '#24aa9a', '3A' => '#7c5cd6', '3B' => '#e8833a', '5' => '#d6527c'];
    $warnaStatus = ['Menunggu' => '#e5b94e', 'Disetujui' => '#25b47e', 'Ditolak' => '#d95757', 'Dibatalkan' => '#495663', 'Selesai' => '#1d4ed8', 'Kadaluwarsa' => '#7c3aed'];
    $curStatus = request('status');
    $curLantai = request('lantai');
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
                <a class="side-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}" title="Dashboard">
                    <span class="mic"><i class="bi bi-speedometer2"></i></span> <span class="lbl">Dashboard</span>
                </a>
            </li>

            <li>
                <a class="side-link {{ request()->routeIs('admin.monitoring*') ? 'active' : '' }}" data-bs-toggle="collapse" href="#subMonitoring"
                   aria-expanded="{{ request()->routeIs('admin.monitoring*') ? 'true' : 'false' }}" title="Monitoring">
                    <span class="mic"><i class="bi bi-grid-3x3-gap"></i></span>
                    <span class="lbl">Monitoring</span> <i class="bi bi-chevron-right caret"></i>
                </a>
                <ul class="side-sub collapse {{ request()->routeIs('admin.monitoring*') ? 'show' : '' }}" id="subMonitoring">
                    @foreach ($sideLantai as $l)
                        <li><a class="{{ request()->routeIs('admin.monitoring') && (string)$curLantai === (string)$l->id_lantai ? 'active' : '' }}"
                               href="{{ route('admin.monitoring', ['lantai' => $l->id_lantai]) }}">Lantai {{ $l->nomor_lantai }}</a></li>
                    @endforeach
                </ul>
            </li>

            <li>
                <a class="side-link {{ request()->routeIs('admin.reservasi*') ? 'active' : '' }}" href="{{ route('admin.reservasi.index') }}" title="Data Reservasi">
                    <span class="mic"><i class="bi bi-journal-check"></i></span>
                    <span class="lbl">Data Reservasi</span>
                    @if ($menungguN > 0)<span class="badge rounded-pill text-bg-warning ms-auto">{{ $menungguN }}</span>@endif
                </a>
            </li>

            <li>
                <a class="side-link {{ request()->routeIs('admin.laporan*') ? 'active' : '' }}" href="{{ route('admin.laporan') }}" title="Laporan">
                    <span class="mic"><i class="bi bi-file-earmark-bar-graph"></i></span>
                    <span class="lbl">Laporan</span>
                </a>
            </li>

            <li>
                <a class="side-link {{ request()->routeIs('admin.profil*') ? 'active' : '' }}" href="{{ route('admin.profil') }}" title="Profil">
                    <span class="mic"><i class="bi bi-person-circle"></i></span>
                    <span class="lbl">Profil</span>
                </a>
            </li>
        </ul>

        <div class="side-bottom">
            <div class="side-nav" style="padding-top:.3rem;margin-top:.3rem;border-top:1px solid var(--line)">
                <form method="POST" action="{{ route('admin.logout') }}" data-confirm="Keluar dari panel admin?" data-icon="warning">@csrf
                    <button type="submit" class="side-link w-100 text-start border-0 bg-transparent" title="Keluar">
                        <span class="mic"><i class="bi bi-box-arrow-right"></i></span>
                        <span class="lbl">Keluar</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>
    <div class="backdrop" id="backdrop"></div>

    <div class="content">
        <div class="topbar">
            <button class="burger d-lg-none" id="burgerBtn"><i class="bi bi-list"></i></button>
            <button class="burger" onclick="history.back()" title="Kembali ke halaman sebelumnya"><i class="bi bi-arrow-left"></i></button>
            <div>
                <div class="crumb">WADUH Admin</div>
                <h1>@yield('title', 'Admin')</h1>
            </div>
            <div class="ms-auto d-flex align-items-center gap-2">
                @yield('actions')
                <span class="day-chip d-none d-md-inline-flex"><i class="bi bi-calendar3 me-1"></i>{{ now()->translatedFormat('d M Y') }}</span>

                <a href="{{ route('admin.reservasi.index', ['status' => 'Menunggu']) }}" class="bell-btn" title="Reservasi menunggu persetujuan">
                    <i class="bi bi-bell"></i>
                    @if ($menungguN > 0)<span class="dot">{{ $menungguN > 99 ? '99+' : $menungguN }}</span>@endif
                </a>

                @php $fotoAdmin = $me?->fotoUrl(); @endphp
                <div class="dropdown">
                    <button class="profile-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar">
                            @if ($fotoAdmin)<img src="{{ $fotoAdmin }}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">
                            @else{{ strtoupper(substr($me?->nama_admin ?? 'A', 0, 1)) }}@endif
                        </span>
                        <span class="who d-none d-md-block">
                            <div class="nm">{{ $me?->nama_admin }}</div>
                            <div class="rl">Administrator</div>
                        </span>
                        <i class="bi bi-chevron-down small d-none d-md-inline" style="color:var(--muted)"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end profile-menu">
                        <div class="pm-head">
                            <span class="avatar">
                                @if ($fotoAdmin)<img src="{{ $fotoAdmin }}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">
                                @else{{ strtoupper(substr($me?->nama_admin ?? 'A', 0, 1)) }}@endif
                            </span>
                            <div><div class="nm">{{ $me?->nama_admin }}</div><div class="em">{{ $me?->email }}</div></div>
                        </div>
                        <a href="{{ route('admin.profil') }}" class="dropdown-item w-100 text-start"><i class="bi bi-person-circle me-2"></i>Lihat Profil</a>
                    </div>
                </div>
            </div>
        </div>

        <main class="inner">
            @if ($errors->any())
                <div class="err-card mb-3">
                    <span class="err-ic"><i class="bi bi-exclamation-triangle"></i></span>
                    <div>
                        <div class="fw-bold" style="color:#a12c2c">Periksa kembali, terdapat {{ $errors->count() }} isian belum benar</div>
                        <ul class="err-list mb-0 mt-1">
                            @foreach ($errors->all() as $e)<li><i class="bi bi-arrow-right-short"></i>{{ $e }}</li>@endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script>
    // Kalau halaman ini dipulihkan dari bfcache (mis. admin pencet "Kembali" setelah
    // setujui/tolak pemesanan), muat ulang dari server. Header Cache-Control: no-store
    // (lihat routes/web.php) saja TIDAK selalu cukup — Chrome versi baru tetap boleh
    // menyimpan halaman ber-no-store ke bfcache — jadi pengecekan pageshow ini tetap
    // dibutuhkan supaya data & tombol aksi yang tampil selalu yang terbaru, bukan
    // salinan lama (yang bisa saja masih menampilkan modal konfirmasi yang belum tertutup).
    window.addEventListener('pageshow', (e) => {
        if (e.persisted) window.location.reload();
    });

    // Sidebar mobile: buka/tutup lewat tombol burger atau tap di luar (backdrop).
    (() => {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('backdrop');
        const burger = document.getElementById('burgerBtn');
        const buka = () => { sidebar.classList.add('open'); backdrop.classList.add('show'); };
        const tutup = () => { sidebar.classList.remove('open'); backdrop.classList.remove('show'); };
        burger.addEventListener('click', () => sidebar.classList.contains('open') ? tutup() : buka());
        backdrop.addEventListener('click', tutup);
        sidebar.querySelectorAll('a:not([data-bs-toggle])').forEach(a => a.addEventListener('click', tutup));
    })();

    // Sidebar desktop: ciutkan/lebarkan, status disimpan supaya tetap sama di halaman berikutnya.
    (() => {
        const collapseBtn = document.getElementById('collapseBtn');
        const setCollapsed = (on) => {
            document.body.classList.toggle('sidebar-collapsed', on);
            localStorage.setItem('adminSidebarCollapsed', on ? '1' : '0');
        };
        collapseBtn.addEventListener('click', () => setCollapsed(! document.body.classList.contains('sidebar-collapsed')));

        // Kalau sidebar sedang ciut, klik menu yang punya submenu (mis. Monitoring) akan
        // melebarkan sidebar dulu supaya submenu-nya kelihatan, bukan cuma toggle tak terlihat.
        document.querySelectorAll('#sidebar [data-bs-toggle="collapse"]').forEach(a => {
            a.addEventListener('click', () => {
                if (document.body.classList.contains('sidebar-collapsed')) setCollapsed(false);
            });
        });
    })();

    // Pop-up flash message — semua pakai modal penuh (bukan toast kecil di pojok).
    @if (session('success'))
        Swal.fire({ icon: 'success', title: 'Berhasil!', text: @json(session('success')), confirmButtonColor: '#176b87', confirmButtonText: 'Oke' });
    @endif
    @if (session('error'))
        Swal.fire({ icon: 'error', title: 'Gagal!', text: @json(session('error')), confirmButtonColor: '#176b87', confirmButtonText: 'Oke, mengerti' });
    @endif

    // ===== Validasi klien ramah (mengganti bubble bawaan browser) =====
    const labelDari = el => {
        const wadah = el.closest('.mb-3, .mb-2, [class*="col-"]') || el.parentElement;
        const lbl = wadah?.querySelector('.form-label, .pf-modal-lbl, .pw-modal-lbl, .aj-sublabel');
        return lbl ? lbl.textContent.replace('*', '').trim() : 'Kolom ini';
    };
    // Pesan singkat & to the point (bukan kalimat panjang) — konsisten dengan lang/id/validation.php.
    const pesanSalah = el => {
        const v = el.validity;
        if (v.valueMissing) return labelDari(el) + ' wajib diisi.';
        if (v.typeMismatch && el.type === 'email') return 'Format email tidak valid.';
        if (v.patternMismatch && el.type === 'tel') return labelDari(el) + ' wajib angka.';
        if (v.patternMismatch) return labelDari(el) + ' formatnya salah.';
        if (v.rangeUnderflow) return labelDari(el) + ' minimal ' + el.min + '.';
        if (v.rangeOverflow) return labelDari(el) + ' maksimal ' + el.max + '.';
        if (v.tooShort) return labelDari(el) + ' minimal ' + el.minLength + ' karakter.';
        if (v.tooLong) return labelDari(el) + ' terlalu panjang.';
        return labelDari(el) + ' formatnya salah.';
    };
    // .input-group (Bootstrap) / .pf-input-group / .pw-input-group (modal Edit Profil & Ubah Kata
    // Sandi) — WAJIB dicari sampai ke pembungkus terluarnya, bukan cuma <input>-nya sendiri, supaya
    // pesan error disisipkan SETELAH kotak ikon+input (baris baru, di bawah), bukan ikut jadi flex
    // item DI DALAM kotak itu (yang membuatnya tampil di samping, bukan di bawah).
    const induk = el => el.closest('.input-group, .pf-input-group, .pw-input-group') || el;
    const tandai = el => {
        el.classList.add('is-salah');
        const wadah = induk(el);
        wadah.parentElement.querySelector(':scope > .catatan-salah')?.remove();
        const note = document.createElement('div');
        note.className = 'catatan-salah';
        note.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i>' + pesanSalah(el);
        wadah.insertAdjacentElement('afterend', note);
    };
    const bersihkan = el => {
        el.classList.remove('is-salah');
        induk(el).parentElement.querySelector(':scope > .catatan-salah')?.remove();
    };
    document.querySelectorAll('form').forEach(f => {
        f.setAttribute('novalidate', '');
        f.addEventListener('submit', e => {
            const salah = [...f.querySelectorAll('input, select, textarea')].filter(el => ! el.disabled && ! el.checkValidity());
            if (! salah.length) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            // Pesan error tampil DI BAWAH tiap field (tandai()) — sengaja tidak ada pop-up
            // lagi di sini, supaya tidak menutupi form dan pemesan/admin tidak terasa
            // "keluar" dari form saat validasi gagal.
            salah.forEach(tandai);
            salah[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => salah[0].focus({ preventScroll: true }), 350);
        }, true);
        f.addEventListener('input', e => bersihkan(e.target), true);
        f.addEventListener('change', e => bersihkan(e.target), true);
    });

    // Dialog konfirmasi untuk form/tautan ber-atribut data-confirm — didelegasikan ke document
    // (bukan dipasang per elemen) supaya otomatis berlaku juga untuk konten yang disisipkan
    // belakangan lewat AJAX (mis. hasil filter interaktif), tanpa perlu pasang ulang listener.
    // Form ber-atribut data-nav-replace redirect balik ke URL halaman yang SAMA (mis.
    // setujui/tolak di halaman detail) — submit form NATIVE akan menambah entri histori
    // kedua utk URL yang sama, jadi tombol back browser perlu ditekan DUA kali baru benar-benar
    // keluar. Dikirim lewat fetch lalu location.replace() supaya entri histori DIGANTI, bukan
    // ditambah, jadi satu kali tombol back sudah cukup. Form lain (mis. cetak faktur — respons
    // langsung berupa file PDF, bukan redirect halaman) TETAP submit native seperti biasa.
    const kirimTanpaDuplikatHistori = f => {
        fetch(f.action, { method: f.method || 'POST', body: new FormData(f), headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => window.location.replace(res.url))
            .catch(() => f.submit()); // fallback: submit native kalau fetch gagal (mis. offline)
    };

    document.addEventListener('submit', e => {
        const f = e.target.closest('form[data-confirm]');
        if (!f || f.dataset.confirmed) return;
        e.preventDefault();
        Swal.fire({
            title: f.dataset.confirmTitle || 'Yakin?',
            text: f.dataset.confirm,
            icon: f.dataset.icon || 'warning',
            showCancelButton: true,
            confirmButtonText: f.dataset.confirmText || 'Ya, lanjutkan',
            cancelButtonText: 'Batal',
            confirmButtonColor: f.dataset.confirmColor || '#176b87',
            cancelButtonColor: '#8a97a5',
            reverseButtons: true,
        }).then(r => {
            if (! r.isConfirmed) return;
            f.dataset.confirmed = 1;
            f.dataset.navReplace !== undefined ? kirimTanpaDuplikatHistori(f) : f.submit();
        });
    });

    document.addEventListener('click', e => {
        const a = e.target.closest('a[data-confirm]');
        if (!a) return;
        e.preventDefault();
        Swal.fire({
            title: a.dataset.confirmTitle || 'Yakin?',
            text: a.dataset.confirm,
            icon: a.dataset.icon || 'warning',
            showCancelButton: true,
            confirmButtonText: a.dataset.confirmText || 'Ya, lanjutkan',
            cancelButtonText: 'Batal',
            confirmButtonColor: a.dataset.confirmColor || '#176b87',
            cancelButtonColor: '#8a97a5',
            reverseButtons: true,
        }).then(r => { if (r.isConfirmed) window.location.href = a.href; });
    });
</script>
</body>
</html>
