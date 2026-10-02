{{--
    Halaman galat bersama (404, 403, 419, 429, 500, 503) — berdiri sendiri tanpa layout aplikasi
    supaya tetap tampil walau galat terjadi di layout. Tombol kembali menyesuaikan siapa yang
    sedang masuk (admin / pemesan / pengunjung).
--}}
@php
    $beranda = auth('admin')->check() ? route('admin.dashboard')
        : (auth('customer')->check() ? route('customer.dashboard') : route('home'));
    $labelBeranda = auth('admin')->check() || auth('customer')->check() ? 'Ke Dashboard' : 'Ke Beranda';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul') | WADUH</title>
    <link href="{{ asset('vendor/fonts/fonts.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>
        :root { --primary:#176b87; --primary-dark:#0f526b; --ink:#0f172a; --muted:#64748b; --line:#e2e8f0; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; padding:1.5rem;
            font-family:'DM Sans', system-ui, sans-serif; color:var(--ink);
            background:radial-gradient(60rem 30rem at 50% -10%, #e6f2f4 0%, #f5f8fa 55%, #f1f5f8 100%); }
        .kartu { width:min(560px, 100%); background:#fff; border:1px solid var(--line); border-radius:1.5rem;
            box-shadow:0 30px 60px -28px rgba(15,60,73,.28); padding:2.75rem 2.25rem 2.25rem; text-align:center; }
        .logo { height:2.2rem; width:auto; margin-bottom:1.75rem; }
        .ikon { display:grid; place-items:center; width:4.75rem; height:4.75rem; margin:0 auto 1.25rem; border-radius:1.25rem;
            background:#e6f2f4; color:var(--primary); font-size:2.1rem; border:1px solid #c9e6ea; }
        .kode { font-family:'Plus Jakarta Sans', 'DM Sans', sans-serif; font-size:.78rem; font-weight:800; letter-spacing:.14em;
            color:var(--primary); text-transform:uppercase; margin:0 0 .4rem; }
        h1 { font-family:'Plus Jakarta Sans', 'DM Sans', sans-serif; font-size:1.6rem; font-weight:800; margin:0 0 .6rem; letter-spacing:-.02em; }
        p.pesan { color:var(--muted); font-size:.95rem; line-height:1.65; margin:0 auto 1.75rem; max-width:26rem; }
        .aksi { display:flex; flex-wrap:wrap; gap:.6rem; justify-content:center; }
        .btn { display:inline-flex; align-items:center; gap:.45rem; padding:.75rem 1.3rem; border-radius:.85rem; font-weight:700;
            font-size:.9rem; text-decoration:none; border:1px solid transparent; cursor:pointer; font-family:inherit; }
        .btn-utama { background:linear-gradient(135deg, var(--primary), var(--primary-dark)); color:#fff; box-shadow:0 10px 22px -10px rgba(23,107,135,.6); }
        .btn-garis { background:#fff; color:var(--primary-dark); border-color:#c9e6ea; }
        .btn:hover { filter:brightness(1.05); }
    </style>
</head>
<body>
    <main class="kartu">
        <img src="{{ asset('images/logo_bitc_crop.png') }}" alt="BITC" class="logo">
        <div class="ikon"><i class="bi @yield('ikon', 'bi-exclamation-circle')"></i></div>
        <p class="kode">Galat @yield('kode')</p>
        <h1>@yield('judul')</h1>
        <p class="pesan">@yield('pesan')</p>
        <div class="aksi">
            <a href="{{ $beranda }}" class="btn btn-utama"><i class="bi bi-house-door"></i>{{ $labelBeranda }}</a>
            <button type="button" class="btn btn-garis" onclick="history.length > 1 ? history.back() : location.assign('{{ $beranda }}')"><i class="bi bi-arrow-left"></i>Kembali</button>
        </div>
    </main>
</body>
</html>
