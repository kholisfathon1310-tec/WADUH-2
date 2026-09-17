<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="WADUH, Wadah Akses Digital Unit Hunian BITC. Reservasi Working Space, Co-Working Space, dan Convention Hall Gedung BITC Cimahi secara online.">
    <title>WADUH | Wadah Akses Digital Unit Hunian BITC</title>
    <link href="{{ asset('vendor/fonts/fonts.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>
        /* ══════════════════════════════════════════════════════════════
           PALET — MONOKROMATIK TEAL
           Prinsip: satu warna aksen (primary teal) untuk seluruh CTA &
           highlight. Sisanya netral (slate). Identitas per lantai
           dinyatakan lewat NUMBERING + ICON, bukan warna berbeda-beda.
           ══════════════════════════════════════════════════════════════ */
        :root {
            --ink:          #0f172a;
            --muted:        #64748b;
            --soft:         #94a3b8;
            --line:         #e5e9ef;
            --line-soft:    #eef2f6;
            --surface:      #f7f9fc;
            --card:         #ffffff;

            --primary:      #0e6b7d;
            --primary-dark: #084b58;
            --primary-soft: #e6f2f4;    /* untuk pill/latar lembut */
            --accent:       #24aa9a;    /* dipakai sangat terbatas */

            --success:      #0d8a5f;
            --success-soft: #e2f7ef;
            --danger:       #c0392b;
            --danger-soft:  #fdecec;

            --radius:       1rem;
            --radius-lg:    1.25rem;
            --radius-sm:    .65rem;

            --shadow-sm:    0 6px 16px -6px rgba(15,23,42,.08);
            --shadow-md:    0 14px 34px -14px rgba(15,23,42,.14);
            --shadow-lg:    0 26px 50px -20px rgba(15,23,42,.20);
        }

        html { scroll-behavior:smooth; scroll-padding-top:92px; }
        body { font-family:'DM Sans',sans-serif; color:var(--ink); background:var(--card); line-height:1.65; -webkit-font-smoothing:antialiased; }
        h1,h2,h3,h4,h5 { font-family:'Plus Jakarta Sans',sans-serif; letter-spacing:-.028em; color:var(--ink); }
        ::selection { background:var(--primary-soft); color:var(--primary-dark); }
        a { color:var(--primary); }
        a:focus-visible, button:focus-visible, .nav-link:focus-visible { outline:2px solid var(--primary); outline-offset:3px; border-radius:.4rem; }
        img { max-width:100%; display:block; }

        /* ══════════════════════════════════════════════════════════════
           NAVBAR — tanpa logo, cuma pill menu, mengambang di tengah,
           transparan di atas hero, glassy pas discroll.
           ══════════════════════════════════════════════════════════════ */
        .navbar { padding:1rem 0; transition:padding .3s ease; }
        .navbar .container { display:flex; align-items:center; justify-content:center; }
        .nav-pill {
            display:flex; align-items:center;
            background:transparent;
            border:1px solid transparent;
            border-radius:1.5rem;
            padding:.4rem .5rem;
            transition:background .3s ease, border-color .3s ease, box-shadow .3s ease, backdrop-filter .3s ease;
        }
        .navbar.scrolled { padding:.55rem 0; }
        .navbar.scrolled .nav-pill {
            background:rgba(255,255,255,.88);
            border-color:var(--line);
            box-shadow:var(--shadow-md);
            backdrop-filter:saturate(140%) blur(18px);
            -webkit-backdrop-filter:saturate(140%) blur(18px);
        }

        .nav-link {
            color:var(--ink); font-weight:600; font-size:.92rem;
            border-radius:.7rem; padding:.55rem 1rem !important;
            text-shadow:0 1px 0 rgba(255,255,255,.6);
            transition:background .15s ease, color .15s ease, text-shadow .15s ease;
        }
        .nav-link:hover, .nav-link.active {
            color:var(--primary);
            background:rgba(255,255,255,.75);
            text-shadow:none;
            backdrop-filter:blur(6px);
        }
        .navbar.scrolled .nav-link { text-shadow:none; }
        .navbar.scrolled .nav-link:hover, .navbar.scrolled .nav-link.active { background:var(--primary-soft); backdrop-filter:none; }
        .btn-nav {
            color:#fff !important; background:var(--primary);
            border-radius:.75rem; padding:.6rem 1.2rem;
            font-weight:700; font-size:.9rem;
            box-shadow:0 10px 22px -8px rgba(14,107,125,.55);
            transition:transform .15s ease, background .15s ease;
        }
        .btn-nav:hover { background:var(--primary-dark); transform:translateY(-1px); }
        .navbar-toggler { color:var(--ink); }
        .dropdown-menu { border:1px solid var(--line); border-radius:var(--radius); box-shadow:var(--shadow-md); padding:.4rem; margin-top:.5rem !important; }
        .dropdown-item { border-radius:.55rem; font-weight:600; font-size:.9rem; padding:.55rem .75rem; }
        .dropdown-item:hover { background:var(--primary-soft); color:var(--primary-dark); }
        .dropdown-item .dot { display:inline-block; width:.6rem; height:.6rem; border-radius:50%; margin-right:.6rem; background:var(--primary); }
        @media (max-width: 991.98px) {
            .navbar-collapse { background:rgba(255,255,255,.96); border:1px solid var(--line); border-radius:var(--radius); padding:.85rem; margin-top:.75rem; backdrop-filter:blur(16px); box-shadow:var(--shadow-md); }
            .nav-link, .navbar.scrolled .nav-link { text-shadow:none; }
        }

        /* ══════════════════════════════════════════════════════════════
           HERO — foto gedung sebagai background, overlay putih halus,
           konten kiri (headline + CTA + stat), floor stack di kanan.
           ══════════════════════════════════════════════════════════════ */
        .hero {
            position:relative; overflow:hidden; padding:10rem 0 6rem;
            background:
                linear-gradient(100deg, rgba(247,249,252,.97) 0%, rgba(247,249,252,.85) 45%, rgba(247,249,252,.5) 75%, rgba(247,249,252,.28) 100%),
                url('{{ asset("images/gedung_bitc.png") }}') center/cover no-repeat;
        }
        .hero::before {
            content:''; position:absolute; inset:0; pointer-events:none;
            background:radial-gradient(60rem 30rem at 110% -10%, rgba(14,107,125,.08) 0%, transparent 60%);
        }
        .hero > .container { position:relative; z-index:1; }

        .hero .badge-hero {
            display:inline-flex; align-items:center; gap:.5rem;
            background:#fff; border:1px solid var(--line);
            color:var(--primary); font-weight:600; font-size:.82rem;
            border-radius:2rem; padding:.5rem 1rem;
            box-shadow:var(--shadow-sm);
        }
        .hero h1 { font-size:clamp(2.6rem, 5.4vw, 4.4rem); font-weight:800; line-height:1.05; letter-spacing:-.035em; }
        .hero h1 .grad { color:var(--primary); }                     /* dulu gradient warna-warni → sekarang teal solid */
        .hero p.lead-copy { max-width:600px; color:var(--muted); font-size:1.05rem; line-height:1.75; margin-top:1.2rem; }

        .btn-hero { display:inline-flex; align-items:center; gap:.55rem; padding:.9rem 1.4rem; border-radius:.75rem; font-weight:700; font-size:.95rem; text-decoration:none; transition:transform .15s ease, background .15s ease, box-shadow .15s ease; }
        .btn-hero.solid { color:#fff; background:var(--primary); box-shadow:0 12px 26px -6px rgba(14,107,125,.35); }
        .btn-hero.solid:hover { background:var(--primary-dark); color:#fff; transform:translateY(-2px); }
        .btn-hero.ghost { color:var(--ink); background:#fff; border:1px solid var(--line); }
        .btn-hero.ghost:hover { border-color:var(--primary); color:var(--primary); }

        .hero-stats { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.75rem; margin-top:2.5rem; max-width:520px; }
        .hstat { display:flex; align-items:center; gap:.85rem; background:#fff; border:1px solid var(--line); border-radius:var(--radius); padding:.9rem 1.1rem; box-shadow:var(--shadow-sm); }
        .hstat .ic { display:grid; place-items:center; width:2.4rem; height:2.4rem; border-radius:.7rem; background:var(--primary-soft); color:var(--primary); font-size:1.1rem; flex:none; }
        .hstat b { font-family:'Plus Jakarta Sans',sans-serif; font-size:1.3rem; color:var(--ink); display:block; line-height:1.1; }
        .hstat span { color:var(--muted); font-size:.78rem; font-weight:500; }

        /* Floor stack (sisi kanan hero) — card individual per lantai, urutan 1→5.
           Warna monokrom: nomor pakai bg --ink (navy pekat), bukan warna berbeda-beda per lantai. */
        .floor-stack { display:flex; flex-direction:column; gap:.7rem; max-width:380px; margin:auto; }
        .floor-chip {
            display:flex; align-items:center; gap:.9rem;
            background:#fff; border:1px solid var(--line); border-radius:var(--radius);
            padding:.85rem 1rem; text-decoration:none; color:inherit;
            box-shadow:var(--shadow-sm);
            transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }
        .floor-chip:hover { transform:translateX(6px); box-shadow:var(--shadow-md); border-color:transparent; color:inherit; }
        .floor-chip .fnum {
            display:grid; place-items:center; min-width:2.7rem; height:2.7rem; padding:0 .55rem;
            border-radius:.75rem; background:var(--primary); color:#fff;
            font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:.98rem;
            flex:none;
            box-shadow:inset 0 -2px 0 rgba(0,0,0,.12);
        }
        .floor-chip .info { flex:1; min-width:0; }
        .floor-chip .info b { display:block; font-family:'Plus Jakarta Sans',sans-serif; font-size:.95rem; color:var(--ink); line-height:1.25; }
        .floor-chip .info small { color:var(--muted); font-size:.78rem; }
        .floor-chip .info small .ok { color:var(--success); font-weight:700; }
        .floor-chip .info small .no { color:var(--danger); font-weight:700; }
        .floor-chip .chev { color:var(--soft); font-size:.9rem; margin-left:.35rem; }

        /* ══════════════════════════════════════════════════════════════
           STRUKTUR SECTION UMUM
           ══════════════════════════════════════════════════════════════ */
        .section { padding:6rem 0; }
        .section-head { max-width:720px; }
        .section-head.center { margin:0 auto; text-align:center; }
        .eyebrow { display:inline-flex; align-items:center; gap:.55rem; color:var(--primary); font-size:.75rem; font-weight:700; letter-spacing:.16em; text-transform:uppercase; margin-bottom:.9rem; }
        .eyebrow::before { content:''; width:2rem; height:2px; background:currentColor; }
        .section-head.center .eyebrow { justify-content:center; }
        .section h2 { font-size:clamp(1.85rem, 3vw, 2.5rem); font-weight:800; line-height:1.2; letter-spacing:-.03em; }
        .section h2 + p.lead { color:var(--muted); font-size:1.05rem; margin-top:.9rem; }

        /* Card generic */
        .xcard { background:#fff; border:1px solid var(--line); border-radius:var(--radius); transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
        .xcard:hover { transform:translateY(-4px); box-shadow:var(--shadow-md); border-color:transparent; }

        /* ══════════════════════════════════════════════════════════════
           FEATURE CARDS di section Tentang BITC — 4 kartu highlight.
           Icon monokrom teal (soft bg + primary color), bukan warna warni.
           ══════════════════════════════════════════════════════════════ */
        .feature-card {
            background:#fff; border:1px solid var(--line); border-radius:var(--radius);
            padding:1.5rem 1.4rem; height:100%;
            transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }
        .feature-card:hover { transform:translateY(-4px); box-shadow:var(--shadow-md); border-color:transparent; }
        .feature-card .ic {
            display:grid; place-items:center;
            width:2.8rem; height:2.8rem;
            border-radius:.8rem;
            background:var(--primary-soft); color:var(--primary);
            font-size:1.25rem;
            margin-bottom:1rem;
        }
        .feature-card h3 { font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:1rem; color:var(--ink); margin-bottom:.4rem; }
        .feature-card p { color:var(--muted); font-size:.87rem; line-height:1.55; margin:0; }

        /* ══════════════════════════════════════════════════════════════
           TENTANG BITC + JAM OPERASIONAL
           ══════════════════════════════════════════════════════════════ */
        .info-card { background:#fff; border:1px solid var(--line); border-radius:var(--radius); overflow:hidden; box-shadow:var(--shadow-sm); }
        .info-card-head { padding:.85rem 1.15rem; font-weight:700; color:var(--ink); background:var(--surface); border-bottom:1px solid var(--line); display:flex; align-items:center; gap:.5rem; }
        .info-card-head i { color:var(--primary); }
        .info-row { display:flex; justify-content:space-between; align-items:center; padding:.8rem 1.15rem; border-bottom:1px solid var(--line-soft); font-size:.92rem; }
        .info-row:last-child { border-bottom:0; }
        .info-row span { color:var(--muted); }
        .info-row b { font-family:'Plus Jakarta Sans',sans-serif; color:var(--ink); }

        /* ══════════════════════════════════════════════════════════════
           FASILITAS PER LANTAI — foto sebagai head, badge & tombol
           semua MONOKROM (single primary color). Identitas lantai
           lewat NOMOR di badge + ICON kategori.
           ══════════════════════════════════════════════════════════════ */
        .lantai-card {
            position:relative; overflow:hidden; cursor:default;
            border:1px solid var(--line); border-radius:var(--radius-lg);
            background:#fff; height:100%;
            display:flex; flex-direction:column;
        }

        .lantai-card .head { position:relative; height:200px; overflow:hidden; background-color:var(--surface); }
        .lantai-card .head-bg { position:absolute; inset:0; background-size:cover; background-position:center; }
        /* Gradient bawah supaya nomor tetap punya kedalaman. */
        .lantai-card .head::after {
            content:''; position:absolute; inset:0; pointer-events:none;
            background:linear-gradient(180deg, rgba(15,23,42,0) 55%, rgba(15,23,42,.32) 100%);
        }

        .lantai-badge {
            position:absolute; top:1rem; left:1rem; z-index:2;
            display:inline-flex; align-items:center; gap:.6rem;
            background:rgba(255,255,255,.95); backdrop-filter:blur(10px);
            border-radius:.85rem; padding:.5rem .8rem .5rem .5rem;
            box-shadow:var(--shadow-md);
        }
        .lantai-badge .num {
            display:grid; place-items:center; min-width:2.1rem; height:2.1rem; padding:0 .5rem;
            border-radius:.55rem; background:var(--primary); color:#fff;
            font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:.9rem;
            box-shadow:inset 0 -2px 0 rgba(0,0,0,.15);
        }
        .lantai-badge .lbl { display:flex; flex-direction:column; line-height:1.15; }
        .lantai-badge .lbl small { font-size:.62rem; font-weight:700; letter-spacing:.14em; color:var(--soft); text-transform:uppercase; }
        .lantai-badge .lbl b { font-family:'Plus Jakarta Sans',sans-serif; font-size:.82rem; color:var(--ink); }

        .lantai-icon {
            position:absolute; top:1rem; right:1rem; z-index:2;
            display:grid; place-items:center; width:2.5rem; height:2.5rem;
            border-radius:.75rem; color:#fff; font-size:1.1rem;
            background:rgba(15,23,42,.35); border:1px solid rgba(255,255,255,.25);
            backdrop-filter:blur(10px);
        }

        .lantai-card .body { padding:1.3rem 1.4rem 1.4rem; display:flex; flex-direction:column; flex-grow:1; }
        .lantai-card .body h3 { font-size:1.15rem; font-weight:800; margin-bottom:.35rem; }
        .lantai-card .body .desc { color:var(--muted); font-size:.9rem; line-height:1.55; margin-bottom:1.1rem; }
        .lantai-card .pills-row { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1.15rem; }

        .lantai-card .amenity-row { display:flex; flex-wrap:wrap; gap:.4rem; margin-bottom:1.1rem; }
        .lantai-card .amenity-chip { display:inline-flex; align-items:center; gap:.35rem;
            background:var(--surface); border:1px solid var(--line); color:var(--muted);
            font-size:.72rem; font-weight:600; padding:.3rem .6rem; border-radius:.5rem; }
        .lantai-card .amenity-chip i { color:var(--primary); font-size:.8rem; }

        /* Pill — 3 varian: unit (netral), tersedia (hijau), habis (merah). */
        .pill { display:inline-flex; align-items:center; gap:.4rem; padding:.42rem .8rem; border-radius:2rem; font-size:.78rem; font-weight:600; line-height:1; }
        .pill-unit     { background:var(--surface); color:var(--ink);     border:1px solid var(--line); }
        .pill-tersedia { background:var(--success-soft); color:var(--success); }
        .pill-habis    { background:var(--danger-soft);  color:var(--danger); }

        /* ══════════════════════════════════════════════════════════════
           CARA KERJA — card center-align dengan nomor circle di atas.
           Warna nomor seragam monokrom (primary teal), bukan warna warni.
           ══════════════════════════════════════════════════════════════ */
        .step-card {
            background:#fff; border:1px solid var(--line); border-radius:var(--radius);
            text-align:center; padding:2rem 1.4rem;
            height:100%;
            transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }
        .step-card:hover { transform:translateY(-5px); box-shadow:var(--shadow-md); border-color:transparent; }
        .step-card .n {
            display:grid; place-items:center; width:3.4rem; height:3.4rem;
            margin:0 auto 1.1rem; border-radius:50%;
            background:var(--primary); color:#fff;
            font-family:'Plus Jakarta Sans',sans-serif; font-weight:800; font-size:1.15rem;
            box-shadow:0 10px 22px -8px rgba(14,107,125,.45);
        }
        .step-card h3 { font-size:1.02rem; font-weight:800; margin-bottom:.4rem; }
        .step-card p { font-size:.86rem; color:var(--muted); line-height:1.6; margin:0; }

        /* ══════════════════════════════════════════════════════════════
           KONTAK — panel gradient teal (versi asli, warna seragam palet).
           ══════════════════════════════════════════════════════════════ */
        .jam-panel {
            background:linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 60%, var(--accent) 130%);
            color:#fff; border-radius:var(--radius-lg); padding:2.6rem;
            position:relative; overflow:hidden;
            box-shadow:0 24px 50px -20px rgba(8,75,88,.45);
        }
        .jam-panel::after {
            content:''; position:absolute; right:-5rem; top:-9rem;
            width:18rem; height:18rem; border-radius:50%;
            border:2.6rem solid rgba(255,255,255,.07);
        }
        .jam-panel::before {
            content:''; position:absolute; inset:0;
            background:radial-gradient(30rem 16rem at -10% 110%, rgba(36,170,154,.28), transparent 60%);
        }
        .jam-panel > * { position:relative; z-index:1; }
        .jam-panel h3 { color:#fff; margin-bottom:1rem; }
        .jam-row {
            display:flex; justify-content:space-between; gap:1rem;
            padding:.8rem 0; border-bottom:1px dashed rgba(255,255,255,.22);
            font-size:.95rem;
        }
        .jam-row:last-child { border-bottom:0; }
        .jam-row span { opacity:.9; }
        .jam-row b { font-family:'Plus Jakarta Sans',sans-serif; color:#fff; }
        .jam-row a { color:#fff; text-decoration:none; border-bottom:1px dashed rgba(255,255,255,.4); }
        .jam-row a:hover { border-bottom-color:#fff; }

        .btn-wa { display:inline-flex; align-items:center; gap:.6rem; padding:.9rem 1.4rem; border-radius:.75rem; font-weight:700; text-decoration:none; color:#fff; background:#22c55e; box-shadow:0 12px 26px -8px rgba(34,197,94,.4); transition:transform .15s ease, background .15s ease; }
        .btn-wa:hover { background:#1da54d; color:#fff; transform:translateY(-2px); }

        /* ══════════════════════════════════════════════════════════════
           CTA BAND — solid teal clean.
           ══════════════════════════════════════════════════════════════ */
        .cta-band {
            background:linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius:var(--radius-lg); color:#fff; padding:3rem;
            box-shadow:var(--shadow-lg);
            position:relative; overflow:hidden;
        }
        .cta-band::after {
            content:''; position:absolute; right:-5rem; top:-5rem;
            width:16rem; height:16rem; border-radius:50%;
            background:rgba(255,255,255,.06);
        }
        .cta-band > * { position:relative; z-index:1; }

        /* ══════════════════════════════════════════════════════════════
           FOOTER
           ══════════════════════════════════════════════════════════════ */
        .site-footer { padding:2.4rem 0; color:var(--muted); border-top:1px solid var(--line); font-size:.85rem; }
        .site-footer a { color:var(--muted); text-decoration:none; }
        .site-footer a:hover { color:var(--primary); }

        /* ══════════════════════════════════════════════════════════════
           REVEAL ANIMATION
           ══════════════════════════════════════════════════════════════ */
        [data-reveal] { opacity:0; transform:translateY(20px); transition:opacity .55s ease, transform .55s ease; }
        [data-reveal].in { opacity:1; transform:none; }
        @media (prefers-reduced-motion: reduce) { [data-reveal] { opacity:1; transform:none; } }
    </style>
</head>
<body>

@php
    // Kategori metadata — ikon & deskripsi singkat.
    $ikonKategori = [
        'Working Space'    => 'bi-briefcase',
        'Co-Working Space' => 'bi-people',
        'Convention Hall'  => 'bi-bank',
    ];
    $deskKategori = [
        'Working Space'    => 'Ruang kerja privat untuk instansi & perusahaan.',
        'Co-Working Space' => 'Kubikal fleksibel untuk startup & pelaku kreatif.',
        'Convention Hall'  => 'Aula besar untuk event, seminar, dan konvensi.',
    ];
    // Mapping foto per nomor lantai (public/images/*).
    $fotoLantai = [
        '1'  => 'images/lt1(home).png',
        '2'  => 'images/lt2(home).png',
        '3A' => 'images/lt3a(home).png',
        '3B' => 'images/lt3b(home).png',
        '5'  => 'images/lt5.png',
    ];
@endphp

{{-- ══════════════════════════════════════════════════════════════
     NAVBAR
     ══════════════════════════════════════════════════════════════ --}}
<nav id="mainNav" class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <div class="nav-pill">
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu"><i class="bi bi-list fs-2"></i></button>
        <div id="navbarMenu" class="collapse navbar-collapse">
            <ul class="navbar-nav align-items-lg-center gap-lg-1">
                <li class="nav-item"><a class="nav-link active" href="#beranda">Beranda</a></li>
                <li class="nav-item"><a class="nav-link" href="#tentang">Tentang BITC</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="{{ route('reservasi.index') }}" role="button" data-bs-toggle="dropdown">Fasilitas</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('reservasi.index') }}"><i class="bi bi-grid-3x3-gap me-2"></i>Semua Lantai</a></li>
                        <li><hr class="dropdown-divider"></li>
                        @foreach ($daftarLantai as $l)
                            <li>
                                <a class="dropdown-item" href="{{ route('reservasi.denah', ['kategori' => $l['kategori'], 'lantai' => $l['id']]) }}">
                                    <span class="dot"></span>
                                    Lantai {{ $l['nomor'] }} · {{ $l['kategori'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link" href="#kontak">Kontak</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('cek-status.form') }}">Cek Status</a></li>
                @auth('customer')
                    <li class="nav-item ms-lg-2"><a class="btn btn-nav" href="{{ route('customer.dashboard') }}"><i class="bi bi-box-arrow-in-right me-1"></i> Masuk</a></li>
                @else
                    <li class="nav-item ms-lg-2"><a class="btn btn-nav" href="{{ route('customer.login') }}"><i class="bi bi-box-arrow-in-right me-1"></i> Masuk</a></li>
                @endauth
            </ul>
        </div>
        </div>
    </div>
</nav>

<main>

    {{-- ══════════════════════════════════════════════════════════════
         HERO
         ══════════════════════════════════════════════════════════════ --}}
    <section id="beranda" class="hero">
        <div class="container">
            <div class="row align-items-center gy-5">
                <div class="col-lg-7" data-reveal>
                    <span class="badge-hero mb-4"><i class="bi bi-stars"></i>Wadah Akses Digital Unit Hunian BITC</span>
                    <h1>Reservasi ruang di <span class="grad">Gedung BITC</span>, semudah beberapa klik.</h1>
                    <p class="lead-copy">WADUH adalah layanan resmi reservasi fasilitas <strong>Baros Information Technology Creative Center (BITC)</strong>, mulai dari kubikal co-working, ruang kerja privat, ruang rapat, hingga convention hall. Pemesanan dilakukan secara online tanpa perlu antre di lokasi.</p>
                    <div class="d-flex flex-wrap gap-3 mt-4">
                        <a href="{{ route('reservasi.index') }}" class="btn-hero solid">Reservasi Sekarang <i class="bi bi-arrow-up-right"></i></a>
                        <a href="{{ route('cek-status.form') }}" class="btn-hero ghost"><i class="bi bi-search"></i> Cek Status Reservasi</a>
                    </div>
                    <div class="hero-stats" data-reveal>
                        <div class="hstat"><span class="ic"><i class="bi bi-grid-3x3-gap-fill"></i></span><span><b>{{ $daftarLantai->sum('total') }}</b><span>Total Unit Ruang</span></span></div>
                        <div class="hstat"><span class="ic"><i class="bi bi-check-circle-fill"></i></span><span><b>{{ $daftarLantai->sum('tersedia') }}</b><span>Siap Direservasi</span></span></div>
                        <div class="hstat"><span class="ic"><i class="bi bi-building"></i></span><span><b>{{ $daftarLantai->count() }}</b><span>Lantai Aktif</span></span></div>
                        <div class="hstat"><span class="ic"><i class="bi bi-clock-history"></i></span><span><b>3</b><span>Jenis Sewa</span></span></div>
                    </div>
                </div>

                {{-- Floor stack sisi kanan — card individual per lantai, urutan 1→5. --}}
                <div class="col-lg-5" data-reveal>
                    <div class="floor-stack">
                        @foreach ($daftarLantai as $l)
                            @php $habis = (int) $l['tersedia'] === 0; @endphp
                            <a class="floor-chip" href="{{ route('reservasi.denah', ['kategori' => $l['kategori'], 'lantai' => $l['id']]) }}">
                                <span class="fnum">{{ $l['nomor'] }}</span>
                                <span class="info">
                                    <b>{{ $l['kategori'] }}</b>
                                    <small>
                                        <span class="{{ $habis ? 'no' : 'ok' }}">{{ $l['tersedia'] }} dari {{ $l['total'] }} unit</span>
                                        {{ $habis ? 'habis dipesan' : 'tersedia' }}
                                    </small>
                                </span>
                                <i class="bi bi-chevron-right chev"></i>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         TENTANG BITC + JAM OPERASIONAL
         ══════════════════════════════════════════════════════════════ --}}
    <section id="tentang" class="section" style="background:var(--surface)">
        <div class="container">
            <div class="row gy-5 align-items-center">
                <div class="col-lg-6" data-reveal>
                    <div class="section-head">
                        <p class="eyebrow">Tentang BITC</p>
                        <h2>Baros Information Technology Creative Centre.</h2>
                        <p class="lead">Gedung <strong>BITC</strong> di  Jl. HMS Mintareja Sarjana Hukum, Baros, Kec. Cimahi Tengah, Kota Cimahi, Jawa Barat dikelola oleh <strong>UPTD Cimahi Techno Park</strong> sebagai pusat pengembangan industri teknologi informasi dan ekonomi kreatif Kota Cimahi. Di dalamnya tersedia unit hunian usaha yang dapat disewa: ruang kerja privat, ruang rapat, kubikal co-working, hingga convention hall untuk acara berskala besar.</p>
                        <p class="text-muted mt-3" style="line-height:1.85"><strong>WADUH (Wadah Akses Digital Unit Hunian)</strong> hadir agar seluruh proses mulai dari melihat ketersediaan per lantai, mengajukan reservasi, sampai cek status reservasi, bisa dilakukan dari mana saja.</p>
                    </div>
                </div>
                <div class="col-lg-6" data-reveal>
                    <div class="info-card">
                        <div class="info-card-head"><i class="bi bi-clock"></i> Jam Operasional Gedung BITC</div>
                        <div class="info-row"><span>Senin – Jumat</span><b>08:00 – 16:00 WIB</b></div>
                        <div class="info-row"><span>Sewa Per Jam</span><b>08:00 – 16:00 WIB</b></div>
                        <div class="info-row"><span>Sabtu – Minggu</span><b class="text-muted">Tutup (kecuali event)</b></div>
                        <div class="info-row"><span>Hari Libur Nasional</span><b class="text-muted">Tutup</b></div>
                    </div>
                </div>
            </div>

            {{-- 4 highlight card — mengapa BITC. Icon semua monokrom teal. --}}
            <div class="row g-3 mt-5">
                <div class="col-md-6 col-lg-3" data-reveal>
                    <div class="feature-card">
                        <span class="ic"><i class="bi bi-geo-alt-fill"></i></span>
                        <h3>Lokasi Strategis</h3>
                        <p> Jl. HMS Mintareja Sarjana Hukum, Baros, Kec. Cimahi Tengah, Kota Cimahi, Jawa Barat. Akses mudah dari tol Baros.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-reveal>
                    <div class="feature-card">
                        <span class="ic"><i class="bi bi-wifi"></i></span>
                        <h3>Fasilitas Penunjang</h3>
                        <p>Internet, area parkir, lift, mushola, dan ruang publik kreatif.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-reveal>
                    <div class="feature-card">
                        <span class="ic"><i class="bi bi-shield-fill-check"></i></span>
                        <h3>Dikelola Resmi</h3>
                        <p>UPTD Cimahi Techno Park, Pemerintah Kota Cimahi.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-reveal>
                    <div class="feature-card">
                        <span class="ic"><i class="bi bi-lightning-charge-fill"></i></span>
                        <h3>Proses Digital</h3>
                        <p>Lihat ruangan sampai checkout, semuanya lewat WADUH.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         FASILITAS PER LANTAI
         ══════════════════════════════════════════════════════════════ --}}
    <section id="fasilitas" class="section">
        <div class="container">
            <div class="section-head center mb-5" data-reveal>
                <p class="eyebrow">Fasilitas per Lantai</p>
                <h2>Lima lantai, tiga jenis ruang.</h2>
                <p class="lead">Pilih lantai untuk langsung melihat denah &amp; detail tiap ruangannya.</p>
            </div>
            <div class="row g-4">
                @foreach ($daftarLantai as $l)
                    @php
                        $foto  = asset($fotoLantai[$l['nomor']] ?? 'images/lt1.png');
                        $habis = (int) $l['tersedia'] === 0;
                        $meta  = \App\Support\KategoriMeta::get($l['kategori']);
                        $amenities = collect($meta['dapat'] ?? [])->take(3);
                        $sisaAmenity = max(0, count($meta['dapat'] ?? []) - $amenities->count());
                        $kapMin = $l['kap_min'] ?? null;
                        $kapMaks = $l['kap_maks'] ?? null;
                    @endphp
                    <div class="col-md-6 col-xl-4" data-reveal>
                        <div class="lantai-card">
                            {{-- HEAD: foto lantai + badge nomor (mono) + icon kategori --}}
                            <div class="head">
                                <div class="head-bg" style="background-image:url('{{ $foto }}')"></div>
                                <span class="lantai-badge">
                                    <span class="num">{{ $l['nomor'] }}</span>
                                    <span class="lbl">
                                        <small>Lantai</small>
                                        <b>{{ $l['kategori'] }}</b>
                                    </span>
                                </span>
                                <span class="lantai-icon"><i class="bi {{ $ikonKategori[$l['kategori']] ?? 'bi-door-open' }}"></i></span>
                            </div>

                            {{-- BODY --}}
                            <div class="body">
                                <h3>{{ $l['kategori'] }}</h3>
                                <p class="desc">{{ $meta['desk'] ?? ($deskKategori[$l['kategori']] ?? '') }}</p>
                                <div class="pills-row">
                                    <span class="pill pill-unit"><i class="bi bi-door-open"></i> {{ $l['total'] }} unit</span>
                                    <span class="pill {{ $habis ? 'pill-habis' : 'pill-tersedia' }}">
                                        <i class="bi {{ $habis ? 'bi-slash-circle' : 'bi-check-circle' }}"></i>
                                        {{ $l['tersedia'] }} tersedia
                                    </span>
                                    @if ($kapMaks)
                                        <span class="pill pill-unit">
                                            <i class="bi bi-people"></i>
                                            Kapasitas {{ $kapMin && $kapMin !== $kapMaks ? "{$kapMin}–{$kapMaks}" : $kapMaks }} orang
                                        </span>
                                    @endif
                                </div>
                                <div class="amenity-row">
                                    @foreach ($amenities as $a)
                                        <span class="amenity-chip"><i class="bi bi-check-circle-fill"></i>{{ $a }}</span>
                                    @endforeach
                                    @if ($sisaAmenity > 0)
                                        <span class="amenity-chip">+{{ $sisaAmenity }} lainnya</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         CARA KERJA — 4 LANGKAH
         ══════════════════════════════════════════════════════════════ --}}
    <section class="section" style="background:var(--surface)">
        <div class="container">
            <div class="section-head center mb-5" data-reveal>
                <p class="eyebrow">Cara Kerja</p>
                <h2>Reservasi dalam 4 langkah singkat.</h2>
                <p class="text-muted mb-0">Masuk sebagai Pemesan untuk memulai — seluruh proses reservasi hanya butuh 4 langkah.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3" data-reveal>
                    <div class="step-card">
                        <span class="n">1</span>
                        <h3>Pilih Ruang</h3>
                        <p>Masuk atau daftar, lalu telusuri lantai & pilih ruang di denah interaktif.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-reveal>
                    <div class="step-card">
                        <span class="n">2</span>
                        <h3>Atur Jadwal</h3>
                        <p>Sewa per jam, harian, atau bulanan, bisa beberapa ruang sekaligus.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-reveal>
                    <div class="step-card">
                        <span class="n">3</span>
                        <h3>Checkout</h3>
                        <p>Isi data diri sekali, unggah dokumen, dapat kode reservasi.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-reveal>
                    <div class="step-card">
                        <span class="n">4</span>
                        <h3>Pantau &amp; Gunakan</h3>
                        <p>Cek status persetujuan dengan kode reservasi, lalu gunakan ruangmu.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         KONTAK
         ══════════════════════════════════════════════════════════════ --}}
    <section id="kontak" class="section">
        @php
            // Nomor & alamat kontak diambil dari biodata admin (halaman Profil admin), dengan
            // fallback ke config('institusi') kalau admin belum mengisi biodatanya.
            $waNomor = $adminKontak?->no_whatsapp ?: ('+'.config('institusi.whatsapp'));
            $waDigit = preg_replace('/\D/', '', $waNomor);
            $kontakAlamat = $adminKontak?->alamat ?: 'Jl. Raya Baros No. 78, Cimahi Selatan';
        @endphp
        <div class="container">
            <div class="row gy-5 align-items-center">
                <div class="col-lg-6" data-reveal>
                    <div class="section-head">
                        <p class="eyebrow">Kontak</p>
                        <h2>Butuh bantuan? Hubungi admin BITC.</h2>
                        <p class="lead">Mau reservasi secara langsung atau ingin melakukan pembayaran? Admin BITC siap membantu.</p>
                        <a class="btn-wa mt-3"
                           href="https://wa.me/{{ $waDigit }}?text={{ urlencode('Halo Admin BITC, saya ingin bertanya tentang reservasi fasilitas lewat WADUH.') }}"
                           target="_blank" rel="noopener">
                            <i class="bi bi-whatsapp"></i> Chat WhatsApp 
                        </a>
                    </div>
                </div>
                <div class="col-lg-6" data-reveal>
                    <div class="jam-panel">
                        <h3 class="h5"><i class="bi bi-headset me-2"></i>Kontak Resmi Gedung BITC</h3>
                        <div class="jam-row">
                            <span><i class="bi bi-whatsapp me-1"></i>WhatsApp Admin</span>
                            <b><a href="https://wa.me/{{ $waDigit }}" target="_blank" rel="noopener">{{ $waNomor }}</a></b>
                        </div>
                        <div class="jam-row"><span><i class="bi bi-envelope me-1"></i>Email</span><b>{{ config('institusi.email') }}</b></div>
                        <div class="jam-row"><span><i class="bi bi-geo-alt me-1"></i>Alamat</span><b class="text-end" style="max-width:60%">{{ $kontakAlamat }}</b></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════════════════
         CTA BAND
         ══════════════════════════════════════════════════════════════ --}}
    <section class="section pt-0">
        <div class="container">
            <div class="cta-band d-flex flex-wrap justify-content-between align-items-center gap-3" data-reveal>
                <div>
                    <h2 class="h3 mb-1" style="color:#fff">Siap pakai ruang di BITC?</h2>
                    <p class="mb-0" style="opacity:.85">Mulai reservasi sekarang, prosesnya cepat.</p>
                </div>
                <a href="{{ route('reservasi.index') }}" class="btn-hero" style="background:#fff; color:var(--primary);">Mulai Reservasi <i class="bi bi-arrow-up-right"></i></a>
            </div>
        </div>
    </section>

</main>

{{-- ══════════════════════════════════════════════════════════════
     FOOTER
     ══════════════════════════════════════════════════════════════ --}}
<footer class="site-footer">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><strong style="color:var(--ink)">WADUH</strong> · Wadah Akses Digital Unit Hunian BITC · UPTD Cimahi Techno Park</span>
        <div class="d-flex gap-3">
            <a href="#tentang">Tentang</a>
            <a href="#fasilitas">Fasilitas</a>
            <a href="#kontak">Kontak</a>
            <a href="{{ route('cek-status.form') }}">Cek Status</a>
        </div>
        <span>&copy; {{ now()->year }} WADUH · BITC Cimahi</span>
    </div>
</footer>

<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script>
    const nav = document.getElementById('mainNav');
    const onScroll = () => nav.classList.toggle('scrolled', window.scrollY > 24);
    onScroll(); window.addEventListener('scroll', onScroll, { passive: true });

    const io = new IntersectionObserver(es => es.forEach(e => e.isIntersecting && e.target.classList.add('in')), { threshold: .12 });
    document.querySelectorAll('[data-reveal]').forEach(el => io.observe(el));
</script>
</body>
</html>