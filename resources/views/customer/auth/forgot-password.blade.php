<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lupa Kata Sandi | WADUH</title>
    <link href="{{ asset('vendor/fonts/fonts.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>
        :root { --primary:#176b87; --primary-dark:#0f526b; --teal:#2f7fd1; --ink:#15243b; --muted:#637189; --line:#e4ebf2; }
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
            width:16rem; height:16rem; background:#2f7fd1; bottom:-6rem; right:-5rem; }
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
        .catatan-salah { align-items:flex-start; line-height:1.4; }
        .catatan-salah i { margin-top:.12rem; flex:none; }
        @media (max-width: 575.98px) {
            body { padding:.75rem; }
            .login-shell, .reg-shell { border-radius:1.25rem; }
            .login-form-panel, .reg-form-panel { padding:1.75rem 1.15rem 1.5rem; }
            .login-form-head h2, .reg-head h2 { font-size:1.25rem; }
        }
    </style>
</head>
<body>
    <div class="login-shell">
        <div class="login-visual">
            <div class="lv-grid"></div>
            <div>
                <h1 class="lv-headline">Atur ulang kata sandi akun Anda</h1>
                <p class="lv-sub">Masukkan email akun Pemesan Anda. Tautan untuk membuat kata sandi baru akan dikirim ke email tersebut.</p>
                <ul class="lv-points">
                    <li><span class="ic"><i class="bi bi-envelope-check"></i></span>Tautan dikirim ke email terdaftar</li>
                    <li><span class="ic"><i class="bi bi-shield-lock"></i></span>Tautan berlaku selama 60 menit</li>
                </ul>
            </div>
        </div>

        <div class="login-form-panel">
            <div class="login-form-head">
                <h2>Lupa Kata Sandi</h2>
                <p>Masukkan email akun Pemesan Anda untuk menerima tautan pengaturan ulang kata sandi.</p>
            </div>

            @php $galatLain = collect($errors->getMessages())->except(['email'])->flatten(); @endphp
            @if ($galatLain->isNotEmpty())
                <div class="alert alert-danger py-2 small mb-3" role="alert">{{ $galatLain->first() }}</div>
            @endif

            <form method="POST" action="{{ route('customer.password.email') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="nama@email.com" value="{{ old('email') }}" required autofocus pattern="[^@\s]+@[^@\s]+\.[^@\s]+" data-pesan-pola="Format email tidak valid, contoh: nama@email.com.">
                    </div>
                    @error('email')<div class="catatan-salah"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                </div>
                <button class="btn btn-login w-100"><i class="bi bi-send me-1"></i> Kirim Tautan</button>
            </form>
            <p class="text-center text-muted small login-foot mb-0"><a href="{{ route('customer.login') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Kembali ke Halaman Masuk</a></p>
        </div>
    </div>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <link href="{{ asset('vendor/waduh/popup.css') }}?v={{ filemtime(public_path('vendor/waduh/popup.css')) }}" rel="stylesheet">
    <script src="{{ asset('vendor/waduh/popup.js') }}?v={{ filemtime(public_path('vendor/waduh/popup.js')) }}"></script>
    <script>
        @if (session('success'))
            Swal.fire({ icon: 'success', title: 'Berhasil', text: @json(session('success')), confirmButtonColor: '#176b87', confirmButtonText: 'Tutup' });
        @endif
        @if (session('error'))
            Swal.fire({ icon: 'error', title: 'Gagal', text: @json(session('error')), confirmButtonColor: '#176b87', confirmButtonText: 'Mengerti' });
        @endif

        // Kolom masuk dibuat readonly saat halaman dimuat agar tidak diisi otomatis oleh peramban,
        // lalu dilepas sesaat kemudian sehingga ketukan pertama langsung dapat dipakai mengetik.
        document.querySelectorAll('[data-lepas-readonly]').forEach(el => {
            const lepas = () => el.removeAttribute('readonly');
            ['focus', 'pointerdown', 'touchstart'].forEach(ev => el.addEventListener(ev, lepas, { passive: true }));
            setTimeout(lepas, 500);
        });

        // Validasi di sisi peramban: satu pesan per kolom, tepat di bawah kolomnya.
        const labelKolom = el => (el.closest('.mb-3')?.querySelector('.form-label')?.textContent || 'Kolom ini').trim();
        const pesanKolom = el => {
            const v = el.validity, l = labelKolom(el);
            if (v.valueMissing) return l + ' wajib diisi.';
            if (v.patternMismatch && el.dataset.pesanPola) return el.dataset.pesanPola;
            if (el.type === 'email' && (v.typeMismatch || v.patternMismatch)) return 'Format email tidak valid, contoh: nama@email.com.';
            if (v.typeMismatch || v.patternMismatch) return 'Format ' + l.toLowerCase() + ' tidak valid.';
            if (v.tooShort) return l + ' minimal ' + el.minLength + ' karakter.';
            if (v.tooLong) return l + ' maksimal ' + el.maxLength + ' karakter.';
            if (v.rangeUnderflow) return el.dataset.pesanMin || (l + ' minimal ' + el.min + '.');
            if (v.rangeOverflow) return el.dataset.pesanMaks || (l + ' maksimal ' + el.max + '.');
            if (v.badInput || v.stepMismatch) return l + ' harus berupa angka.';
            return l + ' tidak valid.';
        };
        const wadahKolom = el => el.closest('.input-group, .pw-wrap') || el;
        const hapusCatatan = el => {
            el.classList.remove('is-salah');
            let n = wadahKolom(el).nextElementSibling;
            while (n && n.classList.contains('catatan-salah')) { const berikut = n.nextElementSibling; n.remove(); n = berikut; }
        };
        const tandaiKolom = (el, pesan) => {
            hapusCatatan(el);
            el.classList.add('is-salah');
            const note = document.createElement('div');
            note.className = 'catatan-salah';
            note.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i><span></span>';
            note.querySelector('span').textContent = pesan;
            wadahKolom(el).insertAdjacentElement('afterend', note);
        };
        document.querySelectorAll('form').forEach(f => {
            f.setAttribute('novalidate', '');
            f.addEventListener('submit', e => {
                if (f.dataset.mengirim === '1') { e.preventDefault(); return; }
                const salah = [...f.querySelectorAll('input, textarea')]
                    .filter(el => el.type !== 'hidden' && ! el.readOnly && ! el.checkValidity());
                const sandi = f.querySelector('[name="password"]');
                const konfirmasi = f.querySelector('[name="password_confirmation"]');
                const tidakCocok = sandi && konfirmasi && ! salah.includes(konfirmasi) && sandi.value !== konfirmasi.value;
                if (! salah.length && ! tidakCocok) {
                    f.dataset.mengirim = '1';
                    const tombol = f.querySelector('button.btn-login');
                    const labelAsli = tombol?.innerHTML;
                    if (tombol) { tombol.disabled = true; tombol.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Memproses…'; }
                    // Bila pengiriman dibatalkan pemeriksa lain (mis. email belum diverifikasi OTP),
                    // kunci tombol dilepas lagi agar form dapat dikirim ulang.
                    setTimeout(() => {
                        if (! e.defaultPrevented) return;
                        f.dataset.mengirim = '';
                        if (tombol) { tombol.disabled = false; tombol.innerHTML = labelAsli; }
                    }, 0);
                    return;
                }
                e.preventDefault();
                salah.forEach(el => tandaiKolom(el, pesanKolom(el)));
                if (tidakCocok) tandaiKolom(konfirmasi, 'Konfirmasi kata sandi tidak cocok.');
                (salah[0] || konfirmasi).focus();
            });
            f.addEventListener('input', e => hapusCatatan(e.target), true);
        });
        // Halaman dipulihkan dari riwayat peramban (tombol Kembali): buka kembali tombol kirim.
        window.addEventListener('pageshow', e => {
            if (! e.persisted) return;
            document.querySelectorAll('form').forEach(f => { delete f.dataset.mengirim; });
            document.querySelectorAll('button.btn-login[disabled]').forEach(b => window.location.reload());
        });
    </script>
</body>
</html>
