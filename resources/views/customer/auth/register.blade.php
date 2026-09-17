<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar | WADUH</title>
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

        .reg-shell { position:relative; z-index:1; width:min(1040px, 100%); background:#fff; border-radius:1.75rem;
            box-shadow:0 34px 76px -20px rgba(15,36,52,.28); overflow:hidden; animation:cardIn .5s cubic-bezier(.2,.7,.3,1) both;
            display:flex; min-height:640px; }

        .reg-visual { flex:1 1 40%; position:relative; overflow:hidden; padding:2.75rem 2.5rem; color:#fff;
            background:url('{{ asset('images/gedung_bitc.png') }}') center/cover no-repeat;
            display:flex; flex-direction:column; justify-content:center; gap:2.25rem; }
        .reg-visual::before { content:''; position:absolute; inset:0; z-index:0; pointer-events:none;
            background:linear-gradient(180deg, rgba(9,23,36,.12) 0%, rgba(9,23,36,.28) 38%, rgba(9,23,36,.62) 72%, rgba(9,23,36,.82) 100%); }
        .reg-visual::after { content:''; position:absolute; z-index:0; pointer-events:none; border-radius:50%; filter:blur(6px); opacity:.22;
            width:16rem; height:16rem; background:#24aa9a; bottom:-6rem; right:-5rem; }
        .rv-grid { position:absolute; inset:0; z-index:0; opacity:.05;
            background-image:linear-gradient(rgba(255,255,255,.6) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.6) 1px, transparent 1px);
            background-size:32px 32px; mask-image:radial-gradient(60% 60% at 30% 30%, #000, transparent); }
        .reg-visual > * { position:relative; z-index:1; }
        .rv-headline { font-weight:800; font-size:1.55rem; line-height:1.3; margin:0 0 .75rem; max-width:20rem; text-shadow:0 3px 14px rgba(0,0,0,.4); }
        .rv-sub { color:#d7e7ec; font-size:.9rem; line-height:1.6; max-width:22rem; margin:0; text-shadow:0 2px 10px rgba(0,0,0,.35); }
        .rv-points { list-style:none; margin:2.25rem 0 0; padding:0; display:flex; flex-direction:column; gap:.85rem; }
        .rv-points li { display:flex; align-items:center; gap:.65rem; font-size:.85rem; color:#eef6f7; text-shadow:0 2px 8px rgba(0,0,0,.4); }
        .rv-points .ic { display:grid; place-items:center; width:1.9rem; height:1.9rem; border-radius:.6rem; background:rgba(255,255,255,.16); backdrop-filter:blur(2px); flex:none; font-size:.85rem; }

        .reg-form-panel { flex:1 1 60%; padding:2.75rem 2.75rem 2rem; display:flex; flex-direction:column; justify-content:center; overflow-y:auto; }
        .reg-head { margin-bottom:1.5rem; }
        .reg-head h2 { font-size:1.4rem; font-weight:800; margin:0 0 .35rem; color:var(--ink); }
        .reg-head p { color:var(--muted); font-size:.88rem; margin:0; }

        .reg-section-lbl { font-size:.68rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase;
            color:var(--primary-dark); margin:1.1rem 0 .6rem; display:flex; align-items:center; gap:.4rem; }
        .reg-section-lbl:first-of-type { margin-top:0; }
        .reg-section-lbl i { font-size:.95em; }

        .form-control { border-radius:.75rem; border-color:var(--line); padding:.6rem .9rem; }
        .form-control:focus { border-color:var(--primary); box-shadow:0 0 0 .2rem rgba(23,107,135,.12); }
        .form-label { font-weight:600; font-size:.85rem; color:#3c4a5f; }
        .btn-login { background:linear-gradient(135deg,var(--primary),var(--primary-dark)); border-color:var(--primary); color:#fff; font-weight:700; border-radius:.8rem; padding:.72rem; transition:background .15s ease, box-shadow .15s ease, transform .15s ease; }
        .btn-login:hover { color:#fff; box-shadow:0 10px 22px -8px rgba(23,107,135,.5); transform:translateY(-1px); }
        .login-foot { border-top:1px solid #eef2f5; margin-top:1.1rem; padding-top:1rem; }
        .is-salah { border-color:#d95757 !important; background:#fffafa !important; animation:goyang .3s; }
        @keyframes goyang { 25% { transform:translateX(-4px); } 75% { transform:translateX(4px); } }
        .catatan-salah { display:flex; align-items:center; gap:.3rem; color:#c02929; font-size:.78rem; font-weight:600; margin-top:.3rem; }
        /* SweetAlert2: matikan pointer-events overlay begitu animasi fade-out mulai, supaya klik berikutnya (mis. buka modal lagi) tidak tertelan. */
        .swal2-backdrop-hide { pointer-events: none !important; }

        /* Pilihan peran — Pemesan / Admin, badge kecil di pojok kanan atas kartu. */
        .corner-toggle { position:absolute; top:1rem; right:1rem; z-index:5; display:flex; gap:.25rem;
            background:rgba(255,255,255,.9); backdrop-filter:blur(6px); border-radius:9999px; padding:.2rem;
            box-shadow:0 4px 14px -4px rgba(15,36,52,.2); }
        .corner-toggle a, .corner-toggle span { display:inline-flex; align-items:center; gap:.3rem; font-size:.7rem; font-weight:700;
            padding:.3rem .65rem; border-radius:9999px; text-decoration:none; color:var(--muted); transition:all .15s ease; }
        .corner-toggle a:hover { color:var(--ink); }
        .corner-toggle .active { background:var(--primary); color:#fff; }

        @media (max-width: 767.98px) {
            .reg-visual { display:none; }
            .reg-shell { min-height:0; }
            .reg-form-panel { padding:2.25rem 1.75rem; }
            .corner-toggle { position:static; margin:0 0 1rem; justify-content:flex-end; background:transparent; box-shadow:none; padding:0; }
        }
    </style>
</head>
<body>
    <div class="reg-shell">
        <div class="corner-toggle" role="tablist" aria-label="Pilih peran">
            <span class="active"><i class="bi bi-person"></i> Pemesan</span>
            <a href="{{ route('admin.login') }}"><i class="bi bi-shield-lock"></i> Admin</a>
        </div>

        <div class="reg-visual">
            <div class="rv-grid"></div>
            <div>
                <h1 class="rv-headline">Bergabung, kelola reservasi lebih mudah</h1>
                <p class="rv-sub">Daftar sekali, riwayat reservasi Anda tersimpan dan bisa dipantau kapan saja.</p>
                <ul class="rv-points">
                    <li><span class="ic"><i class="bi bi-clock-history"></i></span>Isi data sekali, pakai berulang</li>
                    <li><span class="ic"><i class="bi bi-journal-check"></i></span>Riwayat reservasi tersimpan rapi</li>
                    <li><span class="ic"><i class="bi bi-shield-check"></i></span>Data Anda aman &amp; terenkripsi</li>
                </ul>
            </div>
        </div>

        <div class="reg-form-panel">
            <div class="reg-head">
                <h2>Daftar sebagai Pemesan</h2>
                <p>Buat akun untuk mulai memesan fasilitas BITC.</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger py-2 small"><ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <form method="POST" action="{{ route('customer.register.attempt') }}">
                @csrf

                <p class="reg-section-lbl"><i class="bi bi-person-vcard"></i>Identitas</p>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Nama lengkap</label>
                        <input name="nama_lengkap" class="form-control" value="{{ old('nama_lengkap') }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Usia</label>
                        <input type="number" name="usia" class="form-control" min="17" max="120" value="{{ old('usia') }}" required>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Pekerjaan</label>
                        <input name="pekerjaan" class="form-control" value="{{ old('pekerjaan') }}" required>
                    </div>
                </div>

                <p class="reg-section-lbl"><i class="bi bi-geo-alt"></i>Kontak</p>
                <div class="row">
                    <div class="col-md-7 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="nama@email.com" value="{{ old('email') }}" required>
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label">No. telepon</label>
                        <input name="no_telepon" class="form-control" placeholder="08xxxxxxxxxx" value="{{ old('no_telepon') }}" required>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="2" required>{{ old('alamat') }}</textarea>
                    </div>
                </div>

                <p class="reg-section-lbl"><i class="bi bi-shield-lock"></i>Keamanan</p>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kata sandi</label>
                        <input type="password" name="password" class="form-control" minlength="8" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Konfirmasi kata sandi</label>
                        <input type="password" name="password_confirmation" class="form-control" minlength="8" required>
                    </div>
                </div>

                <button class="btn btn-login w-100"><i class="bi bi-person-plus me-1"></i> Daftar</button>
            </form>
            <p class="text-center text-muted small login-foot mb-0">
                Sudah punya akun? <a href="{{ route('customer.login') }}" class="text-decoration-none fw-semibold">Masuk di sini</a>
            </p>
        </div>
    </div>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script>
        @if (session('error'))
            Swal.fire({ icon: 'error', title: 'Gagal', text: @json(session('error')), confirmButtonColor: '#176b87', confirmButtonText: 'Oke, Mengerti' });
        @endif

        document.querySelectorAll('form').forEach(f => {
            f.setAttribute('novalidate', '');
            f.addEventListener('submit', e => {
                const salah = [...f.querySelectorAll('input, textarea')].filter(el => ! el.checkValidity());
                if (! salah.length) return;
                e.preventDefault();
                salah.forEach(el => {
                    el.classList.add('is-salah');
                    el.parentElement.querySelector('.catatan-salah')?.remove();
                    const note = document.createElement('div');
                    note.className = 'catatan-salah';
                    note.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i>' + (el.validity.valueMissing ? 'Kolom ini belum diisi.' : 'Format belum sesuai.');
                    el.insertAdjacentElement('afterend', note);
                });
                salah[0].focus();
            });
            f.addEventListener('input', e => {
                e.target.classList.remove('is-salah');
                e.target.parentElement.querySelector('.catatan-salah')?.remove();
            }, true);
        });
    </script>
</body>
</html>
