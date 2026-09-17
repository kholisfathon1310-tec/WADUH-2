<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk | WADUH</title>
    <link href="{{ asset('vendor/fonts/fonts.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>
        :root { --primary:#176b87; --primary-dark:#0f526b; --teal:#24aa9a; --ink:#15243b; --muted:#637189; --line:#e4ebf2; }
        * { box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; min-height:100vh; margin:0; display:grid; place-items:center;
               background:#f4f7fa; padding:1.5rem; }
        h1,h2,.brand { font-family:'Plus Jakarta Sans',sans-serif; letter-spacing:-.02em; }
        @keyframes cardIn { from { opacity:0; transform:translateY(18px) scale(.98); } to { opacity:1; transform:none; } }

        .login-shell { position:relative; z-index:1; width:min(940px, 100%); background:#fff; border-radius:1.75rem;
            box-shadow:0 34px 76px -20px rgba(15,36,52,.28); overflow:hidden; animation:cardIn .5s cubic-bezier(.2,.7,.3,1) both;
            display:flex; min-height:560px; }

        /* Panel kiri — identitas & sorotan singkat, tersembunyi di layar kecil */
        .login-visual { flex:1 1 46%; position:relative; overflow:hidden; padding:2.75rem 2.5rem; color:#fff;
            background:url('{{ asset('images/gedung_bitc.png') }}') center/cover no-repeat;
            display:flex; flex-direction:column; justify-content:center; gap:2.25rem; }
        /* Gradasi sekarang hanya menggelapkan bagian bawah (tempat teks) supaya foto gedung terlihat jelas di bagian atas */
        .login-visual::before { content:''; position:absolute; inset:0; z-index:0; pointer-events:none;
            background:linear-gradient(180deg, rgba(9,23,36,.12) 0%, rgba(9,23,36,.28) 38%, rgba(9,23,36,.62) 72%, rgba(9,23,36,.82) 100%); }
        .login-visual::after { content:''; position:absolute; z-index:0; pointer-events:none; border-radius:50%; filter:blur(6px); opacity:.22;
            width:16rem; height:16rem; background:#24aa9a; bottom:-6rem; right:-5rem; }
        .lv-grid { position:absolute; inset:0; z-index:0; opacity:.05;
            background-image:linear-gradient(rgba(255,255,255,.6) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.6) 1px, transparent 1px);
            background-size:32px 32px; mask-image:radial-gradient(60% 60% at 30% 30%, #000, transparent); }
        .login-visual > * { position:relative; z-index:1; }
        .lv-headline { font-weight:800; font-size:1.55rem; line-height:1.3; margin:0 0 .75rem; max-width:20rem; text-shadow:0 3px 14px rgba(0,0,0,.4); }
        .lv-sub { color:#d7e7ec; font-size:.9rem; line-height:1.6; max-width:22rem; margin:0; text-shadow:0 2px 10px rgba(0,0,0,.35); }
        .lv-points { list-style:none; margin:2.25rem 0 0; padding:0; display:flex; flex-direction:column; gap:.85rem; }
        .lv-points li { display:flex; align-items:center; gap:.65rem; font-size:.85rem; color:#eef6f7; text-shadow:0 2px 8px rgba(0,0,0,.4); }
        .lv-points .ic { display:grid; place-items:center; width:1.9rem; height:1.9rem; border-radius:.6rem; background:rgba(255,255,255,.16); backdrop-filter:blur(2px); flex:none; font-size:.85rem; }
        .lv-foot { font-size:.72rem; color:#c9dade; text-shadow:0 2px 8px rgba(0,0,0,.4); }

        /* Panel kanan — form */
        .login-form-panel { flex:1 1 54%; padding:3rem 3rem 2.25rem; display:flex; flex-direction:column; justify-content:center; }
        .login-form-head { margin-bottom:1.75rem; }
        .login-form-head h2 { font-size:1.5rem; font-weight:800; margin:0 0 .35rem; color:var(--ink); }
        .login-form-head p { color:var(--muted); font-size:.9rem; margin:0; }

        .form-control { border-radius:.75rem; border-color:var(--line); padding:.65rem .9rem; }
        .form-control:focus { border-color:var(--primary); box-shadow:0 0 0 .2rem rgba(23,107,135,.12); }
        .form-label { font-weight:600; font-size:.85rem; color:#3c4a5f; }
        .btn-login { background:linear-gradient(135deg,var(--primary),var(--primary-dark)); border-color:var(--primary); color:#fff; font-weight:700; border-radius:.8rem; padding:.72rem; transition:background .15s ease, box-shadow .15s ease, transform .15s ease; }
        .btn-login:hover { color:#fff; box-shadow:0 10px 22px -8px rgba(23,107,135,.5); transform:translateY(-1px); }
        .input-group-text { background:#f4f8fa; border-color:var(--line); color:var(--muted); border-radius:.75rem 0 0 .75rem; }
        .login-foot { border-top:1px solid #eef2f5; margin-top:1.1rem; padding-top:1rem; }
        .is-salah { border-color:#d95757 !important; background:#fffafa !important; animation:goyang .3s; }
        @keyframes goyang { 25% { transform:translateX(-4px); } 75% { transform:translateX(4px); } }
        .catatan-salah { display:flex; align-items:center; gap:.3rem; color:#c02929; font-size:.78rem; font-weight:600; margin-top:.3rem; }
        /* SweetAlert2: matikan pointer-events overlay begitu animasi fade-out mulai, supaya klik berikutnya (mis. buka modal lagi) tidak tertelan. */
        .swal2-backdrop-hide { pointer-events: none !important; }

        @media (max-width: 767.98px) {
            .login-visual { display:none; }
            .login-shell { min-height:0; }
            .login-form-panel { padding:2.25rem 1.75rem; }
        }

        /* Pilihan peran — Pemesan / Admin, badge kecil di pojok kanan atas kartu login. */
        .corner-toggle { position:absolute; top:1rem; right:1rem; z-index:5; display:flex; gap:.25rem;
            background:rgba(255,255,255,.9); backdrop-filter:blur(6px); border-radius:9999px; padding:.2rem;
            box-shadow:0 4px 14px -4px rgba(15,36,52,.2); }
        .corner-toggle a, .corner-toggle span { display:inline-flex; align-items:center; gap:.3rem; font-size:.7rem; font-weight:700;
            padding:.3rem .65rem; border-radius:9999px; text-decoration:none; color:var(--muted); transition:all .15s ease; }
        .corner-toggle a:hover { color:var(--ink); }
        .corner-toggle .active { background:var(--primary); color:#fff; }

        @media (max-width: 767.98px) {
            .corner-toggle { position:static; margin:0 0 1rem; justify-content:flex-end; background:transparent; box-shadow:none; padding:0; }
        }
    </style>
</head>
<body>
    <div class="login-shell">
        <div class="corner-toggle" role="tablist" aria-label="Pilih peran login">
            <a href="{{ route('customer.login') }}"><i class="bi bi-person"></i> Pemesan</a>
            <span class="active"><i class="bi bi-shield-lock"></i> Admin</span>
        </div>

        <div class="login-visual">
            <div class="lv-grid"></div>
            <div>
                <h1 class="lv-headline">Kelola reservasi fasilitas BITC dalam satu sistem</h1>
                <p class="lv-sub">Pantau pengajuan, verifikasi dokumen, sampai buat laporan reservasi.</p>
                <ul class="lv-points">
                    <li><span class="ic"><i class="bi bi-journal-check"></i></span>Verifikasi dan persetujuan reservasi</li>
                    <li><span class="ic"><i class="bi bi-grid-3x3-gap"></i></span>Monitoring tiap lantai</li>
                    <li><span class="ic"><i class="bi bi-file-earmark-bar-graph"></i></span>Laporan data reservasi bulanan</li>
                </ul>
            </div>
        </div>

        <div class="login-form-panel">
            <div class="login-form-head">
                <h2>Masuk ke Panel Admin</h2>
                <p>Masukkan email dan kata sandi Anda untuk melanjutkan.</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger py-2 small"><ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <form method="POST" action="{{ route('admin.login.attempt') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="nama@waduh.test" value="{{ old('email') }}" required autofocus>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kata Sandi</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Masukkan kata sandi" required>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check mb-0">
                        <input type="checkbox" name="remember" class="form-check-input" id="remember">
                        <label class="form-check-label small" for="remember">Ingat saya di perangkat ini</label>
                    </div>
                    <a href="{{ route('admin.password.request') }}" class="small text-decoration-none">Lupa kata sandi?</a>
                </div>
                <button class="btn btn-login w-100"><i class="bi bi-box-arrow-in-right me-1"></i> Masuk</button>
            </form>
            <p class="text-center text-muted small login-foot mb-0"><a href="{{ url('/') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Kembali ke Situs Publik</a></p>
        </div>
    </div>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script>
        // Notifikasi hasil login, ditampilkan setelah pengalihan dari proses masuk atau keluar.
        @if (session('success'))
            Swal.fire({ icon: 'success', title: 'Berhasil', text: @json(session('success')), confirmButtonColor: '#176b87', confirmButtonText: 'Oke' });
        @endif
        @if (session('error'))
            Swal.fire({ icon: 'error', title: 'Gagal', text: @json(session('error')), confirmButtonColor: '#176b87', confirmButtonText: 'Oke, Mengerti' });
        @endif

        // Validasi klien di sisi peramban, tanpa pesan bawaan browser.
        document.querySelectorAll('form').forEach(f => {
            f.setAttribute('novalidate', '');
            f.addEventListener('submit', e => {
                const salah = [...f.querySelectorAll('input')].filter(el => ! el.checkValidity());
                if (! salah.length) return;
                e.preventDefault();
                salah.forEach(el => {
                    el.classList.add('is-salah');
                    const grup = el.closest('.input-group') || el;
                    grup.parentElement.querySelector('.catatan-salah')?.remove();
                    const note = document.createElement('div');
                    note.className = 'catatan-salah';
                    note.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i>' +
                        (el.validity.valueMissing
                            ? (el.type === 'password' ? 'Kata sandi belum diisi.' : 'Email belum diisi.')
                            : 'Format email belum benar.');
                    grup.insertAdjacentElement('afterend', note);
                });
                salah[0].focus();
            });
            f.addEventListener('input', e => {
                e.target.classList.remove('is-salah');
                (e.target.closest('.input-group') || e.target).parentElement.querySelector('.catatan-salah')?.remove();
            }, true);
        });
    </script>
</body>
</html>