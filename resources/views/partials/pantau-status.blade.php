{{--
    Pemantau realtime (polling) — disertakan di SEMUA halaman layout admin, pemesan, & reservasi.
    Variabel: $peran ('admin' | 'pemesan' | null untuk tamu).

    Setiap JEDA_MS (hanya saat tab terlihat) menanyakan ringkasan ke server. Request ini juga
    melewati middleware JalankanStatusOtomatis, jadi status Selesai/Kadaluwarsa ikut diperbarui.
    Bila sidik jari data berubah:
      1. Event `realtime:berubah` dikirim ke document — halaman pemesanan memakainya untuk memuat
         ulang ketersediaan jadwal (denah, pilihan jam, cek jadwal).
      2. Halaman yang punya wilayah [data-live="nama"] diperbarui DI TEMPAT: halaman yang sama
         diambil ulang lalu isi tiap wilayah diganti (posisi gulir, isian, & <details> terbuka
         dipertahankan). Skrip di dalam wilayah dijalankan ulang; pendengar global di skrip itu
         memakai `window.liveSinyal()` supaya yang lama dilepas terlebih dahulu.
      3. Halaman dengan @section('pantau_status') tanpa wilayah live dimuat ulang (posisi gulir
         dipertahankan), ditunda selama pengguna mengisi formulir atau membuka dialog.
    Badge ([data-live-badge]) & notifikasi diperbarui dari ringkasan yang sama.
--}}
@php
    $kursorRealtime = \App\Http\Controllers\StatusRealtimeController::kursorAwal();
@endphp
<div class="rt-toasts" id="rtToasts" aria-live="polite" aria-atomic="false"></div>
<div class="rt-info" id="pantauStatusInfo" role="status" hidden>
    <i class="bi bi-arrow-repeat"></i>
    <span>Data telah diperbarui. Halaman akan dimuat ulang setelah Anda selesai mengisi formulir.</span>
</div>
<style>
    .rt-toasts { position:fixed; right:1rem; bottom:1rem; z-index:2100; display:flex; flex-direction:column; gap:.6rem;
        width:min(23rem, calc(100vw - 2rem)); pointer-events:none; }
    .rt-toast { pointer-events:auto; display:flex; gap:.75rem; align-items:flex-start; padding:.85rem .95rem; border-radius:.9rem;
        background:#fff; border:1px solid #e2e8f0; border-left:4px solid var(--rt-c, #176b87); color:#0f172a; text-decoration:none;
        box-shadow:0 18px 38px -14px rgba(15,23,42,.35); opacity:0; transform:translateY(10px);
        transition:opacity .2s ease, transform .2s ease; }
    .rt-toast.show { opacity:1; transform:none; }
    .rt-toast:hover { color:#0f172a; background:#f8fafc; }
    .rt-toast-ic { flex:none; width:2rem; height:2rem; border-radius:.6rem; display:grid; place-items:center;
        background:color-mix(in srgb, var(--rt-c, #176b87) 12%, #fff); color:var(--rt-c, #176b87); font-size:1rem; }
    .rt-toast-body { flex:1; min-width:0; }
    .rt-toast-judul { font-size:.84rem; font-weight:800; line-height:1.3; }
    .rt-toast-pesan { font-size:.78rem; color:#475569; line-height:1.45; margin-top:.15rem; }
    .rt-toast-tutup { flex:none; border:0; background:none; color:#94a3b8; padding:0 .1rem; font-size:1rem; line-height:1; cursor:pointer; }
    .rt-toast-tutup:hover { color:#0f172a; }
    .rt-toast.sukses { --rt-c:#0d7a55; } .rt-toast.bahaya { --rt-c:#b91c1c; }
    .rt-toast.peringatan { --rt-c:#b45309; } .rt-toast.info { --rt-c:#176b87; }
    .rt-info { position:fixed; left:50%; bottom:1rem; transform:translateX(-50%); z-index:2000; display:flex; gap:.5rem; align-items:center;
        background:#0f172a; color:#fff; padding:.6rem 1rem; border-radius:.7rem; font-size:.82rem; font-weight:600;
        box-shadow:0 10px 26px rgba(0,0,0,.25); max-width:calc(100vw - 2rem); }
    .rt-info[hidden] { display:none; }
    /* Sorotan halus pada wilayah yang baru diperbarui. */
    [data-live].rt-segar { animation:rtSegar 1.2s ease; }
    @keyframes rtSegar { 0% { box-shadow:0 0 0 0 rgba(23,107,135,.0); } 25% { box-shadow:0 0 0 3px rgba(23,107,135,.18); } 100% { box-shadow:0 0 0 0 rgba(23,107,135,0); } }
    @media (max-width: 575.98px) { .rt-toasts { right:.75rem; left:.75rem; width:auto; bottom:.75rem; } }
    @media (prefers-reduced-motion: reduce) { .rt-toast { transition:none; } [data-live].rt-segar { animation:none; } }
</style>
<script>
    // Pendengar global milik skrip di dalam wilayah live dilepas sebelum skrip itu dijalankan ulang.
    window.liveSinyal = window.liveSinyal || (() => (window.__liveAC ??= new AbortController()).signal);

    (() => {
        const URL_RINGKASAN = @json(route('status-reservasi.ringkasan'));
        const PERAN = @json($peran ?? null);
        const PANTAU_HALAMAN = @json(trim($__env->yieldContent('pantau_status')) !== '');
        const JEDA_MS = 10000;
        const KUNCI_GULIR = 'pantauStatus:gulir:' + location.pathname + location.search;
        const KUNCI_KURSOR = 'pantauStatus:kursor:' + (PERAN || 'tamu');
        const versiAwal = @json(app(\App\Services\StatusOtomatisService::class)->versi());

        let versi = versiAwal;
        let menungguMuatUlang = false;
        let formDiubah = false;
        let sedangMemperbarui = false;

        // Kursor notifikasi: ambil yang terbesar antara render server & sesi sebelumnya, supaya
        // notifikasi tidak muncul dua kali saat berpindah halaman.
        let kursor = @json($kursorRealtime);
        try {
            const k = JSON.parse(sessionStorage.getItem(KUNCI_KURSOR) || 'null');
            if (k) kursor = { r: Math.max(kursor.r, k.r || 0), h: Math.max(kursor.h, k.h || 0) };
        } catch (e) {}
        const simpanKursor = () => { try { sessionStorage.setItem(KUNCI_KURSOR, JSON.stringify(kursor)); } catch (e) {} };
        simpanKursor();

        try {
            const y = sessionStorage.getItem(KUNCI_GULIR);
            if (y !== null) { sessionStorage.removeItem(KUNCI_GULIR); window.scrollTo(0, parseInt(y, 10) || 0); }
        } catch (e) {}

        document.addEventListener('input', (e) => {
            const f = e.target.closest('form');
            if (f && (f.method || '').toLowerCase() === 'post') formDiubah = true;
        });
        document.addEventListener('submit', () => { formDiubah = false; });

        const adaDialog = () => !!document.querySelector('.modal.show, .swal2-container, .offcanvas.show, .db-floating-card.show');

        // ── Notifikasi ──
        const IKON = { sukses: 'bi-check-circle-fill', bahaya: 'bi-x-circle-fill', peringatan: 'bi-exclamation-triangle-fill', info: 'bi-bell-fill' };
        const wadahToast = document.getElementById('rtToasts');
        const toast = (n) => {
            const el = document.createElement(n.url ? 'a' : 'div');
            el.className = 'rt-toast ' + (IKON[n.jenis] ? n.jenis : 'info');
            if (n.url) el.href = n.url;
            el.innerHTML = '<span class="rt-toast-ic"><i class="bi"></i></span><span class="rt-toast-body"><span class="rt-toast-judul d-block"></span><span class="rt-toast-pesan d-block"></span></span><button type="button" class="rt-toast-tutup" aria-label="Tutup">&times;</button>';
            el.querySelector('.bi').classList.add(IKON[n.jenis] || IKON.info);
            el.querySelector('.rt-toast-judul').textContent = n.judul;
            el.querySelector('.rt-toast-pesan').textContent = n.pesan;
            const tutup = () => { el.classList.remove('show'); setTimeout(() => el.remove(), 220); };
            el.querySelector('.rt-toast-tutup').addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); tutup(); });
            wadahToast.appendChild(el);
            while (wadahToast.children.length > 3) wadahToast.firstElementChild.remove();
            requestAnimationFrame(() => el.classList.add('show'));
            setTimeout(tutup, 9000);
        };

        // ── Badge ──
        const perbaruiBadge = (badge) => {
            Object.entries(badge || {}).forEach(([nama, n]) => {
                document.querySelectorAll(`[data-live-badge="${nama}"]`).forEach((el) => {
                    el.textContent = n > 99 ? '99+' : n;
                    el.hidden = n <= 0;
                });
            });
        };

        // ── Pembaruan wilayah live ──
        const jalankanSkrip = (wadah) => {
            wadah.querySelectorAll('script').forEach((lama) => {
                const baru = document.createElement('script');
                [...lama.attributes].forEach((a) => baru.setAttribute(a.name, a.value));
                baru.textContent = lama.textContent;
                lama.replaceWith(baru);
            });
        };

        const sibuk = (wilayah) => {
            const aktif = document.activeElement;
            return adaDialog()
                || (aktif && wilayah.contains(aktif) && aktif.matches('input, textarea, select, [contenteditable]'))
                || !!wilayah.querySelector('.buka, [aria-expanded="true"]');
        };

        const perbaruiWilayah = async () => {
            const wilayah = [...document.querySelectorAll('[data-live]')];
            if (!wilayah.length || sedangMemperbarui) return false;
            if (wilayah.some(sibuk)) { menungguMuatUlang = true; return true; }
            sedangMemperbarui = true;
            try {
                const res = await fetch(location.href, { headers: { 'X-Realtime': '1' }, cache: 'no-store', credentials: 'same-origin' });
                if (!res.ok || new URL(res.url).pathname !== location.pathname) { muatUlang(); return true; }
                const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
                const adaSkrip = wilayah.some((w) => w.querySelector('script'));
                if (adaSkrip) { window.__liveAC?.abort(); window.__liveAC = null; }
                for (const w of wilayah) {
                    const baru = doc.querySelector(`[data-live="${w.dataset.live}"]`);
                    if (!baru) { muatUlang(); return true; }
                    if (baru.innerHTML === w.innerHTML) continue;
                    const terbuka = new Set([...w.querySelectorAll('details[open][data-live-key]')].map((d) => d.dataset.liveKey));
                    const gulirDalam = [...w.querySelectorAll('[data-live-gulir]')].map((el) => [el.dataset.liveGulir, el.scrollLeft, el.scrollTop]);
                    baru.querySelectorAll('[data-reveal]').forEach((el) => el.removeAttribute('data-reveal'));
                    w.innerHTML = baru.innerHTML;
                    w.querySelectorAll('details[data-live-key]').forEach((d) => { d.open = terbuka.has(d.dataset.liveKey); });
                    gulirDalam.forEach(([k, x, y]) => { const el = w.querySelector(`[data-live-gulir="${k}"]`); if (el) { el.scrollLeft = x; el.scrollTop = y; } });
                    jalankanSkrip(w);
                    w.classList.remove('rt-segar'); void w.offsetWidth; w.classList.add('rt-segar');
                    w.dispatchEvent(new CustomEvent('realtime:diperbarui', { bubbles: true }));
                }
                menungguMuatUlang = false;
            } catch (e) {
                menungguMuatUlang = true;
            } finally {
                sedangMemperbarui = false;
            }
            return true;
        };

        const muatUlang = () => {
            try { sessionStorage.setItem(KUNCI_GULIR, String(window.scrollY)); } catch (e) {}
            location.reload();
        };

        const tanganiPerubahan = async () => {
            if (document.querySelector('[data-live]')) return perbaruiWilayah();
            if (!PANTAU_HALAMAN) { menungguMuatUlang = false; return; }
            if (document.visibilityState === 'visible' && !formDiubah && !adaDialog()) return muatUlang();
            if (formDiubah) document.getElementById('pantauStatusInfo').hidden = false;
        };

        const periksa = async () => {
            if (document.visibilityState !== 'visible') return;
            try {
                const q = new URLSearchParams({ r: kursor.r, h: kursor.h });
                if (PERAN) q.set('peran', PERAN);
                const res = await fetch(URL_RINGKASAN + '?' + q, { headers: { 'Accept': 'application/json' }, cache: 'no-store', credentials: 'same-origin' });
                if (!res.ok) return;
                const data = await res.json();
                perbaruiBadge(data.badge);
                (data.notifikasi || []).forEach(toast);
                if (data.kursor) { kursor = data.kursor; simpanKursor(); }
                if (data.versi !== versi) {
                    versi = data.versi;
                    menungguMuatUlang = true;
                    document.dispatchEvent(new CustomEvent('realtime:berubah', { detail: { versi } }));
                }
                if (menungguMuatUlang) await tanganiPerubahan();
            } catch (e) {
                // Jaringan terputus sementara — coba lagi pada putaran berikutnya.
            }
        };

        setInterval(periksa, JEDA_MS);
        document.addEventListener('visibilitychange', periksa);
        document.addEventListener('hidden.bs.modal', () => { if (menungguMuatUlang) tanganiPerubahan(); });
    })();
</script>
