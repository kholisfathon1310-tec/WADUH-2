{{--
    Pemantau status realtime. Disertakan oleh layout pada halaman yang men-set
    @section('pantau_status', '1'). Setiap beberapa detik menanyakan sidik jari data
    reservasi ke server; bila berubah (mis. status otomatis menjadi Selesai/Kadaluwarsa,
    atau admin menyetujui), halaman dimuat ulang dengan posisi gulir dipertahankan.
    Pemuatan ulang ditunda selama pengguna sedang mengisi formulir atau membuka dialog.
--}}
<div id="pantauStatusInfo" role="status" aria-live="polite"
     style="position:fixed;left:50%;bottom:1rem;transform:translateX(-50%);z-index:2000;display:none;
            background:#0f172a;color:#fff;padding:.55rem 1rem;border-radius:.6rem;font-size:.85rem;
            box-shadow:0 6px 20px rgba(0,0,0,.25);max-width:calc(100vw - 2rem);text-align:center">
    Status reservasi telah diperbarui. Halaman akan dimuat ulang setelah Anda selesai mengisi formulir.
</div>
<script>
    (() => {
        const URL_VERSI = @json(route('status-reservasi.versi'));
        const JEDA_MS = 15000;
        const KUNCI_GULIR = 'pantauStatus:gulir:' + location.pathname + location.search;
        // Versi saat halaman ini dirender, jadi perubahan sesaat setelah render tetap terdeteksi.
        const versiAwal = @json(app(\App\Services\StatusOtomatisService::class)->versi());
        let menungguMuatUlang = false;
        let formDiubah = false;

        // Pulihkan posisi gulir setelah pemuatan ulang otomatis.
        try {
            const y = sessionStorage.getItem(KUNCI_GULIR);
            if (y !== null) {
                sessionStorage.removeItem(KUNCI_GULIR);
                window.scrollTo(0, parseInt(y, 10) || 0);
            }
        } catch (e) {}

        // Formulir yang sudah diisi pengguna (selain filter pencarian) menahan pemuatan ulang.
        document.addEventListener('input', e => {
            const f = e.target.closest('form');
            if (f && (f.method || '').toLowerCase() === 'post') formDiubah = true;
        });
        document.addEventListener('submit', () => { formDiubah = false; });

        const amanDimuatUlang = () =>
            document.visibilityState === 'visible'
            && !formDiubah
            && !document.querySelector('.modal.show, .swal2-container, .offcanvas.show');

        const muatUlang = () => {
            try { sessionStorage.setItem(KUNCI_GULIR, String(window.scrollY)); } catch (e) {}
            location.reload();
        };

        const cobaMuatUlang = () => {
            if (!menungguMuatUlang) return;
            if (amanDimuatUlang()) return muatUlang();
            if (formDiubah) document.getElementById('pantauStatusInfo').style.display = 'block';
        };

        const periksa = async () => {
            if (document.visibilityState !== 'visible') return;
            try {
                const res = await fetch(URL_VERSI, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
                if (!res.ok) return;
                const { versi } = await res.json();
                if (versi !== versiAwal) menungguMuatUlang = true;
                cobaMuatUlang();
            } catch (e) {
                // Jaringan terputus sementara — coba lagi pada putaran berikutnya.
            }
        };

        setInterval(periksa, JEDA_MS);
        document.addEventListener('visibilitychange', periksa);
        document.addEventListener('hidden.bs.modal', cobaMuatUlang);
    })();
</script>
