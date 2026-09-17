@extends('admin.layouts.app')
@section('title', 'Profil')

@section('content')
    <style>
        /* ===== Kartu hero — persis pola .pf-hero milik Pemesan (customer/akun/partials/hero.blade.php) ===== */
        .pf-hero { overflow:hidden; border:1px solid var(--line); }
        .pf-hero-banner { height:9rem; position:relative; overflow:hidden;
            background:linear-gradient(135deg,#0c3648 0%,#0f526b 45%,#176b87 100%); }
        .pf-hero-banner::before { content:''; position:absolute; inset:0;
            background-image:radial-gradient(rgba(255,255,255,.16) 1.5px, transparent 1.5px);
            background-size:22px 22px; opacity:.75; }
        .pf-hero-banner::after { content:''; position:absolute; inset:0;
            background:
                radial-gradient(28rem 14rem at 92% -30%, rgba(36,170,154,.35), transparent 55%),
                radial-gradient(20rem 10rem at 10% 120%, rgba(255,255,255,.15), transparent 60%);
            pointer-events:none; }

        .pf-hero-actions { position:absolute; top:1.1rem; right:1.25rem; display:flex; gap:.55rem; z-index:2; }
        .pf-pill { display:inline-flex; align-items:center; gap:.4rem; border:0; border-radius:.85rem;
            padding:.55rem 1rem; font-size:.78rem; font-weight:700; text-decoration:none;
            transition:transform .15s ease, box-shadow .15s ease; cursor:pointer; }
        .pf-pill:hover { transform:translateY(-1px); }
        .pf-pill-light { background:#fff; color:var(--ink); box-shadow:0 4px 12px rgba(15,23,42,.18); }
        .pf-pill-light:hover { background:var(--primary-soft); color:var(--primary-dark); }
        .pf-pill-dark { background:rgba(15,23,42,.35); color:#fff; backdrop-filter:blur(10px);
            border:1px solid rgba(255,255,255,.25); box-shadow:0 4px 12px rgba(0,0,0,.2); }
        .pf-pill-dark:hover { background:rgba(15,23,42,.55); color:#fff; }
        .pf-pill i { font-size:.85rem; }

        .pf-hero-body { padding:0 1.75rem 1.75rem; position:relative; }
        .pf-identity { display:flex; align-items:flex-end; gap:1.5rem; flex-wrap:wrap;
            margin-top:-3.75rem; margin-bottom:1.5rem; }
        @media (max-width: 767.98px) { .pf-identity { margin-top:-3rem; } }

        .pf-avatar-box { position:relative; flex:none; align-self:flex-end; }
        .pf-avatar { width:7rem; height:7rem; border-radius:50%; display:grid; place-items:center; overflow:hidden;
            background:linear-gradient(135deg,var(--primary),var(--accent-light));
            color:#fff; font-weight:800; font-size:2.4rem; font-family:'Plus Jakarta Sans',sans-serif;
            box-shadow:0 14px 28px -6px rgba(15,23,42,.35); border:4px solid #fff; }
        .pf-avatar img { width:100%; height:100%; object-fit:cover; }
        .pf-avatar-dot { position:absolute; bottom:.5rem; right:.5rem; width:1.15rem; height:1.15rem;
            background:var(--emerald); border-radius:50%; border:3px solid #fff;
            box-shadow:0 2px 4px rgba(0,0,0,.2); }

        .pf-name-block { min-width:0; flex:1 1 16rem; padding-bottom:.35rem; }
        .pf-member-id { display:inline-flex; align-items:center; gap:.3rem; font-size:.65rem;
            font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:var(--primary-dark);
            background:#fff; padding:.25rem .6rem; border-radius:9999px; margin-bottom:.5rem;
            box-shadow:0 2px 8px -2px rgba(15,23,42,.25); }
        .pf-name { font-weight:800; font-size:1.7rem; color:var(--ink); margin:0 0 .6rem;
            letter-spacing:-.02em; line-height:1.2; word-break:break-word;
            background:linear-gradient(135deg, var(--ink), var(--primary-dark));
            -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent; }
        .pf-meta-row { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; }
        .pf-role-badge { display:inline-flex; align-items:center; gap:.35rem;
            background:linear-gradient(135deg, var(--primary-soft), var(--primary-tint));
            color:var(--primary-dark); font-size:.72rem; font-weight:800; padding:.32rem .75rem;
            border-radius:.5rem; border:1px solid var(--primary-softer); }
        .pf-since { display:inline-flex; align-items:center; gap:.35rem; background:var(--surface); color:var(--muted);
            font-size:.72rem; font-weight:600; padding:.32rem .75rem; border-radius:.5rem; border:1px solid var(--line); }

        .pf-divider { border:0; border-top:1px dashed var(--line); margin:1.5rem 0; }

        .pf-info-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(15rem, 1fr)); gap:.85rem; }
        .pf-info-card { display:flex; align-items:flex-start; gap:.85rem; padding:1rem 1.1rem;
            background:linear-gradient(135deg, #fff, var(--surface));
            border:1px solid var(--line); border-radius:1rem;
            transition:border-color .18s ease, box-shadow .18s ease, transform .18s ease; }
        .pf-info-card:hover { border-color:var(--primary-softer); box-shadow:0 8px 20px -8px rgba(15,60,73,.12); transform:translateY(-2px); }
        .pf-info-ic { display:grid; place-items:center; width:2.6rem; height:2.6rem; border-radius:.75rem;
            background:linear-gradient(135deg, var(--primary-soft), var(--primary-tint));
            border:1px solid var(--primary-softer); color:var(--primary-dark); font-size:1rem; flex:none; }
        .pf-info-body { min-width:0; flex:1; }
        .pf-info-lbl { display:block; font-size:.62rem; font-weight:800; letter-spacing:.1em;
            text-transform:uppercase; color:var(--soft); margin-bottom:.2rem; }
        .pf-info-val { font-size:.88rem; font-weight:700; color:var(--ink); overflow-wrap:anywhere; line-height:1.4; }
        .pf-info-val.pf-info-empty { color:var(--muted); font-style:italic; font-weight:500; }
        .pf-info-full { grid-column:1 / -1; }

        /* ===== Modal Edit Profil / Ubah Kata Sandi — dibuka lewat tombol pill di atas ===== */
        .pf-modal, .pw-modal { border:1px solid var(--line); border-radius:1.4rem; overflow:hidden;
            box-shadow:0 25px 50px -12px rgba(21,36,59,.25); }
        .pf-modal-head, .pw-modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem;
            padding:1.35rem 1.75rem; border-bottom:1px solid var(--line);
            background:linear-gradient(135deg, var(--surface), #fff); }
        .pf-modal-head-left, .pw-modal-head-left { display:flex; align-items:flex-start; gap:.85rem; min-width:0; }
        .pf-modal-icon, .pw-modal-icon { display:grid; place-items:center; width:2.75rem; height:2.75rem; border-radius:.85rem;
            background:linear-gradient(135deg, var(--primary-dark), var(--primary));
            color:#fff; font-size:1.15rem; flex:none;
            box-shadow:0 8px 18px -4px rgba(23,107,135,.45); }
        .pf-modal-title, .pw-modal-title { font-size:1.05rem; font-weight:800; color:var(--ink); margin:0;
            display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
        .pf-modal-tag { font-size:.6rem; font-weight:800; letter-spacing:.06em;
            background:var(--primary-soft); color:var(--primary-dark);
            padding:.15rem .5rem; border-radius:.35rem; border:1px solid var(--primary-softer); }
        .pw-modal-tag { font-size:.6rem; font-weight:800; letter-spacing:.06em;
            background:var(--emerald-soft); color:#047857;
            padding:.15rem .5rem; border-radius:.35rem; border:1px solid #a7f3d0; }
        .pf-modal-sub, .pw-modal-sub { font-size:.78rem; color:var(--muted); margin:.2rem 0 0; }
        .pf-modal-head .btn-close, .pw-modal-head .btn-close { flex:none; margin-top:.2rem; }

        .pf-modal-body, .pw-modal-body { padding:1.5rem 1.75rem; }
        .pf-modal-lbl, .pw-modal-lbl { display:flex; align-items:center; justify-content:space-between; gap:.5rem;
            font-size:.7rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase;
            color:#334155; margin-bottom:.5rem; }
        .pw-hint { font-size:.65rem; font-weight:600; color:var(--muted); text-transform:none; letter-spacing:0; }

        .pf-input-group { display:flex; align-items:stretch; border:1px solid var(--line);
            border-radius:.85rem; background:#fff; overflow:hidden;
            transition:border-color .15s ease, box-shadow .15s ease; }
        .pf-input-group:focus-within { border-color:var(--primary); box-shadow:0 0 0 3px rgba(23,107,135,.12); }
        .pf-input-group.is-invalid { border-color:var(--rose); }
        .pf-field-err { font-size:.75rem; font-weight:600; color:var(--rose); margin-top:.35rem; }
        .pf-input-ic { display:grid; place-items:center; width:2.6rem; color:var(--primary);
            border-right:1px solid var(--line); background:var(--surface); flex:none; font-size:.9rem; }
        .pf-input { flex:1; border:0; padding:.7rem .95rem; font-size:.88rem; font-weight:600;
            color:var(--ink); background:transparent; outline:none; resize:vertical; min-width:0; }

        .pw-input-group { display:flex; align-items:center; border:1px solid var(--line);
            border-radius:.85rem; background:#fff; overflow:hidden;
            transition:border-color .15s ease, box-shadow .15s ease; }
        .pw-input-group:focus-within { border-color:var(--primary); box-shadow:0 0 0 3px rgba(23,107,135,.12); }
        .pw-input-group.is-invalid { border-color:var(--rose); }
        .pw-field-err { font-size:.75rem; font-weight:600; color:var(--rose); margin-top:.35rem; }
        .pw-input-ic { display:grid; place-items:center; width:2.6rem; height:2.6rem; color:var(--primary);
            border-right:1px solid var(--line); background:var(--surface); flex:none; font-size:.9rem; }
        .pw-input { flex:1; border:0; padding:.7rem .95rem; font-size:.88rem; font-weight:500;
            color:var(--ink); background:transparent; outline:none; min-width:0; }
        .pw-input::-ms-reveal, .pw-input::-ms-clear { display:none; }
        .pw-eye-btn { display:grid; place-items:center; width:2.6rem; height:2.6rem; background:none; border:0;
            color:var(--muted); cursor:pointer; transition:color .15s ease; flex:none; }
        .pw-eye-btn:hover { color:var(--primary-dark); }

        .pf-modal-foot, .pw-modal-foot { display:flex; align-items:center; justify-content:flex-end; gap:.65rem;
            padding:1.15rem 1.75rem; border-top:1px solid var(--line); background:var(--surface); }

        @media (max-width: 575.98px) {
            .pf-modal-head, .pf-modal-body, .pf-modal-foot,
            .pw-modal-head, .pw-modal-body, .pw-modal-foot { padding-left:1.15rem; padding-right:1.15rem; }
        }
    </style>

    <div data-reveal>
        {{-- ===== Hero — identitas, kontak, dan aksi cepat (pola sama dengan hero Profil Pemesan) ===== --}}
        <div class="xcard pf-hero mb-3">
            <div class="pf-hero-banner">
                <div class="pf-hero-actions">
                    <button type="button" class="pf-pill pf-pill-dark" data-bs-toggle="modal" data-bs-target="#modalEditProfil">
                        <i class="bi bi-pencil-fill"></i>Edit Profil
                    </button>
                    <button type="button" class="pf-pill pf-pill-light" data-bs-toggle="modal" data-bs-target="#modalUbahPassword">
                        <i class="bi bi-shield-lock-fill"></i>Ubah Kata Sandi
                    </button>
                </div>
            </div>

            <div class="pf-hero-body">
                <div class="pf-identity">
                    <div class="pf-avatar-box">
                        <div class="pf-avatar">
                            @if ($me->fotoUrl())
                                <img src="{{ $me->fotoUrl() }}" alt="{{ $me->nama_admin }}">
                            @else
                                {{ strtoupper(substr($me->nama_admin, 0, 1)) }}
                            @endif
                        </div>
                        <span class="pf-avatar-dot" title="Aktif"></span>
                    </div>

                    <div class="pf-name-block">
                        <span class="pf-member-id"><i class="bi bi-patch-check"></i>BITC-A-{{ str_pad($me->id_admin, 5, '0', STR_PAD_LEFT) }}</span>
                        <h2 class="pf-name">{{ $me->nama_admin }}</h2>
                        <div class="pf-meta-row">
                            <span class="pf-role-badge"><i class="bi bi-patch-check-fill"></i>Administrator</span>
                            @if ($me->created_at)
                                <span class="pf-since"><i class="bi bi-calendar3"></i>Terdaftar sejak {{ $me->created_at->translatedFormat('d F Y') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <hr class="pf-divider">

                <div class="pf-info-grid">
                    <div class="pf-info-card pf-info-full">
                        <span class="pf-info-ic"><i class="bi bi-envelope-fill"></i></span>
                        <div class="pf-info-body">
                            <span class="pf-info-lbl">Email</span>
                            <div class="pf-info-val">{{ $me->email }}</div>
                        </div>
                    </div>
                    <div class="pf-info-card">
                        <span class="pf-info-ic"><i class="bi bi-whatsapp"></i></span>
                        <div class="pf-info-body">
                            <span class="pf-info-lbl">WhatsApp</span>
                            <div class="pf-info-val @unless($me->no_whatsapp) pf-info-empty @endunless">{{ $me->no_whatsapp ?: 'Belum diisi' }}</div>
                        </div>
                    </div>
                    <div class="pf-info-card pf-info-full">
                        <span class="pf-info-ic"><i class="bi bi-geo-alt-fill"></i></span>
                        <div class="pf-info-body">
                            <span class="pf-info-lbl">Alamat</span>
                            <div class="pf-info-val @unless($me->alamat) pf-info-empty @endunless">{{ $me->alamat ?: 'Belum diisi' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════ MODAL EDIT PROFIL ══════════════ --}}
    <div class="modal fade" id="modalEditProfil" tabindex="-1" aria-labelledby="modalEditProfilLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content pf-modal">
                <div class="pf-modal-head">
                    <div class="pf-modal-head-left">
                        <span class="pf-modal-icon"><i class="bi bi-person-lines-fill"></i></span>
                        <div>
                            <h5 class="pf-modal-title" id="modalEditProfilLabel">
                                Edit Profil <span class="pf-modal-tag">IDENTITAS</span>
                            </h5>
                            <p class="pf-modal-sub">Perbarui informasi identitas akun admin.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <form method="POST" action="{{ route('admin.profil.update') }}"
                      data-confirm="Nama dan email akun akan diperbarui." data-confirm-title="Simpan perubahan profil?"
                      data-icon="warning" data-confirm-text="Ya, simpan">
                    @csrf
                    @method('PUT')
                    <div class="pf-modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="pf-modal-lbl">Nama</label>
                                <div class="pf-input-group @error('nama_admin') is-invalid @enderror">
                                    <span class="pf-input-ic"><i class="bi bi-person"></i></span>
                                    <input type="text" name="nama_admin" class="pf-input" value="{{ old('nama_admin', $me->nama_admin) }}" required>
                                </div>
                                @error('nama_admin') <div class="pf-field-err">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="pf-modal-lbl">Email (dipakai untuk masuk)</label>
                                <div class="pf-input-group @error('email') is-invalid @enderror">
                                    <span class="pf-input-ic"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="pf-input" value="{{ old('email', $me->email) }}" required>
                                </div>
                                @error('email') <div class="pf-field-err">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="pf-modal-lbl">No. WhatsApp</label>
                                <div class="pf-input-group @error('no_whatsapp') is-invalid @enderror">
                                    <span class="pf-input-ic"><i class="bi bi-whatsapp"></i></span>
                                    <input type="tel" name="no_whatsapp" class="pf-input" inputmode="tel" placeholder="Contoh: +6281234567890" value="{{ old('no_whatsapp', $me->no_whatsapp) }}" required>
                                </div>
                                @error('no_whatsapp') <div class="pf-field-err">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="pf-modal-lbl">Alamat</label>
                                <div class="pf-input-group @error('alamat') is-invalid @enderror">
                                    <span class="pf-input-ic"><i class="bi bi-geo-alt"></i></span>
                                    <textarea name="alamat" class="pf-input" rows="1" required>{{ old('alamat', $me->alamat) }}</textarea>
                                </div>
                                @error('alamat') <div class="pf-field-err">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="pf-modal-foot">
                        <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-brand"><i class="bi bi-check2 me-1"></i>Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ══════════════ MODAL UBAH PASSWORD ══════════════ --}}
    <div class="modal fade" id="modalUbahPassword" tabindex="-1" aria-labelledby="modalUbahPasswordLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content pw-modal">
                <div class="pw-modal-head">
                    <div class="pw-modal-head-left">
                        <span class="pw-modal-icon"><i class="bi bi-shield-lock-fill"></i></span>
                        <div>
                            <h5 class="pw-modal-title" id="modalUbahPasswordLabel">
                                Ubah Kata Sandi <span class="pw-modal-tag">SECURITY</span>
                            </h5>
                            <p class="pw-modal-sub">Pastikan menggunakan minimal 8 karakter.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <form method="POST" action="{{ route('admin.profil.password') }}">
                    @csrf
                    @method('PUT')
                    <div class="pw-modal-body">
                        <div class="mb-3">
                            <label class="pw-modal-lbl">Kata Sandi Lama</label>
                            <div class="pw-input-group @error('password_lama') is-invalid @enderror" data-pw-toggle>
                                <span class="pw-input-ic"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password_lama" class="pw-input" placeholder="Masukkan sandi aktif saat ini" required>
                                <button type="button" class="pw-eye-btn" data-pw-target><i class="bi bi-eye"></i></button>
                            </div>
                            @error('password_lama') <div class="pw-field-err">{{ $message }}</div> @enderror
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label class="pw-modal-lbl">Kata Sandi Baru <span class="pw-hint">Min. 8 karakter</span></label>
                                <div class="pw-input-group @error('password_baru') is-invalid @enderror" data-pw-toggle>
                                    <span class="pw-input-ic"><i class="bi bi-shield-lock"></i></span>
                                    <input type="password" name="password_baru" class="pw-input" minlength="8" required>
                                    <button type="button" class="pw-eye-btn" data-pw-target><i class="bi bi-eye"></i></button>
                                </div>
                                @error('password_baru') <div class="pw-field-err">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="pw-modal-lbl">Konfirmasi Kata Sandi Baru</label>
                                <div class="pw-input-group" data-pw-toggle>
                                    <span class="pw-input-ic"><i class="bi bi-shield-check"></i></span>
                                    <input type="password" name="password_baru_confirmation" class="pw-input" minlength="8" required>
                                    <button type="button" class="pw-eye-btn" data-pw-target><i class="bi bi-eye"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="pw-modal-foot">
                        <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-brand"><i class="bi bi-shield-check me-1"></i>Ubah Kata Sandi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Buka otomatis modal terkait kalau baru saja submit dengan error dari form itu.
        // Ditunggu sampai DOMContentLoaded karena script ini letaknya SEBELUM
        // <script src="bootstrap.bundle.min.js"> di layout (dirender di tengah @yield('content')),
        // jadi objek `bootstrap` belum ada kalau dipanggil langsung di sini.
        @if ($errors->has('nama_admin') || $errors->has('email') || $errors->has('no_whatsapp') || $errors->has('alamat'))
            document.addEventListener('DOMContentLoaded', () => {
                new bootstrap.Modal(document.getElementById('modalEditProfil')).show();
            });
        @endif
        @if ($errors->has('password_lama') || $errors->has('password_baru'))
            document.addEventListener('DOMContentLoaded', () => {
                new bootstrap.Modal(document.getElementById('modalUbahPassword')).show();
            });
        @endif

        // Toggle eye untuk semua input password di modal Ubah Kata Sandi.
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-pw-target]');
            if (!btn) return;
            const wrap = btn.closest('[data-pw-toggle]');
            const input = wrap.querySelector('.pw-input');
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    </script>
@endsection