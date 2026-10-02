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
        :root { --primary:#176b87; --primary-dark:#0f526b; --teal:#2f7fd1; --ink:#15243b; --muted:#637189; --line:#e4ebf2; }
        * { box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; min-height:100vh; margin:0; display:grid; place-items:center;
               background:#f4f7fa; padding:1.5rem; }
        h1,h2,.brand { font-family:'Plus Jakarta Sans',sans-serif; letter-spacing:-.02em; }
        @keyframes cardIn { from { opacity:0; transform:translateY(18px) scale(.98); } to { opacity:1; transform:none; } }

        /* Tinggi kartu SENGAJA dibatasi (bukan min-height saja) supaya panel form yang panjang
           (banyak field) scroll DI DALAM panelnya sendiri — panel visual & badge peran di pojok
           tetap diam di tempat. Tanpa batas ini, seluruh kartu (termasuk panel visual) ikut
           tergulung saat body yang scroll, jadi terlihat terputus/berantakan. */
        .reg-shell { position:relative; z-index:1; width:min(1040px, 100%); background:#fff; border-radius:1.75rem;
            box-shadow:0 34px 76px -20px rgba(15,36,52,.28); overflow:hidden; animation:cardIn .5s cubic-bezier(.2,.7,.3,1) both;
            display:flex; height:min(700px, calc(100vh - 3rem)); }

        .reg-visual { flex:1 1 40%; position:relative; overflow:hidden; padding:2.75rem 2.5rem; color:#fff;
            background:url('{{ asset('images/gedung_bitc.png') }}') center/cover no-repeat;
            display:flex; flex-direction:column; justify-content:center; gap:2.25rem; }
        .reg-visual::before { content:''; position:absolute; inset:0; z-index:0; pointer-events:none;
            background:linear-gradient(180deg, rgba(9,23,36,.12) 0%, rgba(9,23,36,.28) 38%, rgba(9,23,36,.62) 72%, rgba(9,23,36,.82) 100%); }
        .reg-visual::after { content:''; position:absolute; z-index:0; pointer-events:none; border-radius:50%; filter:blur(6px); opacity:.22;
            width:16rem; height:16rem; background:#2f7fd1; bottom:-6rem; right:-5rem; }
        .rv-grid { position:absolute; inset:0; z-index:0; opacity:.05;
            background-image:linear-gradient(rgba(255,255,255,.6) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.6) 1px, transparent 1px);
            background-size:32px 32px; mask-image:radial-gradient(60% 60% at 30% 30%, #000, transparent); }
        .reg-visual > * { position:relative; z-index:1; }
        .rv-headline { font-weight:800; font-size:1.55rem; line-height:1.3; margin:0 0 .75rem; max-width:20rem; text-shadow:0 3px 14px rgba(0,0,0,.4); }
        .rv-sub { color:#d7e7ec; font-size:.9rem; line-height:1.6; max-width:22rem; margin:0; text-shadow:0 2px 10px rgba(0,0,0,.35); }
        .rv-points { list-style:none; margin:2.25rem 0 0; padding:0; display:flex; flex-direction:column; gap:.85rem; }
        .rv-points li { display:flex; align-items:center; gap:.65rem; font-size:.85rem; color:#eef6f7; text-shadow:0 2px 8px rgba(0,0,0,.4); }
        .rv-points .ic { display:grid; place-items:center; width:1.9rem; height:1.9rem; border-radius:.6rem; background:rgba(255,255,255,.16); backdrop-filter:blur(2px); flex:none; font-size:.85rem; }

        /* justify-content SENGAJA flex-start, bukan center — form ini lebih panjang dari tinggi
           kartu jadi ISI-nya pasti overflow (scroll). Kalau dipusatkan (center) pada container
           yang overflow, browser mulai men-scroll dari TENGAH konten, judul di paling atas jadi
           terpotong/tak terlihat sampai pengguna scroll ke atas sendiri — judul harus kelihatan
           duluan (form dibaca dari atas ke bawah). */
        .reg-form-panel { flex:1 1 60%; padding:2.75rem 2.75rem 2rem; display:flex; flex-direction:column; justify-content:flex-start;
            overflow-y:auto; scrollbar-gutter:stable; scrollbar-width:thin; scrollbar-color:var(--line) transparent; }
        .reg-form-panel::-webkit-scrollbar { width:8px; }
        .reg-form-panel::-webkit-scrollbar-track { background:transparent; }
        .reg-form-panel::-webkit-scrollbar-thumb { background:var(--line); border-radius:9999px; }
        .reg-form-panel::-webkit-scrollbar-thumb:hover { background:#c7d3de; }
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

        /* Ikon mata tampilkan/sembunyikan kata sandi — form ini pakai .form-control Bootstrap
           polos (bukan floating-label .fl-field seperti halaman lain), jadi tombolnya diposisikan
           relatif terhadap wrapper sendiri di sini. */
        .pw-wrap { position:relative; }
        .pw-wrap .form-control { padding-right:2.75rem; }
        .pw-eye { position:absolute; right:.3rem; top:50%; transform:translateY(-50%); width:2.1rem; height:2.1rem;
            display:grid; place-items:center; background:none; border:0; color:var(--muted); border-radius:50%;
            cursor:pointer; transition:color .15s ease, background .15s ease; }
        .pw-eye:hover { color:var(--primary-dark); background:var(--line); }
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
            .reg-shell { height:auto; }
            .reg-form-panel { padding:2.25rem 1.75rem; overflow-y:visible; }
            .corner-toggle { position:static; margin:0 0 1rem; justify-content:flex-end; background:transparent; box-shadow:none; padding:0; }
            /* Kartu bertumpuk vertikal di HP: pilihan peran di atas form, bukan kolom sempit di sampingnya. */
            .login-shell, .reg-shell { flex-direction:column; }
            .corner-toggle { align-self:stretch; margin:1rem 1.15rem 0; }
        }
        /* Badge peran di pojok kanan atas tidak boleh menimpa judul form pada layar lebar. */
        @media (min-width: 768px) { .reg-form-panel { padding-top:3.5rem; } }
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
    <div class="reg-shell">
        <div class="corner-toggle" role="tablist" aria-label="Pilih peran">
            <span class="active"><i class="bi bi-person"></i> Pemesan</span>
            <a href="{{ route('admin.login') }}"><i class="bi bi-shield-lock"></i> Admin</a>
        </div>

        <div class="reg-visual">
            <div class="rv-grid"></div>
            <div>
                <h1 class="rv-headline">Daftar untuk mengelola reservasi Anda</h1>
                <p class="rv-sub">Dengan satu akun, riwayat reservasi Anda tersimpan dan dapat dipantau kapan saja.</p>
                <ul class="rv-points">
                    <li><span class="ic"><i class="bi bi-clock-history"></i></span>Data diri cukup diisi satu kali</li>
                    <li><span class="ic"><i class="bi bi-journal-check"></i></span>Riwayat reservasi tersimpan</li>
                    <li><span class="ic"><i class="bi bi-shield-check"></i></span>Kata sandi tersimpan terenkripsi</li>
                </ul>
            </div>
        </div>

        <div class="reg-form-panel">
            <div class="reg-head">
                <h2>Daftar sebagai Pemesan</h2>
                <p>Buat akun untuk mengajukan reservasi fasilitas BITC.</p>
            </div>

            @php $galatLain = collect($errors->getMessages())->except(['alamat', 'email', 'nama_lengkap', 'no_telepon', 'password', 'password_confirmation', 'pekerjaan', 'usia'])->flatten(); @endphp
            @if ($galatLain->isNotEmpty())
                <div class="alert alert-danger py-2 small mb-3" role="alert">{{ $galatLain->first() }}</div>
            @endif

            <form method="POST" action="{{ route('customer.register.attempt') }}">
                @csrf

                <p class="reg-section-lbl"><i class="bi bi-person-vcard"></i>Identitas</p>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input name="nama_lengkap" class="form-control" value="{{ old('nama_lengkap') }}" autocomplete="off" minlength="3" maxlength="100" pattern="[\p{L}\s.'\-]+" data-pesan-pola="Nama lengkap hanya boleh berisi huruf." required>
                        @error('nama_lengkap')<div class="catatan-salah"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Usia</label>
                        <input type="number" name="usia" class="form-control" min="17" max="100" step="1" inputmode="numeric" value="{{ old('usia') }}" autocomplete="off" data-pesan-min="Usia minimal 17 tahun." data-pesan-maks="Usia maksimal 100 tahun." required>
                        @error('usia')<div class="catatan-salah"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Pekerjaan</label>
                        <input name="pekerjaan" class="form-control" value="{{ old('pekerjaan') }}" autocomplete="off" minlength="3" maxlength="100" pattern="[\p{L}\s.,'\/&amp;\(\)\-]+" data-pesan-pola="Pekerjaan hanya boleh berisi huruf." required>
                        @error('pekerjaan')<div class="catatan-salah"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                    </div>
                </div>

                <p class="reg-section-lbl"><i class="bi bi-geo-alt"></i>Kontak</p>
                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label" for="regEmail">Email</label>
                        <div class="otp-field pw-wrap">
                            <input type="email" name="email" id="regEmail" class="form-control" placeholder="nama@email.com" value="{{ old('email') }}" autocomplete="email" maxlength="150" pattern="[^@\s]+@[^@\s]+\.[^@\s]+" data-pesan-pola="Format email tidak valid, contoh: nama@email.com." required>
                            @include('partials.otp-email-tombol', ['emailId' => 'regEmail'])
                        </div>
                        @error('email')<div class="catatan-salah otp-galat-email"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                        @include('partials.otp-email', [
                            'emailId' => 'regEmail', 'url' => route('customer.register.otp'), 'urlVerifikasi' => route('customer.register.otp.verifikasi'),
                            'status' => $otpStatus ?? null, 'terverifikasi' => $otpTerverifikasi ?? false,
                        ])
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">No. Telepon</label>
                        <input type="tel" name="no_telepon" class="form-control" placeholder="08xxxxxxxxxx" value="{{ old('no_telepon') }}" inputmode="numeric" maxlength="16" pattern="\+?[0-9]{10,15}" data-pesan-pola="No. telepon harus berupa angka 10–15 digit." autocomplete="off" required>
                        @error('no_telepon')<div class="catatan-salah"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="2" autocomplete="off" minlength="10" maxlength="500" required>{{ old('alamat') }}</textarea>
                        @error('alamat')<div class="catatan-salah"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                    </div>
                </div>

                <p class="reg-section-lbl"><i class="bi bi-shield-lock"></i>Keamanan</p>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kata Sandi</label>
                        <div class="pw-wrap">
                            <input type="password" name="password" id="regPassword" class="form-control" minlength="8" maxlength="100" pattern="(?=.*[A-Za-z])(?=.*[0-9]).{8,}" data-pesan-pola="Kata sandi minimal 8 karakter, berisi huruf dan angka." autocomplete="new-password" required>
                            <button type="button" class="pw-eye" data-pw-toggle-for="regPassword" aria-label="Tampilkan/sembunyikan kata sandi"><i class="bi bi-eye"></i></button>
                        </div>
                        @error('password')<div class="catatan-salah"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $message }}</span></div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Konfirmasi Kata Sandi</label>
                        <div class="pw-wrap">
                            <input type="password" name="password_confirmation" id="regPasswordConf" class="form-control" maxlength="100" autocomplete="new-password" required>
                            <button type="button" class="pw-eye" data-pw-toggle-for="regPasswordConf" aria-label="Tampilkan/sembunyikan kata sandi"><i class="bi bi-eye"></i></button>
                        </div>
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
            Swal.fire({ icon: 'error', title: 'Gagal', text: @json(session('error')), confirmButtonColor: '#176b87', confirmButtonText: 'Mengerti' });
        @endif

        document.querySelectorAll('.pw-eye').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = document.getElementById(btn.dataset.pwToggleFor);
                const icon = btn.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('bi-eye', 'bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.replace('bi-eye-slash', 'bi-eye');
                }
            });
        });

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
        document.addEventListener('input', e => {
            if (e.target.tagName !== 'INPUT' || e.target.type !== 'tel') return;
            const plus = e.target.value.startsWith('+') ? '+' : '';
            e.target.value = plus + e.target.value.replace(/[^0-9]/g, '');
        });

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
