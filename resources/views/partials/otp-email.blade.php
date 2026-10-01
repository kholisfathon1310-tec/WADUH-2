{{--
    Verifikasi email dengan kode OTP — halaman Daftar, Edit Profil pemesan, dan Edit Profil admin.

    Alur: tombol "Kirim OTP" (di dalam kolom email, lihat otp-email-tombol.blade.php) mengirim
    kode 6 angka ke email → kolom Kode OTP + tombol "Verifikasi" muncul di bawahnya → bila kode
    valid, kolom email diberi tanda centang "Terverifikasi" dan form boleh disimpan. Tanpa
    verifikasi, form tidak dapat dikirim (diperiksa juga di server).

    Variabel:
      $emailId         : id kolom email
      $url             : endpoint POST kirim kode
      $urlVerifikasi   : endpoint POST verifikasi kode
      $emailAwal       : (opsional) email saat ini — verifikasi hanya diperlukan bila email diubah
      $status          : (opsional) OtpEmailService::status() untuk melanjutkan hitung mundur
      $terverifikasi   : (opsional) email pada kolom sudah terverifikasi di session ini
--}}
<div class="otp" data-otp data-email-id="{{ $emailId }}" data-url="{{ $url }}" data-url-verifikasi="{{ $urlVerifikasi }}"
     data-email-awal="{{ $emailAwal ?? '' }}" data-berlaku="{{ $status['berlaku_detik'] ?? 0 }}" data-jeda="{{ $status['jeda_detik'] ?? 0 }}"
     data-terverifikasi="{{ ($terverifikasi ?? false) ? '1' : '0' }}">
    <div class="otp-panel" data-otp-panel hidden>
        <label class="otp-lbl" for="otpKode-{{ $emailId }}">Kode OTP</label>
        <div class="otp-row pw-input-group pw-wrap">
            <input type="text" id="otpKode-{{ $emailId }}" class="otp-input" data-otp-kode inputmode="numeric" maxlength="6"
                   autocomplete="one-time-code" placeholder="6 angka" aria-describedby="otpStatus-{{ $emailId }}">
            <button type="button" class="otp-verif" data-otp-verifikasi><i class="bi bi-shield-check"></i><span>Verifikasi</span></button>
        </div>
    </div>
    <div class="otp-status" id="otpStatus-{{ $emailId }}" data-otp-status role="status" hidden></div>
</div>

@once
<style>
    /* Tombol & tanda centang di dalam kolom email */
    .otp-field { position:relative; }
    .otp-field > input[type="email"] { padding-right:7.6rem !important; }
    .otp-field > input[type="email"].otp-ok { border-color:#10b981 !important; background:#f0fdf6 !important; }
    .otp-kirim { position:absolute; right:.4rem; top:50%; transform:translateY(-50%); z-index:3;
        display:inline-flex; align-items:center; gap:.35rem; height:2rem; padding:0 .75rem; border:0; border-radius:.55rem;
        background:#176b87; color:#fff; font-size:.76rem; font-weight:700; font-family:inherit; white-space:nowrap; cursor:pointer;
        transition:background .15s ease; }
    .otp-kirim:hover:not(:disabled) { background:#0f526b; }
    .otp-kirim:disabled { background:#e2e8f0; color:#64748b; cursor:not-allowed; }
    .otp-kirim[hidden], .otp-badge[hidden] { display:none; }
    .otp-badge { position:absolute; right:.55rem; top:50%; transform:translateY(-50%); z-index:3;
        display:inline-flex; align-items:center; gap:.3rem; padding:.25rem .6rem; border-radius:2rem;
        background:#d1fae5; color:#047857; font-size:.74rem; font-weight:800; pointer-events:none; }
    .otp-badge i { font-size:.9rem; }

    /* Kolom kode OTP + tombol Verifikasi */
    .otp { margin-top:.5rem; }
    .otp-panel { padding:.75rem .85rem; border:1px solid #c9e6ea; border-radius:.8rem; background:#f3f9fa; }
    .otp-panel[hidden] { display:none; }
    .otp-lbl { display:block; font-size:.76rem; font-weight:700; color:#334155; margin:0 0 .35rem; }
    .otp-row { display:flex; gap:.5rem; }
    .otp-input { flex:1; min-width:0; height:2.6rem; border:1px solid #d6e2ea; border-radius:.65rem; background:#fff;
        padding:0 .8rem; font-size:1.05rem; font-weight:800; letter-spacing:.3em; color:#0f172a; text-align:center; outline:none;
        font-family:inherit; transition:border-color .15s ease, box-shadow .15s ease; }
    .otp-input::placeholder { font-size:.82rem; font-weight:500; letter-spacing:0; color:#94a3b8; }
    .otp-input:focus { border-color:#176b87; box-shadow:0 0 0 3px rgba(23,107,135,.12); }
    .otp-input.salah { border-color:#e11d48; }
    .otp-verif { flex:none; display:inline-flex; align-items:center; justify-content:center; gap:.35rem; height:2.6rem; padding:0 1rem;
        border:0; border-radius:.65rem; background:#047857; color:#fff; font-size:.8rem; font-weight:700; font-family:inherit; cursor:pointer;
        transition:background .15s ease; }
    .otp-verif:hover:not(:disabled) { background:#065f46; }
    .otp-verif:disabled { background:#cbd5e1; color:#64748b; cursor:not-allowed; }
    .otp-status { display:flex; align-items:flex-start; gap:.35rem; margin-top:.45rem; font-size:.76rem; font-weight:600; line-height:1.45; color:#0f526b; }
    .otp-status[hidden] { display:none; }
    .otp-status.galat { color:#be123c; }
    .otp-status.berhasil { color:#047857; }
    .otp-status i { margin-top:.1rem; flex:none; }
    .otp-waktu { margin-left:auto; padding-left:.5rem; white-space:nowrap; color:#64748b; font-weight:700; }
    @media (max-width: 400px) {
        .otp-field > input[type="email"] { padding-right:6.4rem !important; }
        .otp-kirim { padding:0 .55rem; }
        .otp-kirim i { display:none; }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const fmt = (d) => String(Math.floor(d / 60)).padStart(2, '0') + ':' + String(d % 60).padStart(2, '0');
        const detikIni = () => Math.floor(Date.now() / 1000);

        document.querySelectorAll('[data-otp]').forEach((box) => {
            const id = box.dataset.emailId;
            const email = document.getElementById(id);
            const tombolKirim = document.querySelector('[data-otp-kirim="' + id + '"]');
            const badge = document.querySelector('[data-otp-badge="' + id + '"]');
            const panel = box.querySelector('[data-otp-panel]');
            const kode = box.querySelector('[data-otp-kode]');
            const tombolVerif = box.querySelector('[data-otp-verifikasi]');
            const status = box.querySelector('[data-otp-status]');
            const form = email?.closest('form');
            if (!email || !tombolKirim || !form) return;

            const awal = (box.dataset.emailAwal || '').trim().toLowerCase();
            const token = () => form.querySelector('[name="_token"]')?.value || '';
            const nilai = () => email.value.trim().toLowerCase();
            const perluVerifikasi = () => !awal || nilai() !== awal;

            let berlakuSampai = 0, jedaSampai = 0, emailTerkirim = '', emailTerverifikasi = '', sibuk = false;

            const tulis = (teks, jenis = '', ikon = 'bi-info-circle-fill', denganWaktu = false) => {
                status.hidden = !teks;
                status.className = 'otp-status ' + jenis;
                status.innerHTML = '';
                if (!teks) return;
                const i = document.createElement('i'); i.className = 'bi ' + ikon;
                const s = document.createElement('span'); s.textContent = teks;
                status.append(i, s);
                if (denganWaktu) { const w = document.createElement('span'); w.className = 'otp-waktu'; w.dataset.otpWaktu = ''; status.append(w); }
                gambar();
            };

            // Satu fungsi yang menyusun tampilan dari keadaan saat ini.
            const gambar = () => {
                const t = detikIni();
                const terverifikasi = emailTerverifikasi !== '' && emailTerverifikasi === nilai();
                const perlu = perluVerifikasi();
                const sisaJeda = Math.max(0, jedaSampai - t);
                const sisaBerlaku = Math.max(0, berlakuSampai - t);
                const kodeAktif = emailTerkirim !== '' && emailTerkirim === nilai() && sisaBerlaku > 0;

                email.classList.toggle('otp-ok', terverifikasi && perlu);
                badge && (badge.hidden = !(terverifikasi && perlu));
                tombolKirim.hidden = !perlu || terverifikasi;
                tombolKirim.disabled = sibuk || sisaJeda > 0;
                tombolKirim.querySelector('span').textContent = sibuk ? 'Mengirim…'
                    : sisaJeda > 0 ? 'Kirim ulang (' + sisaJeda + ')'
                    : (emailTerkirim === nilai() && emailTerkirim ? 'Kirim ulang' : 'Kirim OTP');
                panel.hidden = !perlu || terverifikasi || !kodeAktif;
                tombolVerif.disabled = sibuk || kode.value.length !== 6;
                box.hidden = !perlu && status.hidden;

                const w = status.querySelector('[data-otp-waktu]');
                if (w) w.textContent = sisaBerlaku > 0 ? 'Berlaku ' + fmt(sisaBerlaku) : '';
                if (emailTerkirim && !terverifikasi && berlakuSampai && sisaBerlaku === 0) {
                    berlakuSampai = 0;
                    tulis('Kode OTP sudah kedaluwarsa. Silakan kirim ulang kode.', 'galat', 'bi-clock-history');
                }
            };

            const kirim = async (url, isi) => {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token() },
                    body: JSON.stringify(isi),
                });
                return { res, data: await res.json().catch(() => ({})) };
            };

            tombolKirim.addEventListener('click', async () => {
                if (!email.value.trim() || !email.checkValidity()) {
                    tulis('Isi alamat email yang valid terlebih dahulu.', 'galat', 'bi-exclamation-circle-fill');
                    email.focus();
                    return;
                }
                sibuk = true; gambar();
                try {
                    const { res, data } = await kirim(box.dataset.url, { email: email.value.trim() });
                    if (data.berlaku_detik) berlakuSampai = detikIni() + data.berlaku_detik;
                    if (data.jeda_detik) jedaSampai = detikIni() + data.jeda_detik;
                    if (res.ok && data.ok) {
                        emailTerkirim = nilai();
                        kode.value = '';
                        tulis(data.pesan, '', 'bi-envelope-check-fill', true);
                        setTimeout(() => kode.focus(), 50);
                    } else if (res.status === 429) {
                        tulis('Terlalu banyak permintaan kode. Silakan coba lagi dalam beberapa menit.', 'galat', 'bi-exclamation-circle-fill');
                    } else {
                        if (data.berlaku_detik) emailTerkirim = nilai();
                        tulis(data.errors?.email?.[0] || data.pesan || 'Kode OTP gagal dikirim. Silakan coba lagi.', 'galat', 'bi-exclamation-circle-fill', !!data.berlaku_detik);
                    }
                } catch (e) {
                    tulis('Tidak dapat terhubung ke server. Periksa koneksi internet Anda.', 'galat', 'bi-wifi-off');
                } finally {
                    sibuk = false; gambar();
                }
            });

            kode.addEventListener('input', () => {
                kode.value = kode.value.replace(/\D/g, '').slice(0, 6);
                kode.classList.remove('salah');
                gambar();
            });
            kode.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') { e.preventDefault(); if (!tombolVerif.disabled) tombolVerif.click(); }
            });

            tombolVerif.addEventListener('click', async () => {
                sibuk = true; gambar();
                try {
                    const { res, data } = await kirim(box.dataset.urlVerifikasi, { email: email.value.trim(), kode: kode.value });
                    if (res.ok && data.ok) {
                        emailTerverifikasi = nilai();
                        berlakuSampai = 0; jedaSampai = 0;
                        tulis(data.pesan, 'berhasil', 'bi-patch-check-fill');
                        email.closest('form')?.querySelectorAll('.otp-galat-email').forEach((n) => n.remove());
                    } else if (res.status === 429) {
                        tulis('Terlalu banyak percobaan. Silakan tunggu beberapa menit.', 'galat', 'bi-exclamation-circle-fill');
                    } else {
                        kode.classList.add('salah');
                        const pesan = data.errors?.kode?.[0] || data.pesan || 'Kode OTP tidak valid.';
                        if (/kedaluwarsa|kirim ulang|belum dikirim/i.test(pesan)) { emailTerkirim = ''; berlakuSampai = 0; jedaSampai = 0; }
                        tulis(pesan, 'galat', 'bi-x-circle-fill', berlakuSampai > detikIni());
                    }
                } catch (e) {
                    tulis('Tidak dapat terhubung ke server. Periksa koneksi internet Anda.', 'galat', 'bi-wifi-off');
                } finally {
                    sibuk = false; gambar();
                }
            });

            // Email diubah → status verifikasi/kode lama tidak berlaku untuk email yang baru.
            email.addEventListener('input', () => {
                if (emailTerkirim && nilai() !== emailTerkirim) { emailTerkirim = ''; berlakuSampai = 0; jedaSampai = 0; kode.value = ''; tulis(''); }
                if (emailTerverifikasi && nilai() !== emailTerverifikasi) tulis('');
                gambar();
            });

            // Form tidak boleh dikirim sebelum email diverifikasi.
            form.addEventListener('submit', (e) => {
                if (!perluVerifikasi() || (emailTerverifikasi && emailTerverifikasi === nilai())) return;
                e.preventDefault();
                e.stopPropagation();
                tulis(emailTerkirim ? 'Masukkan kode OTP lalu tekan "Verifikasi" sebelum menyimpan.' : 'Verifikasi email terlebih dahulu: tekan "Kirim OTP", lalu masukkan kode dari email Anda.', 'galat', 'bi-exclamation-circle-fill', berlakuSampai > detikIni());
                email.classList.add('is-salah');
                (panel.hidden ? email : kode).focus();
            });

            // Keadaan setelah halaman dimuat ulang (mis. validasi kolom lain gagal).
            if (box.dataset.terverifikasi === '1') {
                emailTerverifikasi = nilai();
            } else if ((parseInt(box.dataset.berlaku, 10) || 0) > 0) {
                berlakuSampai = detikIni() + parseInt(box.dataset.berlaku, 10);
                jedaSampai = detikIni() + (parseInt(box.dataset.jeda, 10) || 0);
                emailTerkirim = nilai();
                tulis('Kode OTP sudah dikirim ke email Anda.', '', 'bi-envelope-fill', true);
            }
            gambar();
            setInterval(gambar, 1000);
        });
    });
</script>
@endonce
