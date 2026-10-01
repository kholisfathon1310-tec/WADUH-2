<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Reservasi Fasilitas') | WADUH</title>
    <link href="{{ asset('vendor/fonts/fonts.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>
        :root {
            --ink:#0f172a; --muted:#64748b; --soft:#94a3b8;
            --primary:#0e6b7d; --primary-dark:#084b58; --primary-soft:#e6f2f4;
            --teal:#2f7fd1;
            --surface:#f7f9fc; --line:#e5e9ef;
        }
        * { scrollbar-color: #c3d5de transparent; }
        body {
            font-family:'DM Sans',sans-serif; color:var(--ink); min-height:100vh; display:flex; flex-direction:column;
            line-height:1.6; -webkit-font-smoothing:antialiased;
            background:
                radial-gradient(46rem 26rem at 108% -8%, #ddf3ee 0%, transparent 60%),
                radial-gradient(36rem 22rem at -12% 8%, #e2eefb 0%, transparent 55%),
                var(--surface);
            background-attachment:fixed;
        }
        h1,h2,h3,h4,h5,.step-label { font-family:'Plus Jakarta Sans',sans-serif; letter-spacing:-.02em; }
        ::selection { background:#bfe3ea; color:var(--primary-dark); }

        @keyframes riseIn { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:none; } }
        [data-reveal] { animation:riseIn .55s cubic-bezier(.2,.7,.3,1) both; }
        @media (prefers-reduced-motion: reduce) { [data-reveal] { animation:none; } }

        /* Navbar — tanpa logo, cuma pill menu, mengambang & sticky. */
        .nav-shell { position:sticky; top:.85rem; z-index:1030; padding:0 1rem; }
        .topnav {
            max-width:1200px; margin:0 auto;
            background:rgba(255,255,255,.85); backdrop-filter:blur(16px); border:1px solid rgba(228,235,242,.9);
            border-radius:1.5rem; box-shadow:0 14px 34px -18px rgba(21,36,59,.28); padding:.4rem .5rem;
            transition:box-shadow .2s ease;
        }
        .topnav .container-fluid { justify-content:center; }
        .topnav .nav-link { color:#4e5c70; font-weight:600; font-size:.9rem; border-radius:.8rem; padding:.55rem 1rem; transition:background .15s ease, color .15s ease; }
        .topnav .nav-link:hover, .topnav .nav-link.active { color:var(--primary); background:#eef6f9; }
        .btn-nav { color:#fff !important; background:var(--primary); border-radius:.75rem; padding:.6rem 1.2rem;
            font-weight:700; font-size:.9rem; box-shadow:0 10px 22px -8px rgba(14,107,125,.55);
            transition:transform .15s ease, background .15s ease; }
        .btn-nav:hover { background:var(--primary-dark); transform:translateY(-1px); }
        .topnav .dropdown-menu { border:1px solid var(--line); border-radius:1rem; box-shadow:0 14px 34px -14px rgba(15,23,42,.14); padding:.4rem; margin-top:.5rem !important; }
        .topnav .dropdown-item { border-radius:.55rem; font-weight:600; font-size:.9rem; padding:.55rem .75rem; }
        .topnav .dropdown-item:hover { background:var(--primary-soft); color:var(--primary-dark); }
        .topnav .dropdown-item .dot { display:inline-block; width:.6rem; height:.6rem; border-radius:50%; margin-right:.6rem; background:var(--primary); }

        /* Stepper — pill progress yang lebih hidup */
        /* ══════════════════════════════════════════════════════════════
           STEPPER — centered container dengan max-width, kompak & rapi.
           Berlaku di seluruh halaman yang mengisi section stepper.
           ══════════════════════════════════════════════════════════════ */
        .stepper-wrap { max-width: 780px; margin: 0 auto; }
        .stepper { display:flex; align-items:center; gap:.4rem; overflow-x:auto; padding:.4rem .2rem .8rem; }
        .step { display:flex; align-items:center; gap:.55rem; flex:1 1 0; min-width:110px; }
        .step .dot {
            display:grid; place-items:center; width:2rem; height:2rem;
            border-radius:50%; font-weight:700; font-size:.82rem;
            background:#eef2f6; color:var(--muted); flex:none;
            border:1.5px solid #e2e9f0;
            transition:background .2s ease, box-shadow .2s ease, transform .2s ease, border-color .2s ease;
        }
        .step .step-label {
            font-size:.76rem; font-weight:700; color:var(--muted);
            white-space:nowrap; letter-spacing:.01em;
        }
        .step .bar {
            height:2.5px; border-radius:2px; background:#e2e9f0; flex:1; min-width:14px;
        }
        .step.done .dot { background:var(--primary); color:#fff; border-color:var(--primary); }
        .step.done .bar { background:var(--primary); }
        .step.done .step-label { color:var(--primary); }
        .step.now .dot {
            background:var(--primary); color:#fff; border-color:var(--primary);
            box-shadow:0 0 0 4px rgba(14,107,125,.14);
        }
        .step.now .step-label { color:var(--primary); }
        @media (max-width: 575.98px) {
            .step .step-label { display:none; }
            .step { min-width:0; }
        }

        /* Cards & buttons — lebih rounded & hangat */
        .xcard { background:#fff; border:1px solid var(--line); border-radius:1.35rem; box-shadow:0 4px 18px -4px rgba(21,60,73,.07); transition:transform .22s ease, box-shadow .22s ease, border-color .22s ease; }
        a.xcard { text-decoration:none; color:inherit; display:block; }
        .xcard.hover:hover { transform:translateY(-5px); box-shadow:0 20px 40px -12px rgba(21,60,73,.16); border-color:#bfe0e0; }
        .icon-tile { display:grid; place-items:center; width:3.1rem; height:3.1rem; border-radius:1rem; background:linear-gradient(135deg,#e5f4f3,#dcf0ee); color:var(--primary); font-size:1.3rem; }
        .btn-brand { background:linear-gradient(135deg,var(--primary),var(--primary-dark)); border-color:var(--primary); color:#fff; font-weight:700; border-radius:.85rem; transition:background .15s ease, border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
        .btn-brand:hover { border-color:var(--primary-dark); color:#fff; box-shadow:0 10px 22px -8px rgba(23,107,135,.45); transform:translateY(-1px); }
        .btn-brand-outline { color:var(--primary); border:1.5px solid #cfe0e6; border-radius:.85rem; font-weight:700; background:#fff; transition:all .15s ease; }
        .btn-brand-outline:hover { color:var(--primary-dark); border-color:var(--primary); background:#f4fafb; transform:translateY(-1px); }
        .btn-success { border-radius:.85rem; font-weight:700; }
        .form-control,.form-select { border-radius:.75rem; border-color:#d8e2ea; padding:.6rem .9rem; }
        .form-control:focus,.form-select:focus { border-color:var(--primary); box-shadow:0 0 0 .2rem rgba(23,107,135,.12); }
        .form-label { font-weight:600; font-size:.86rem; color:#3c4a5f; }
        .breadcrumb { font-size:.83rem; }
        .breadcrumb a { color:var(--primary); text-decoration:none; font-weight:600; }
        .eyebrow-sm { color:var(--primary); font-size:.72rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase; }
        .page-head h1 { font-weight:800; }
        /* Varian banner berwarna (mis. kategori/jenis-sewa/lantai) — hanya saat dikombinasikan dengan text-white */
        .page-head.text-white { position:relative; overflow:hidden; border-radius:1.5rem !important; box-shadow:0 20px 42px -18px rgba(15,60,80,.35); }
        .page-head.text-white::before { content:''; position:absolute; inset:0; background:radial-gradient(28rem 16rem at 105% -20%, rgba(255,255,255,.16), transparent 55%); pointer-events:none; }
        .page-head.text-white > * { position:relative; }
        .alert { border-radius:1rem; }

        /* Availability badges */
        .avail { display:inline-flex; align-items:center; gap:.35rem; font-size:.75rem; font-weight:700; padding:.32rem .65rem; border-radius:2rem; }
        .avail.hijau { background:#e2f7ef; color:#0d8a5f; }
        .avail.kuning { background:#fff4d6; color:#9a6b00; }
        .avail.merah { background:#fde4e4; color:#c02929; }

        .site-footer { margin-top:auto; padding:2rem 0 1.75rem; color:var(--muted); font-size:.83rem; background:transparent; border-top:1px solid var(--line); }

        /* Validasi klien yang ramah — mengganti bubble bawaan browser */
        .is-salah { border-color:#d95757 !important; background:#fffafa !important; box-shadow:0 0 0 .18rem rgba(217,87,87,.12) !important; animation:goyang .3s; }
        @keyframes goyang { 25% { transform:translateX(-4px); } 75% { transform:translateX(4px); } }
        .catatan-salah { display:flex; align-items:center; gap:.3rem; color:#c02929; font-size:.78rem; font-weight:600; margin-top:.3rem; }
        /* SweetAlert2: matikan pointer-events overlay begitu animasi fade-out mulai, supaya klik berikutnya (mis. buka modal lagi) tidak tertelan. */
        .swal2-backdrop-hide { pointer-events: none !important; }

        /* SweetAlert2 harus selalu di atas overlay form jadwal (1990) dan lightbox foto (2000). */
        .swal2-container { z-index:3000 !important; }
        .is-salah.tanpa-goyang { animation:none; }
        /* Label mengambang punya latar putih — kolom yang ditandai jangan diberi latar merah
           muda supaya tidak muncul "tambalan" putih di belakang label. */
        .fl-field .fl-input.is-salah { background:#fff !important; }
        .catatan-salah { align-items:flex-start; line-height:1.35; }
        .catatan-salah i { flex:none; margin-top:.12rem; }

        /* ─── RESPONSIF ───────────────────────────────────────── */
        body { overflow-x:clip; }
        .site-footer .container { row-gap:.35rem; }
        @media (max-width: 991.98px) {
            /* Menu terlipat (HP/tablet): daftar menu & tombol Masuk turun di bawah tombol burger. */
            .topnav .container-fluid { justify-content:flex-start; }
            .topnav .navbar-collapse { padding:.35rem .25rem .5rem; }
            .topnav .btn-nav { display:inline-flex; align-items:center; margin-top:.4rem; }
        }
        @media (max-width: 575.98px) {
            .nav-shell { top:.5rem; padding:0 .6rem; }
            .topnav { border-radius:1.1rem; }
            .site-footer { padding:1.4rem 0 1.25rem; font-size:.78rem; }
            .site-footer .container { flex-direction:column; align-items:flex-start !important; }
            .err-card { padding:.85rem .9rem !important; gap:.65rem !important; }
        }
    </style>
</head>
<body>
    <div class="nav-shell">
        <nav class="navbar navbar-expand-lg topnav">
        <div class="container-fluid">
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
                <i class="bi bi-list fs-3"></i>
            </button>
            <div id="nav" class="collapse navbar-collapse">
                {{-- Menu SAMA dengan navbar beranda (home.blade.php) supaya bar atas tidak berubah
                     saat berpindah dari beranda ke halaman publik lain seperti Cek Status. --}}
                @php
                    $navLantai = \App\Models\Lantai::with('fasilitas:id_fasilitas,id_lantai,kategori_fasilitas')
                        ->orderBy('id_lantai')
                        ->get()
                        ->map(fn ($l) => [
                            'id'       => $l->id_lantai,
                            'nomor'    => $l->nomor_lantai,
                            'kategori' => $l->fasilitas->countBy('kategori_fasilitas')->sortDesc()->keys()->first(),
                        ])
                        ->filter(fn ($l) => $l['kategori']);
                @endphp
                <ul class="navbar-nav mx-auto align-items-lg-center gap-lg-1">
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ url('/') }}">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ url('/') }}#tentang">Tentang BITC</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('fasilitas.*') ? 'active' : '' }}" href="{{ route('reservasi.index') }}" role="button" data-bs-toggle="dropdown" aria-expanded="false">Fasilitas</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('reservasi.index') }}"><i class="bi bi-grid-3x3-gap me-2"></i>Semua Lantai</a></li>
                            <li><hr class="dropdown-divider"></li>
                            @foreach ($navLantai as $nl)
                                <li>
                                    <a class="dropdown-item" href="{{ route('reservasi.denah', ['kategori' => $nl['kategori'], 'lantai' => $nl['id']]) }}">
                                        <span class="dot"></span>Lantai {{ $nl['nomor'] }} · {{ $nl['kategori'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="{{ url('/') }}#kontak">Kontak</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('cek-status.*') ? 'active' : '' }}" href="{{ route('cek-status.form') }}">Cek Status</a></li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-nav" href="{{ auth('customer')->check() ? route('customer.dashboard') : route('customer.login') }}"><i class="bi bi-box-arrow-in-right me-1"></i> Masuk</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    </div>

    <main class="container py-4 py-md-5">
        {{-- Tombol kembali global --}}
        <button type="button" onclick="history.back()" class="btn btn-sm btn-brand-outline mb-3"><i class="bi bi-arrow-left me-1"></i>Kembali</button>

        @hasSection('stepper')
            <div class="stepper-wrap mb-4">@yield('stepper')</div>
        @endif

        @if ($errors->any())
            <div class="err-card mb-3" role="alert">
                <span class="err-ic"><i class="bi bi-exclamation-triangle"></i></span>
                <div>
                    <div class="fw-bold" style="color:#a12c2c">Mohon periksa kembali {{ $errors->count() }} isian berikut</div>
                    <ul class="err-list mb-0 mt-1">
                        @foreach ($errors->all() as $e)<li><i class="bi bi-arrow-right-short"></i>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            </div>
            <style>
                .err-card { display:flex; gap:.9rem; align-items:flex-start; padding:1rem 1.15rem; background:linear-gradient(120deg,#fdf1f1,#fff7f4); border:1px solid #f0c9c9; border-left:4px solid #d95757; border-radius:1rem; box-shadow:0 8px 22px rgba(180,60,60,.08); }
                .err-ic { display:grid; place-items:center; flex:none; width:2.6rem; height:2.6rem; border-radius:.9rem; background:#fbdddd; color:#c02929; font-size:1.3rem; }
                .err-list { list-style:none; margin:0; padding:0; font-size:.88rem; color:#7c3a3a; }
                .err-list li { padding:.12rem 0; }
                .err-list i { color:#d95757; }
            </style>
        @endif

        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="d-flex align-items-center gap-2"><img src="{{ asset('images/logo_bitc_crop.png') }}" alt="Logo BITC" style="height:1.6rem;width:auto;"><span class="fw-bold">WADUH</span> · Wadah Akses Digital Unit Hunian BITC</span>
            <span>&copy; {{ now()->year }} WADUH · BITC Cimahi</span>
        </div>
    </footer>
    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script>
        // Pop-up flash message — semua pakai modal penuh (bukan toast kecil di pojok).
        @if (session('success'))
            Swal.fire({ icon: 'success', title: 'Berhasil', text: @json(session('success')), confirmButtonColor: '#176b87', confirmButtonText: 'Tutup' });
        @endif
        @if (session('error'))
            Swal.fire({ icon: 'error', title: 'Terjadi Kesalahan', text: @json(session('error')), confirmButtonColor: '#176b87', confirmButtonText: 'Mengerti' });
        @endif
        @if (session('checkout'))
            Swal.fire({
                icon: 'success',
                title: 'Reservasi Berhasil Dikirim',
                html: 'Kode reservasi Anda:<br>'
                    + '<div style="display:flex;align-items:center;justify-content:center;gap:.5rem;flex-wrap:wrap;margin-top:.3rem">'
                    + '<strong style="font-size:1.6rem;color:#176b87;letter-spacing:.08em">{{ session('checkout')['kode_transaksi'] ?? '' }}</strong>'
                    + '<button type="button" class="btn btn-sm btn-brand-outline" data-salin="{{ session('checkout')['kode_transaksi'] ?? '' }}"><i class="bi bi-clipboard me-1"></i>Salin</button>'
                    + '</div>'
                    + '<small class="text-muted">Simpan kode ini untuk memantau status reservasi Anda.</small>',
                confirmButtonColor: '#176b87',
                confirmButtonText: 'Mengerti',
            });
        @endif

        // Tombol salin kode reservasi — umpan balik langsung di tombol, tanpa ketergantungan pustaka.
        // navigator.clipboard hanya ada di secure context (HTTPS/localhost); di HTTP biasa
        // (mis. domain .test lokal) ia undefined dan memanggilnya langsung akan melempar error
        // sinkron yang menghentikan handler sebelum sempat kasih umpan balik apa pun. Makanya
        // dicek dulu, dengan fallback textarea+execCommand untuk browser/konteks yang tidak
        // punya Clipboard API.
        document.addEventListener('click', e => {
            const btn = e.target.closest('[data-salin]');
            if (!btn) return;
            const teks = btn.dataset.salin || '';

            const sukses = () => {
                const asli = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Tersalin';
                btn.disabled = true;
                setTimeout(() => { btn.innerHTML = asli; btn.disabled = false; }, 1800);
            };
            const gagal = () => {
                const asli = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-x-lg me-1"></i>Gagal menyalin';
                setTimeout(() => { btn.innerHTML = asli; }, 1800);
            };
            const salinFallback = () => {
                const ta = document.createElement('textarea');
                ta.value = teks;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.focus();
                ta.select();
                let ok = false;
                try { ok = document.execCommand('copy'); } catch (err) { ok = false; }
                document.body.removeChild(ta);
                ok ? sukses() : gagal();
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(teks).then(sukses).catch(salinFallback);
            } else {
                salinFallback();
            }
        });

        // ===== Validasi sisi klien (menggantikan balon bawaan browser) =====
        // Satu pesan per kolom, selalu DI BAWAH kolomnya. Pembungkus yang dikenali:
        //   .fl-field (label mengambang)  → pesan disisipkan setelah pembungkus, bukan di antara
        //                                   <input> dan <label> (selector "input + label" tetap utuh);
        //   .aj-field (form Atur Jadwal)  → pesan disisipkan tepat setelah kolom di dalam pembungkus;
        //   .input-group / pemilih jam    → pesan disisipkan setelah pembungkus terluarnya.
        const labelDari = el => {
            if (el.dataset.label) return el.dataset.label;
            const wadah = el.closest('.fl-field, .aj-field, .mb-3, .mb-2, [class*="col-"]') || el.parentElement;
            const lbl = wadah?.querySelector('.fl-label-txt, .aj-sublabel, .form-label, .aj-section-label');
            const teks = lbl ? lbl.textContent.replace(/\(.*?\)/g, '').replace('*', '').replace(/\s+/g, ' ').trim() : '';
            return teks || 'Kolom ini';
        };
        const pesanSalah = el => {
            const v = el.validity;
            const nama = labelDari(el);
            if (v.customError) return el.validationMessage;
            if (v.valueMissing) {
                if (el.type === 'file') return 'Dokumen wajib dilampirkan.';
                if (el.tagName === 'SELECT' || el.type === 'hidden' || el.type === 'date') return nama + ' wajib dipilih.';
                return nama + ' wajib diisi.';
            }
            if (v.typeMismatch && el.type === 'email') return 'Format email tidak valid. Contoh: nama@email.com.';
            if (v.patternMismatch && el.type === 'tel') return nama + ' hanya boleh berisi angka (8–20 digit).';
            if (v.rangeUnderflow) return el.dataset.pesanMin || (nama + ' minimal ' + el.min + '.');
            if (v.rangeOverflow) return el.dataset.pesanMaks || (nama + ' maksimal ' + el.max + '.');
            if (v.tooShort) return nama + ' minimal ' + el.minLength + ' karakter.';
            if (v.tooLong) return nama + ' maksimal ' + el.maxLength + ' karakter.';
            if ((v.badInput || v.stepMismatch) && el.type === 'number') return nama + ' harus berupa angka bulat.';
            return nama + ' tidak sesuai format.';
        };
        // Input tersembunyi (mis. pemilih jam) → yang ditandai adalah tombol yang terlihat.
        const wakilTerlihat = el => el.type === 'hidden' ? (el.closest('[data-jampicker]')?.querySelector('.jam-btn') || el) : el;
        const induk = el => el.closest('.fl-field, .input-group, .pf-input-group, .pw-input-group, [data-jampicker]') || el;
        // Buang pesan lama milik kolom ini (pesan klien MAUPUN pesan server) supaya tidak dobel.
        const hapusPesan = wadah => {
            let n = wadah.nextElementSibling;
            while (n && n.matches('.catatan-salah, .fl-err, .aj-field-err')) {
                const berikut = n.nextElementSibling;
                n.remove();
                n = berikut;
            }
        };
        const tandai = (el, goyang = true) => {
            const target = wakilTerlihat(el);
            if (! target.parentElement) return;
            target.classList.add('is-salah');
            target.classList.toggle('tanpa-goyang', ! goyang);
            const wadah = induk(target);
            hapusPesan(wadah);
            const note = document.createElement('div');
            note.className = 'catatan-salah';
            note.setAttribute('role', 'alert');
            note.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i>';
            note.appendChild(document.createElement('span')).textContent = pesanSalah(el);
            wadah.insertAdjacentElement('afterend', note);
        };
        const bersihkan = el => {
            if (! el || ! el.classList || ! el.parentElement) return;
            const target = wakilTerlihat(el);
            target.classList.remove('is-salah', 'tanpa-goyang', 'is-invalid');
            const wadah = induk(target);
            wadah.classList.remove('is-invalid');
            hapusPesan(wadah);
        };
        // Dipakai skrip halaman (mis. validasi langsung kata sandi) agar memakai tampilan yang sama.
        window.WaduhValidasi = { tandai, bersihkan };

        document.querySelectorAll('form').forEach(f => {
            f.setAttribute('novalidate', '');
            f.addEventListener('submit', e => {
                const salah = [...f.querySelectorAll('input, select, textarea')].filter(el => ! el.disabled && ! el.checkValidity());
                if (! salah.length) return;
                e.preventDefault();
                e.stopImmediatePropagation(); // jangan lanjut ke dialog konfirmasi
                salah.forEach(el => tandai(el));
                const pertama = wakilTerlihat(salah[0]);
                pertama.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => pertama.focus({ preventScroll: true }), 350);
            }, true);
            f.addEventListener('input', e => bersihkan(e.target), true);
            f.addEventListener('change', e => bersihkan(e.target), true);
        });

        // Kolom nomor telepon/WhatsApp (input[type=tel]) — hanya menerima angka dan tanda "+" di depan.
        document.addEventListener('input', (e) => {
            if (e.target.tagName !== 'INPUT' || e.target.type !== 'tel') return;
            const plus = e.target.value.startsWith('+') ? '+' : '';
            const angka = e.target.value.replace(/[^0-9]/g, '');
            e.target.value = plus + angka;
        });

        // Dialog konfirmasi untuk form/tautan ber-atribut data-confirm — didelegasikan ke document
        // supaya berlaku juga untuk konten yang disisipkan belakangan lewat AJAX.
        // Bila dialog muncul di atas modal Bootstrap yang sedang terbuka, penjaga fokus modal
        // dimatikan sementara: tanpa itu fokus direbut kembali ke modal, sehingga menekan Enter
        // pada dialog justru menutup modal dan form tidak terkirim.
        const dialogKonfirmasi = sumber => {
            const modal = document.querySelector('.modal.show');
            const penjaga = modal && window.bootstrap ? bootstrap.Modal.getInstance(modal)?._focustrap : null;
            penjaga?.deactivate();
            return Swal.fire({
                title: sumber.dataset.confirmTitle || 'Konfirmasi',
                text: sumber.dataset.confirm,
                icon: sumber.dataset.icon || 'question',
                showCancelButton: true,
                confirmButtonText: sumber.dataset.confirmText || 'Ya, lanjutkan',
                cancelButtonText: 'Batal',
                confirmButtonColor: sumber.dataset.confirmColor || '#176b87',
                cancelButtonColor: '#94a3b8',
                reverseButtons: true,
            }).then(r => {
                if (! r.isConfirmed) penjaga?.activate();
                return r;
            });
        };

        document.addEventListener('submit', e => {
            const f = e.target.closest('form[data-confirm]');
            if (!f || f.dataset.confirmed) return;
            e.preventDefault();
            dialogKonfirmasi(f).then(r => {
                if (! r.isConfirmed) return;
                f.dataset.confirmed = 1;
                f.submit();
            });
        });

        document.addEventListener('click', e => {
            const a = e.target.closest('a[data-confirm]');
            if (!a) return;
            e.preventDefault();
            dialogKonfirmasi(a).then(r => { if (r.isConfirmed) window.location.href = a.href; });
        });
    </script>
</body>
</html>