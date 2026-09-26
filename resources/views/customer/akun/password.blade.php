@extends('layouts.customer')
@section('title', 'Ubah Kata Sandi')

@php $pemesan = auth('customer')->user(); @endphp

@section('content')
    <div class="page-head mb-4" data-reveal>
        <p class="eyebrow-sm mb-2">Area Pemesan</p>
        <h1 class="h3 mb-1">Profil &amp; Keamanan</h1>
        <p class="text-muted mb-0 lead">Kelola informasi akun dan perbarui kata sandi Anda secara berkala.</p>
    </div>

    {{-- Hero card --}}
    @include('customer.akun.partials.hero', [
        'user' => $pemesan,
        'role' => 'Pemesan',
        'idPrefix' => 'BITC-P-',
        'idField' => 'id_pemesan',
        'active' => 'password',
        'routes' => [
            'profil'       => 'customer.akun.profil',
            'password'     => 'customer.akun.password',
            'modal_edit'   => 'modalEditProfil',
            'modal_password' => 'modalUbahPassword',
        ],
    ])

    {{-- Info cards ringkasan (sama seperti profil) --}}
    <div class="pf-info-grid" data-reveal>
        <div class="pf-info-card">
            <span class="pf-info-ic"><i class="bi bi-envelope-fill"></i></span>
            <div class="pf-info-body">
                <span class="pf-info-lbl">Email</span>
                <div class="pf-info-val">{{ $pemesan->email }}</div>
            </div>
        </div>
        <div class="pf-info-row">
            <div class="pf-info-card">
                <span class="pf-info-ic"><i class="bi bi-telephone-fill"></i></span>
                <div class="pf-info-body">
                    <span class="pf-info-lbl">No. Telepon</span>
                    <div class="pf-info-val">{{ $pemesan->no_telepon }}</div>
                </div>
            </div>
            <div class="pf-info-card">
                <span class="pf-info-ic"><i class="bi bi-shield-lock-fill"></i></span>
                <div class="pf-info-body">
                    <span class="pf-info-lbl">Kata Sandi</span>
                    <div class="pf-info-val">••••••••••</div>
                </div>
            </div>
            <div class="pf-info-card">
                <span class="pf-info-ic"><i class="bi bi-clock-history"></i></span>
                <div class="pf-info-body">
                    <span class="pf-info-lbl">Terakhir Diperbarui</span>
                    <div class="pf-info-val">{{ $pemesan->updated_at?->translatedFormat('d M Y') ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════ MODAL UBAH KATA SANDI ══════════════ --}}
    <div class="modal fade" id="modalUbahPassword" tabindex="-1" aria-labelledby="modalUbahPasswordLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content pw-modal">
                <div class="pw-modal-head">
                    <div class="pw-modal-head-left">
                        <span class="pw-modal-icon"><i class="bi bi-shield-lock-fill"></i></span>
                        <div>
                            <h5 class="pw-modal-title" id="modalUbahPasswordLabel">
                                Ubah Kata Sandi <span class="pw-modal-tag">KEAMANAN</span>
                            </h5>
                            <p class="pw-modal-sub">Gunakan minimal 8 karakter kombinasi huruf &amp; angka.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <form method="POST" action="{{ route('customer.akun.password.update') }}"
                      data-confirm="Anda akan keluar dari sesi lain setelah kata sandi diganti."
                      data-confirm-title="Ubah kata sandi?" data-icon="question" data-confirm-text="Ya, ubah">
                    @csrf
                    @method('PUT')
                    <div class="pw-modal-body">
                        <div class="mb-3">
                            <div class="fl-field fl-field-pw @error('password_lama') is-invalid @enderror" data-pw-toggle>
                                <input type="password" name="password_lama" id="flPasswordLamaP" class="fl-input" placeholder=" " autocomplete="off" readonly onfocus="this.removeAttribute('readonly')" required>
                                <label for="flPasswordLamaP"><span class="fl-label-txt">Kata Sandi Lama</span></label>
                                <button type="button" class="fl-eye-btn" data-pw-target data-tip="Tampilkan/sembunyikan">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('password_lama') <div class="fl-err">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <div class="fl-field fl-field-pw @error('password') is-invalid @enderror" data-pw-toggle>
                                <input type="password" name="password" id="flPasswordBaruP" class="fl-input" placeholder=" " minlength="8" autocomplete="off" readonly onfocus="this.removeAttribute('readonly')" required>
                                <label for="flPasswordBaruP"><span class="fl-label-txt">Kata Sandi Baru</span></label>
                                <button type="button" class="fl-eye-btn" data-pw-target data-tip="Tampilkan/sembunyikan">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('password') <div class="fl-err">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <div class="fl-field fl-field-pw" data-pw-toggle>
                                <input type="password" name="password_confirmation" id="flPasswordConfP" class="fl-input" placeholder=" " minlength="8" autocomplete="off" readonly onfocus="this.removeAttribute('readonly')" required>
                                <label for="flPasswordConfP"><span class="fl-label-txt">Konfirmasi Kata Sandi Baru</span></label>
                                <button type="button" class="fl-eye-btn" data-pw-target data-tip="Tampilkan/sembunyikan">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="pw-tips">
                            <p class="pw-tips-title"><i class="bi bi-info-circle-fill"></i>Persyaratan Keamanan</p>
                            <ul>
                                <li><i class="bi bi-check2"></i>Minimal 8 karakter</li>
                                <li><i class="bi bi-check2"></i>Kombinasi huruf besar &amp; angka</li>
                                <li><i class="bi bi-check2"></i>Hindari kata sandi yang mudah ditebak</li>
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

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                new bootstrap.Modal(document.getElementById('modalUbahPassword')).show();
            });
        </script>
    @endif

    <style>
        .pw-modal { border:1px solid var(--line); border-radius:1.25rem; overflow:hidden;
            box-shadow:0 25px 50px -12px rgba(15,23,42,.25); }
        .pw-modal-head { display:flex; align-items:flex-start; justify-content:space-between;
            gap:1rem; padding:1.25rem 1.5rem; border-bottom:1px solid var(--line-soft);
            background:linear-gradient(135deg, var(--surface), #fff); }
        .pw-modal-head-left { display:flex; align-items:flex-start; gap:.85rem; min-width:0; }
        .pw-modal-icon { display:grid; place-items:center; width:2.5rem; height:2.5rem;
            border-radius:.75rem;
            background:linear-gradient(135deg, var(--primary-dark), var(--primary));
            color:#fff; font-size:1.1rem; flex:none;
            box-shadow:0 6px 14px -3px rgba(15,118,110,.45); }
        .pw-modal-title { font-size:1.05rem; font-weight:800; color:var(--ink); margin:0;
            display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
        .pw-modal-tag { font-size:.58rem; font-weight:800; letter-spacing:.06em;
            background:var(--emerald-soft); color:#047857;
            padding:.15rem .5rem; border-radius:.35rem; border:1px solid #a7f3d0; }
        .pw-modal-sub { font-size:.75rem; color:var(--muted); margin:.2rem 0 0; }
        .pw-modal-body { padding:1.35rem 1.5rem; }
        .fl-input::-ms-reveal, .fl-input::-ms-clear { display:none; }
        .pw-tips { padding:.85rem 1rem;
            background:linear-gradient(135deg, var(--surface), var(--primary-soft));
            border:1px solid var(--primary-softer); border-radius:.75rem; }
        .pw-tips-title { font-size:.68rem; font-weight:800; letter-spacing:.06em;
            text-transform:uppercase; color:var(--primary-dark);
            margin:0 0 .4rem; display:flex; align-items:center; gap:.4rem; }
        .pw-tips ul { list-style:none; margin:0; padding:0; font-size:.72rem; color:var(--muted); }
        .pw-tips li { padding:.15rem 0; display:flex; align-items:center; gap:.4rem; }
        .pw-tips li i { color:#10b981; font-size:.9em; }
        .pw-modal-foot { display:flex; justify-content:flex-end; gap:.55rem;
            padding:1rem 1.5rem; border-top:1px solid var(--line-soft); background:var(--surface); }
    </style>

    <script>
        // Toggle mata untuk password input
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-pw-target]');
            if (!btn) return;
            const wrap = btn.closest('[data-pw-toggle]');
            const input = wrap.querySelector('.fl-input');
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });

        // ═══ VALIDASI LANGSUNG (bukan tunggu server) — kata sandi baru & konfirmasi dicek
        // begitu diketik/lepas fokus, pesan kesalahan tampil inline di bawah field yang sama
        // seperti error dari server (.fl-err), TANPA pop-up "Yakin?" dulu. Pop-up konfirmasi
        // (data-confirm di <form>) baru muncul kalau semua isian sudah valid. ═══
        (function () {
            const form = document.getElementById('modalUbahPassword')?.querySelector('form');
            if (!form) return;
            const baru = document.getElementById('flPasswordBaruP');
            const konf = document.getElementById('flPasswordConfP');

            const setError = (input, pesan) => {
                const wrap = input.closest('.fl-field');
                wrap.classList.add('is-invalid');
                let err = wrap.parentElement.querySelector('.fl-err[data-live]');
                if (!err) {
                    err = document.createElement('div');
                    err.className = 'fl-err';
                    err.setAttribute('data-live', '1');
                    wrap.insertAdjacentElement('afterend', err);
                }
                err.textContent = pesan;
            };
            const clearError = (input) => {
                const wrap = input.closest('.fl-field');
                wrap.classList.remove('is-invalid');
                wrap.parentElement.querySelector('.fl-err[data-live]')?.remove();
            };

            const cekBaru = () => {
                const v = baru.value;
                if (!v) { clearError(baru); return true; }
                if (v.length < 8) { setError(baru, 'Minimal 8 karakter.'); return false; }
                if (!/[A-Za-z]/.test(v) || !/[0-9]/.test(v)) { setError(baru, 'Wajib kombinasi huruf & angka.'); return false; }
                clearError(baru);
                return true;
            };
            const cekKonf = () => {
                if (!konf.value) { clearError(konf); return true; }
                if (konf.value !== baru.value) { setError(konf, 'Konfirmasi tidak sama dengan kata sandi baru.'); return false; }
                clearError(konf);
                return true;
            };

            baru.addEventListener('input', () => { cekBaru(); if (konf.value) cekKonf(); });
            konf.addEventListener('input', cekKonf);
            baru.addEventListener('blur', cekBaru);
            konf.addEventListener('blur', cekKonf);

            form.addEventListener('submit', (e) => {
                const okBaru = cekBaru();
                const okKonf = cekKonf();
                if (!okBaru || !okKonf) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    (okBaru ? konf : baru).focus();
                }
            });
        })();
    </script>
@endsection