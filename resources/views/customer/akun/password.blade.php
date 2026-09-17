@extends('layouts.customer')
@section('title', 'Ubah Kata Sandi')

@section('content')
    <div class="page-head mb-4" data-reveal>
        <p class="eyebrow-sm mb-2">Akun</p>
        <h1 class="h3 mb-1">Ubah Kata Sandi</h1>
        <p class="text-muted mb-0 lead">Kelola informasi akun dan perbarui kata sandi Anda secara berkala.</p>
    </div>

    @include('customer.akun.partials.hero', ['active' => 'password', 'pemesan' => auth('customer')->user()])

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
                            <p class="pw-modal-sub">Pastikan menggunakan minimal 8 karakter kombinasi.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <form method="POST" action="{{ route('customer.akun.password.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="pw-modal-body">
                        <div class="mb-3">
                            <label class="pw-modal-lbl">Kata Sandi Lama</label>
                            <div class="pw-input-group @error('password_lama') is-invalid @enderror" data-pw-toggle>
                                <span class="pw-input-ic"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password_lama" class="pw-input"
                                       placeholder="Masukkan sandi aktif saat ini" required>
                                <button type="button" class="pw-eye-btn" data-pw-target>
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('password_lama') <div class="pw-field-err">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="pw-modal-lbl">
                                Kata Sandi Baru <span class="pw-hint">Minimal 8 karakter</span>
                            </label>
                            <div class="pw-input-group @error('password') is-invalid @enderror" data-pw-toggle>
                                <span class="pw-input-ic"><i class="bi bi-shield-lock"></i></span>
                                <input type="password" name="password" class="pw-input"
                                       minlength="8" placeholder="Minimal 8 karakter, campur huruf & angka" required>
                                <button type="button" class="pw-eye-btn" data-pw-target>
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('password') <div class="pw-field-err">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="pw-modal-lbl">Konfirmasi Kata Sandi Baru</label>
                            <div class="pw-input-group" data-pw-toggle>
                                <span class="pw-input-ic"><i class="bi bi-shield-check"></i></span>
                                <input type="password" name="password_confirmation" class="pw-input"
                                       minlength="8" placeholder="Ulangi kata sandi baru" required>
                                <button type="button" class="pw-eye-btn" data-pw-target>
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="pw-tips">
                            <p class="pw-tips-title"><i class="bi bi-info-circle-fill"></i>Persyaratan Keamanan</p>
                            <ul>
                                <li><i class="bi bi-check2 text-success"></i>Minimal 8 karakter</li>
                                <li><i class="bi bi-check2 text-success"></i>Kombinasikan huruf besar &amp; angka</li>
                                <li><i class="bi bi-check2 text-success"></i>Hindari sandi yang mudah ditebak</li>
                            </ul>
                        </div>
                    </div>
                    <div class="pw-modal-foot">
                        <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-brand">
                            <i class="bi bi-shield-check me-1"></i>Perbarui Kata Sandi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Ditunggu sampai DOMContentLoaded karena script ini dirender sebelum
         <script src="bootstrap.bundle.min.js"> di layout. --}}
    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                new bootstrap.Modal(document.getElementById('modalUbahPassword')).show();
            });
        </script>
    @endif

    <style>
        /* ══════ MODAL PASSWORD ══════ */
        .pw-modal { border:1px solid var(--line); border-radius:1.4rem; overflow:hidden;
            box-shadow:0 25px 50px -12px rgba(15,23,42,.25); }
        .pw-modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem;
            padding:1.35rem 1.75rem; border-bottom:1px solid var(--line-soft);
            background:linear-gradient(135deg, var(--surface), #fff); }
        .pw-modal-head-left { display:flex; align-items:flex-start; gap:.85rem; min-width:0; }
        .pw-modal-icon { display:grid; place-items:center; width:2.75rem; height:2.75rem; border-radius:.85rem;
            background:linear-gradient(135deg, var(--primary-dark), var(--primary));
            color:#fff; font-size:1.15rem; flex:none;
            box-shadow:0 8px 18px -4px rgba(23,107,135,.45); }
        .pw-modal-title { font-size:1.05rem; font-weight:800; color:var(--ink); margin:0;
            display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
        .pw-modal-tag { font-size:.6rem; font-weight:800; letter-spacing:.06em;
            background:var(--emerald-soft); color:#047857;
            padding:.15rem .5rem; border-radius:.35rem; border:1px solid #a7f3d0; }
        .pw-modal-sub { font-size:.78rem; color:var(--muted); margin:.2rem 0 0; }
        .pw-modal-head .btn-close { flex:none; margin-top:.2rem; }

        .pw-modal-body { padding:1.5rem 1.75rem; }
        .pw-modal-lbl { display:flex; align-items:center; justify-content:space-between; gap:.5rem;
            font-size:.7rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase;
            color:#334155; margin-bottom:.5rem; }
        .pw-hint { font-size:.65rem; font-weight:600; color:var(--soft); text-transform:none; letter-spacing:0; }

        .pw-input-group { display:flex; align-items:center; border:1px solid var(--line);
            border-radius:.85rem; background:#fff; overflow:hidden;
            transition:border-color .15s ease, box-shadow .15s ease; }
        .pw-input-group:focus-within { border-color:var(--primary); box-shadow:0 0 0 3px rgba(23,107,135,.12); }
        .pw-input-group.is-invalid { border-color:var(--rose); }
        .pw-field-err { font-size:.75rem; font-weight:600; color:var(--rose); margin-top:.35rem; }
        .pw-input-ic { display:grid; place-items:center; width:2.6rem; height:2.6rem; color:var(--primary);
            border-right:1px solid var(--line-soft); background:var(--surface); flex:none; font-size:.9rem; }
        .pw-input { flex:1; border:0; padding:.7rem .95rem; font-size:.88rem; font-weight:500;
            color:var(--ink); background:transparent; outline:none; min-width:0; }
        /* Sembunyikan tombol reveal-password bawaan Edge/IE — sudah ada toggle custom (.pw-eye-btn),
           tanpa ini keduanya tampil bertumpuk jadi terlihat seperti 2 ikon mata. */
        .pw-input::-ms-reveal, .pw-input::-ms-clear { display:none; }
        .pw-eye-btn { display:grid; place-items:center; width:2.6rem; height:2.6rem; background:none; border:0;
            color:var(--muted); cursor:pointer; transition:color .15s ease; flex:none; }
        .pw-eye-btn:hover { color:var(--primary-dark); }

        .pw-tips { padding:.95rem 1.1rem; background:linear-gradient(135deg, var(--surface), var(--primary-soft));
            border:1px solid var(--primary-softer); border-radius:.85rem; }
        .pw-tips-title { font-size:.7rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase;
            color:var(--primary-dark); margin:0 0 .5rem; display:flex; align-items:center; gap:.4rem; }
        .pw-tips ul { list-style:none; margin:0; padding:0; font-size:.75rem; color:var(--muted); }
        .pw-tips li { padding:.15rem 0; display:flex; align-items:center; gap:.4rem; }
        .pw-tips li i.text-success { color:var(--emerald) !important; font-size:.9em; }

        .pw-modal-foot { display:flex; align-items:center; justify-content:flex-end; gap:.65rem;
            padding:1.15rem 1.75rem; border-top:1px solid var(--line-soft); background:var(--surface); }

        @media (max-width: 575.98px) {
            .pw-modal-head, .pw-modal-body, .pw-modal-foot { padding-left:1.15rem; padding-right:1.15rem; }
        }
    </style>

    <script>
        // Toggle eye untuk semua password input di modal
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