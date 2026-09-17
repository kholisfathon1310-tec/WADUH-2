<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ubah Kata Sandi | WADUH</title>
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

        .login-visual { flex:1 1 46%; position:relative; overflow:hidden; padding:2.75rem 2.5rem; color:#fff;
            background:url('{{ asset('images/gedung_bitc.png') }}') center/cover no-repeat;
            display:flex; flex-direction:column; justify-content:center; gap:2.25rem; }
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

        .login-form-panel { flex:1 1 54%; padding:3rem 3rem 2.25rem; display:flex; flex-direction:column; justify-content:center; }
        .login-form-head { margin-bottom:1.75rem; }
        .login-form-head h2 { font-size:1.5rem; font-weight:800; margin:0 0 .35rem; color:var(--ink); }
        .login-form-head p { color:var(--muted); font-size:.9rem; margin:0; }

        .form-control { border-radius:.75rem; border-color:var(--line); padding:.65rem .9rem; }
        .form-control:focus { border-color:var(--primary); box-shadow:0 0 0 .2rem rgba(23,107,135,.12); }
        .form-control[readonly] { background:#f4f8fa; color:var(--muted); }
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
    </style>
</head>
<body>
    <div class="login-shell">
        <div class="login-visual">
            <div class="lv-grid"></div>
            <div>
                <h1 class="lv-headline">Buat kata sandi baru</h1>
                <p class="lv-sub">Pastikan kata sandi baru mudah Anda ingat, tapi sulit ditebak orang lain.</p>
                <ul class="lv-points">
                    <li><span class="ic"><i class="bi bi-shield-check"></i></span>Minimal 8 karakter</li>
                </ul>
            </div>
        </div>

        <div class="login-form-panel">
            <div class="login-form-head">
                <h2>Ubah Kata Sandi</h2>
                <p>Masukkan kata sandi baru untuk akun Pemesan Anda.</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger py-2 small"><ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <form method="POST" action="{{ route('customer.password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $email) }}" readonly required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kata Sandi Baru</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Minimal 8 karakter" minlength="8" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Konfirmasi Kata Sandi Baru</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                        <input type="password" name="password_confirmation" class="form-control" placeholder="Ulangi kata sandi baru" minlength="8" required>
                    </div>
                </div>
                <button class="btn btn-login w-100"><i class="bi bi-check-circle me-1"></i> Ubah Kata Sandi</button>
            </form>
            <p class="text-center text-muted small login-foot mb-0"><a href="{{ route('customer.login') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Kembali ke Halaman Masuk</a></p>
        </div>
    </div>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script>
        @if (session('success'))
            Swal.fire({ icon: 'success', title: 'Berhasil', text: @json(session('success')), confirmButtonColor: '#176b87', confirmButtonText: 'Oke' });
        @endif
        @if (session('error'))
            Swal.fire({ icon: 'error', title: 'Gagal', text: @json(session('error')), confirmButtonColor: '#176b87', confirmButtonText: 'Oke, Mengerti' });
        @endif

        document.querySelectorAll('form').forEach(f => {
            f.setAttribute('novalidate', '');
            f.addEventListener('submit', e => {
                const salah = [...f.querySelectorAll('input:not([readonly])')].filter(el => ! el.checkValidity());
                if (! salah.length) return;
                e.preventDefault();
                salah.forEach(el => {
                    el.classList.add('is-salah');
                    const grup = el.closest('.input-group') || el;
                    grup.parentElement.querySelector('.catatan-salah')?.remove();
                    const note = document.createElement('div');
                    note.className = 'catatan-salah';
                    note.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i>' +
                        (el.validity.valueMissing ? 'Wajib diisi.' : 'Minimal 8 karakter.');
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
