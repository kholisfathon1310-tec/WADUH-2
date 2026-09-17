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
            --teal:#24aa9a;
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
        .btn-cart { position:relative; color:#fff; border:none; border-radius:.85rem; font-weight:700; background:var(--primary); box-shadow:0 10px 22px -8px rgba(14,107,125,.5); transition:all .15s ease; }
        .btn-cart:hover { color:#fff; background:var(--primary-dark); transform:translateY(-1px); }
        .btn-cart .badge { position:absolute; top:-7px; right:-7px; background:#f59e0b; }

        /* Stepper — pill progress yang lebih hidup */
        /* ══════════════════════════════════════════════════════════════
           STEPPER — centered container dengan max-width, kompak & rapi.
           Berlaku di seluruh halaman yang menggunakan @section('stepper').
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
                <ul class="navbar-nav mx-auto gap-lg-1">
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ url('/') }}">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('reservasi.index') ? 'active' : '' }}" href="{{ route('reservasi.index') }}">Reservasi</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('fasilitas.*') ? 'active' : '' }}" href="{{ route('fasilitas.index') }}" role="button" data-bs-toggle="dropdown">Fasilitas</a>
                        <ul class="dropdown-menu shadow border-0" style="border-radius:1rem">
                            <li><a class="dropdown-item fw-semibold" href="{{ route('fasilitas.index') }}"><i class="bi bi-grid-3x3-gap me-2"></i>Semua Lantai</a></li>
                            <li><hr class="dropdown-divider"></li>
                            @php
                                $navLantai = \App\Models\Lantai::with(['fasilitas' => fn ($q) => $q->limit(1)])->orderBy('id_lantai')->get();
                            @endphp
                            @foreach ($navLantai as $nl)
                                @if ($nl->fasilitas->isNotEmpty())
                                    <li>
                                        <a class="dropdown-item fw-semibold" href="{{ route('fasilitas.denah', ['kategori' => $nl->fasilitas->first()->kategori_fasilitas, 'lantai' => $nl->id_lantai]) }}">
                                            <span class="d-inline-block rounded-circle me-2" style="width:.6rem;height:.6rem;background:var(--primary)"></span>
                                            Lantai {{ $nl->nomor_lantai }} · {{ $nl->fasilitas->first()->kategori_fasilitas }}
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('cek-status.*') ? 'active' : '' }}" href="{{ route('cek-status.form') }}">Cek Status</a></li>
                </ul>
                @php $cartN = app(\App\Services\CartService::class)->count(); @endphp
                <a href="{{ route('reservasi.checkout.form') }}" class="btn btn-cart btn-sm px-3 py-2">
                    <i class="bi bi-cart3 me-1"></i> Keranjang
                    @if ($cartN > 0)<span class="badge rounded-pill">{{ $cartN }}</span>@endif
                </a>
            </div>
        </div>
    </nav>
    </div>

    <main class="container py-4 py-md-5">
        {{-- Tombol kembali global --}}
        <button onclick="history.back()" class="btn btn-sm btn-brand-outline mb-3"><i class="bi bi-arrow-left me-1"></i>Kembali</button>

        @hasSection('stepper')
            <div class="stepper-wrap mb-4">@yield('stepper')</div>
        @endif

        @if ($errors->any())
            <div class="err-card mb-3">
                <span class="err-ic"><i class="bi bi-emoji-frown"></i></span>
                <div>
                    <div class="fw-bold" style="color:#a12c2c">Ups, ada {{ $errors->count() }} hal yang perlu diperbaiki</div>
                    <div class="small text-muted mb-1">Lengkapi dulu ya, biar reservasimu bisa diproses:</div>
                    <ul class="err-list">
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
                confirmButtonText: 'Siap, sudah kusimpan',
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
                btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Tersalin!';
                btn.disabled = true;
                setTimeout(() => { btn.innerHTML = asli; btn.disabled = false; }, 1800);
            };
            const gagal = () => {
                const asli = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-x-lg me-1"></i>Gagal, salin manual';
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

        // ===== Validasi klien yang ramah (mengganti bubble bawaan browser) =====
        const labelDari = el => {
            const wadah = el.closest('.mb-3, .mb-2, [class*="col-"]') || el.parentElement;
            const lbl = wadah?.querySelector('.form-label');
            return lbl ? lbl.textContent.replace('*', '').trim() : 'Kolom ini';
        };
        const pesanSalah = el => {
            const v = el.validity;
            if (v.valueMissing) {
                if (el.type === 'file') return 'Dokumen belum dilampirkan.';
                if (el.tagName === 'SELECT') return labelDari(el) + ' belum dipilih.';
                return labelDari(el) + ' belum diisi.';
            }
            if (v.typeMismatch && el.type === 'email') return 'Format email belum benar (contoh: nama@email.com).';
            if (v.rangeUnderflow) return labelDari(el) + ' minimal ' + el.min + '.';
            if (v.rangeOverflow) return labelDari(el) + ' maksimal ' + el.max + '.';
            if (v.tooLong) return labelDari(el) + ' terlalu panjang.';
            return labelDari(el) + ' belum sesuai format.';
        };
        // Input hidden (mis. pemilih jam custom) → tandai tombol/wadah yang terlihat.
        const wakilTerlihat = el => {
            if (el.type === 'hidden') return el.closest('[data-jampicker]')?.querySelector('.jam-btn') || el;
            return el;
        };
        const tandai = el => {
            const target = wakilTerlihat(el);
            target.classList.add('is-salah');
            const induk = target.closest('.input-group') || target.closest('[data-jampicker]') || target;
            induk.parentElement.querySelector(':scope > .catatan-salah')?.remove();
            const note = document.createElement('div');
            note.className = 'catatan-salah';
            note.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i>' + pesanSalah(el);
            induk.insertAdjacentElement('afterend', note);
        };
        const bersihkan = el => {
            const target = wakilTerlihat(el);
            target.classList.remove('is-salah');
            const induk = target.closest('.input-group') || target.closest('[data-jampicker]') || target;
            induk.parentElement.querySelector(':scope > .catatan-salah')?.remove();
        };
        document.querySelectorAll('form').forEach(f => {
            f.setAttribute('novalidate', '');
            f.addEventListener('submit', e => {
                const salah = [...f.querySelectorAll('input, select, textarea')].filter(el => ! el.disabled && ! el.checkValidity());
                if (! salah.length) return;
                e.preventDefault();
                e.stopImmediatePropagation(); // jangan lanjut ke dialog konfirmasi
                salah.forEach(tandai);
                const pertama = wakilTerlihat(salah[0]);
                pertama.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => pertama.focus({ preventScroll: true }), 350);
                Swal.fire({ icon: 'warning', title: 'Periksa kembali', text: salah.length + ' isian belum lengkap.', confirmButtonColor: '#176b87', confirmButtonText: 'Oke' });
            }, true);
            f.addEventListener('input', e => bersihkan(e.target), true);
            f.addEventListener('change', e => bersihkan(e.target), true);
        });
        @if ($errors->any())
            Swal.fire({ icon: 'warning', title: 'Periksa kembali', text: '{{ $errors->count() }} isian belum benar.', confirmButtonColor: '#176b87', confirmButtonText: 'Oke' });
        @endif

        // Dialog konfirmasi untuk form/tautan ber-atribut data-confirm — didelegasikan ke document
        // supaya otomatis berlaku juga untuk konten yang disisipkan belakangan lewat AJAX.
        document.addEventListener('submit', e => {
            const f = e.target.closest('form[data-confirm]');
            if (!f || f.dataset.confirmed) return;
            e.preventDefault();
            Swal.fire({
                title: f.dataset.confirmTitle || 'Yakin?',
                text: f.dataset.confirm,
                icon: f.dataset.icon || 'question',
                showCancelButton: true,
                confirmButtonText: f.dataset.confirmText || 'Ya, lanjutkan',
                cancelButtonText: 'Batal',
                confirmButtonColor: f.dataset.confirmColor || '#176b87',
                cancelButtonColor: '#8a97a5',
                reverseButtons: true,
            }).then(r => { if (r.isConfirmed) { f.dataset.confirmed = 1; f.submit(); } });
        });

        document.addEventListener('click', e => {
            const a = e.target.closest('a[data-confirm]');
            if (!a) return;
            e.preventDefault();
            Swal.fire({
                title: a.dataset.confirmTitle || 'Yakin?',
                text: a.dataset.confirm,
                icon: a.dataset.icon || 'question',
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