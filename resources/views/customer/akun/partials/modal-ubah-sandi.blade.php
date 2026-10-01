{{--
    Modal "Ubah Kata Sandi" + gaya modal profil — dipakai BERSAMA oleh halaman Profil Pemesan
    (customer/akun/profil) dan Profil Admin (admin/profil/index) supaya perilaku kedua sisi
    identik.
    Variabel:
      - $aksi      : URL tujuan form (PUT)
      - $kolomBaru : nama kolom kata sandi baru ('password' untuk Pemesan, 'password_baru' untuk Admin)
--}}
@php
    $kolomBaru = $kolomBaru ?? 'password';
    $kolomKonf = $kolomBaru.'_confirmation';
@endphp

<div class="modal fade" id="modalUbahPassword" tabindex="-1" data-bs-focus="false"
     aria-labelledby="modalUbahPasswordLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content pw-modal">
            <div class="pw-modal-head">
                <div class="pw-modal-head-left">
                    <span class="pw-modal-icon"><i class="bi bi-shield-lock-fill"></i></span>
                    <div>
                        <h5 class="pw-modal-title" id="modalUbahPasswordLabel">
                            Ubah Kata Sandi <span class="pw-modal-tag">KEAMANAN</span>
                        </h5>
                        <p class="pw-modal-sub">Masukkan kata sandi lama, lalu tentukan kata sandi baru.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <form method="POST" action="{{ $aksi }}" id="formUbahSandi"
                  data-confirm="Kata sandi akun Anda akan diganti dengan kata sandi baru."
                  data-confirm-title="Ubah kata sandi?" data-icon="question" data-confirm-text="Ya, ubah">
                @csrf
                @method('PUT')
                <div class="pw-modal-body">
                    <div class="mb-3">
                        <div class="fl-field fl-field-pw @error('password_lama') is-invalid @enderror" data-pw-toggle>
                            <input type="password" name="password_lama" id="flSandiLama" class="fl-input" placeholder=" "
                                   autocomplete="current-password" data-sandi="lama" required>
                            <label for="flSandiLama"><span class="fl-label-txt">Kata Sandi Lama</span></label>
                            <button type="button" class="fl-eye-btn" data-pw-target title="Tampilkan atau sembunyikan kata sandi" aria-label="Tampilkan atau sembunyikan kata sandi">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('password_lama') <div class="fl-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <div class="fl-field fl-field-pw @error($kolomBaru) is-invalid @enderror" data-pw-toggle>
                            <input type="password" name="{{ $kolomBaru }}" id="flSandiBaru" class="fl-input" placeholder=" "
                                   autocomplete="new-password" data-sandi="baru" required>
                            <label for="flSandiBaru"><span class="fl-label-txt">Kata Sandi Baru</span></label>
                            <button type="button" class="fl-eye-btn" data-pw-target title="Tampilkan atau sembunyikan kata sandi" aria-label="Tampilkan atau sembunyikan kata sandi">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error($kolomBaru) <div class="fl-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <div class="fl-field fl-field-pw @error($kolomKonf) is-invalid @enderror" data-pw-toggle>
                            <input type="password" name="{{ $kolomKonf }}" id="flSandiKonf" class="fl-input" placeholder=" "
                                   autocomplete="new-password" data-sandi="konfirmasi" required>
                            <label for="flSandiKonf"><span class="fl-label-txt">Konfirmasi Kata Sandi Baru</span></label>
                            <button type="button" class="fl-eye-btn" data-pw-target title="Tampilkan atau sembunyikan kata sandi" aria-label="Tampilkan atau sembunyikan kata sandi">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error($kolomKonf) <div class="fl-err">{{ $message }}</div> @enderror
                    </div>

                    <div class="pw-tips">
                        <p class="pw-tips-title"><i class="bi bi-info-circle-fill"></i>Ketentuan Kata Sandi</p>
                        <ul>
                            <li><i class="bi bi-check2"></i>Minimal 8 karakter</li>
                            <li><i class="bi bi-check2"></i>Mengandung huruf dan angka</li>
                            <li><i class="bi bi-check2"></i>Berbeda dari kata sandi lama</li>
                        </ul>
                    </div>
                </div>
                <div class="pw-modal-foot">
                    <button type="button" class="btn btn-brand-outline" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-brand">
                        <i class="bi bi-shield-check me-1"></i>Simpan Kata Sandi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* ══════ MODAL PROFIL — Edit Profil (.pf-*) & Ubah Kata Sandi (.pw-*) ══════ */
    .pf-modal, .pw-modal { border:1px solid var(--line); border-radius:1.25rem; overflow:hidden;
        box-shadow:0 25px 50px -12px rgba(15,23,42,.25); }
    .pf-modal-head, .pw-modal-head { display:flex; align-items:flex-start; justify-content:space-between;
        gap:1rem; padding:1.25rem 1.5rem; border-bottom:1px solid var(--line-soft); flex:none;
        background:linear-gradient(135deg, var(--surface), #fff); }
    .pf-modal-head-left, .pw-modal-head-left { display:flex; align-items:flex-start; gap:.85rem; min-width:0; }
    .pf-modal-icon, .pw-modal-icon { display:grid; place-items:center; width:2.5rem; height:2.5rem;
        border-radius:.75rem; background:linear-gradient(135deg, var(--primary-dark), var(--primary));
        color:#fff; font-size:1.1rem; flex:none; box-shadow:0 6px 14px -3px rgba(15,90,110,.45); }
    .pf-modal-title, .pw-modal-title { font-size:1.05rem; font-weight:800; color:var(--ink); margin:0;
        display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
    .pf-modal-tag, .pw-modal-tag { font-size:.58rem; font-weight:800; letter-spacing:.06em;
        padding:.15rem .5rem; border-radius:.35rem; }
    .pf-modal-tag { background:var(--primary-soft); color:var(--primary-dark); border:1px solid var(--primary-softer); }
    .pw-modal-tag { background:var(--emerald-soft); color:#047857; border:1px solid #a7f3d0; }
    .pf-modal-sub, .pw-modal-sub { font-size:.75rem; color:var(--muted); margin:.2rem 0 0; }

    /* Kepala & kaki modal tetap di tempat, HANYA isi form yang bergulir — tombol "Simpan"
       selalu terjangkau di layar pendek. (<form> membungkus isi+kaki, jadi ia sendiri yang
       harus menjadi kolom flex yang boleh menyusut.) */
    .pf-modal > form, .pw-modal > form { display:flex; flex-direction:column; flex:1 1 auto; min-height:0; }
    .pf-modal-body, .pw-modal-body { padding:1.35rem 1.5rem; flex:1 1 auto; min-height:0; overflow-y:auto; }
    .pf-modal-foot, .pw-modal-foot { display:flex; flex-wrap:wrap; align-items:center; justify-content:flex-end; gap:.55rem;
        padding:1rem 1.5rem; border-top:1px solid var(--line-soft); background:var(--surface); flex:none; }
    .fl-input::-ms-reveal, .fl-input::-ms-clear { display:none; }

    .pw-tips { padding:.85rem 1rem;
        background:linear-gradient(135deg, var(--surface), var(--primary-soft));
        border:1px solid var(--primary-softer); border-radius:.75rem; }
    .pw-tips-title { font-size:.68rem; font-weight:800; letter-spacing:.06em;
        text-transform:uppercase; color:var(--primary-dark);
        margin:0 0 .4rem; display:flex; align-items:center; gap:.4rem; }
    .pw-tips ul { list-style:none; margin:0; padding:0; font-size:.74rem; color:var(--muted); }
    .pw-tips li { padding:.15rem 0; display:flex; align-items:center; gap:.4rem; }
    .pw-tips li i { color:#10b981; font-size:.9em; }

    @media (max-width: 575.98px) {
        .pf-modal-head, .pf-modal-body, .pf-modal-foot,
        .pw-modal-head, .pw-modal-body, .pw-modal-foot { padding-left:1.1rem; padding-right:1.1rem; }
        .pf-modal-head, .pw-modal-head { padding-top:1rem; padding-bottom:1rem; }
        /* Tombol kaki modal selebar layar, tombol utama di atas. */
        .pf-modal-foot, .pw-modal-foot { flex-direction:column-reverse; align-items:stretch; }
        .pf-modal-foot .btn, .pw-modal-foot .btn { width:100%; }
    }
</style>

<script>
    // Tombol mata — tampilkan/sembunyikan isi kolom kata sandi.
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-pw-target]');
        if (! btn) return;
        const input = btn.closest('[data-pw-toggle]').querySelector('.fl-input');
        const icon = btn.querySelector('i');
        const tampil = input.type === 'password';
        input.type = tampil ? 'text' : 'password';
        icon.classList.toggle('bi-eye', ! tampil);
        icon.classList.toggle('bi-eye-slash', tampil);
    });

    // Validasi langsung kata sandi baru & konfirmasinya. Aturannya dipasang lewat
    // setCustomValidity(), sehingga pesan ditampilkan oleh validasi bawaan layout
    // (window.WaduhValidasi) — satu pesan per kolom, sama persis dengan kolom lain, dan
    // dialog konfirmasi baru muncul setelah seluruh isian benar.
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('modalUbahPassword');
        const form = document.getElementById('formUbahSandi');
        if (! modal || ! form) return;
        const lama = form.querySelector('[data-sandi="lama"]');
        const baru = form.querySelector('[data-sandi="baru"]');
        const konf = form.querySelector('[data-sandi="konfirmasi"]');

        const aturBaru = () => {
            const v = baru.value;
            let pesan = '';
            if (v && v.length < 8) pesan = 'Kata sandi baru minimal 8 karakter.';
            else if (v && (! /\p{L}/u.test(v) || ! /\d/.test(v))) pesan = 'Kata sandi baru harus mengandung huruf dan angka.';
            else if (v && v === lama.value) pesan = 'Kata sandi baru tidak boleh sama dengan kata sandi lama.';
            baru.setCustomValidity(pesan);
        };
        const aturKonf = () => {
            konf.setCustomValidity(konf.value && konf.value !== baru.value
                ? 'Konfirmasi kata sandi tidak sama dengan kata sandi baru.' : '');
        };
        const tampilkan = (el) => {
            const V = window.WaduhValidasi;
            if (! V) return;
            el.validity.customError ? V.tandai(el, false) : V.bersihkan(el);
        };

        baru.addEventListener('input', () => {
            aturBaru(); aturKonf();
            tampilkan(baru);
            if (konf.value) tampilkan(konf);
        });
        konf.addEventListener('input', () => { aturKonf(); tampilkan(konf); });
        lama.addEventListener('input', () => { aturBaru(); if (baru.value) tampilkan(baru); });

        // Modal ditutup → kosongkan isian & pesan, supaya saat dibuka lagi form kembali bersih.
        modal.addEventListener('hidden.bs.modal', () => {
            form.reset();
            [lama, baru, konf].forEach((el) => {
                el.setCustomValidity('');
                el.type = 'password';
                window.WaduhValidasi?.bersihkan(el);
            });
            form.querySelectorAll('[data-pw-target] i').forEach((i) => { i.classList.add('bi-eye'); i.classList.remove('bi-eye-slash'); });
        });
    });
</script>
