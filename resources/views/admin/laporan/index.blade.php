@extends('admin.layouts.app')
@section('title', 'Laporan Data Reservasi')

@section('content')
    @include('admin.partials.filter-ui')
    <style>
        /* Filter periode ringkas: judul di kiri, Bulan · Tahun · Bulan Ini dalam satu baris di kanan. */
        .lp-filter { display:flex; align-items:center; justify-content:space-between; gap:.75rem 1.25rem; flex-wrap:wrap; padding:.9rem 1.15rem; }
        .lp-filter .wf-filter-title { flex:none; }
        .lp-filter-kontrol { display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; }
        .lp-filter-kontrol .wf-field.bulan { width:11.5rem; }
        .lp-filter-kontrol .wf-field.tahun { width:8.5rem; }
        .lp-filter-kontrol .wf-btn { min-height:2.75rem; }
        @media (max-width: 575.98px) {
            .lp-filter-kontrol { width:100%; display:grid; grid-template-columns:minmax(0, 1.3fr) minmax(0, 1fr); }
            .lp-filter-kontrol .wf-field.bulan, .lp-filter-kontrol .wf-field.tahun { width:auto; }
            .lp-filter-kontrol .wf-btn { grid-column:1 / -1; }
        }
    </style>

    <form method="GET" action="{{ route('admin.laporan') }}" class="xcard wf-filter lp-filter mb-4" data-filter-form data-reveal>
        <div class="wf-filter-title">
            <span class="ic"><i class="bi bi-calendar-range"></i></span>
            <div>Periode Laporan</div>
        </div>

        <div class="lp-filter-kontrol">
            <div class="wf-field bulan">
                <label class="visually-hidden" for="fBulan">Bulan</label>
                <select id="fBulan" name="bulan" data-wf-select data-wf-netral data-ikon="bi-calendar3">
                    @foreach ($daftarBulan as $angka => $nama)
                        <option value="{{ $angka }}" @selected($bulan === $angka)>{{ $nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="wf-field tahun">
                <label class="visually-hidden" for="fTahun">Tahun</label>
                <select id="fTahun" name="tahun" data-wf-select data-wf-netral data-ikon="bi-calendar4">
                    @foreach ($daftarTahun as $t => $jumlah)
                        <option value="{{ $t }}" @selected($tahun === $t) @if($jumlah) data-keterangan="{{ $jumlah }} data" @endif>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <a href="{{ route('admin.laporan') }}" class="btn btn-brand-outline wf-btn" data-filter-reset><i class="bi bi-calendar-check"></i>Bulan Ini</a>
        </div>
    </form>
    {{-- Tombol Ekspor PDF ada di kaki kartu tabel --}}

    <div id="hasil-laporan" data-filter-hasil>
        @include('admin.laporan.partials.hasil')
    </div>

    <script>
        let semuaTampil = false;
        function toggleSemuaBaris() {
            semuaTampil = !semuaTampil;
            document.querySelectorAll('.baris-extra').forEach(tr => tr.classList.toggle('d-none', !semuaTampil));
            const btn = document.getElementById('btnSemua');
            const info = document.getElementById('infoBaris');
            if (btn) btn.innerHTML = semuaTampil
                ? '<i class="bi bi-chevron-double-up me-1"></i>Tampilkan 10 Data'
                : '<i class="bi bi-chevron-double-down me-1"></i>Tampilkan Semua';
            const jumlah = document.querySelectorAll('#hasil-laporan tbody tr').length;
            if (info) info.textContent = semuaTampil ? 'Menampilkan seluruh ' + jumlah + ' data' : 'Menampilkan 10 dari ' + jumlah + ' data';
        }

        (function () {
            const form = document.querySelector('[data-filter-form]');
            const hasil = document.getElementById('hasil-laporan');
            if (!form || !hasil) return;

            let controller = null;

            const terapkan = () => {
                semuaTampil = false;
                controller?.abort();
                controller = new AbortController();

                const params = new URLSearchParams(new FormData(form));
                [...params.keys()].forEach((k) => { if (!params.get(k)) params.delete(k); });
                const url = form.getAttribute('action') || window.location.pathname;
                const urlLengkap = url + (params.toString() ? '?' + params.toString() : '');

                hasil.classList.add('opacity-50');
                fetch(urlLengkap, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
                    .then((r) => r.text())
                    .then((html) => {
                        hasil.innerHTML = html;
                        hasil.classList.remove('opacity-50');
                        window.history.replaceState(null, '', urlLengkap);
                    })
                    .catch((err) => {
                        if (err.name !== 'AbortError') hasil.classList.remove('opacity-50');
                    });
            };

            form.querySelectorAll('select').forEach((el) => {
                el.addEventListener('change', terapkan);
            });

            form.addEventListener('submit', (e) => { e.preventDefault(); terapkan(); });

            const reset = form.querySelector('[data-filter-reset]');
            reset?.addEventListener('click', (e) => {
                e.preventDefault();
                const now = new Date();
                form.querySelector('[name="bulan"]').value = String(now.getMonth() + 1);
                form.querySelector('[name="tahun"]').value = String(now.getFullYear());
                form.querySelectorAll('select').forEach((el) => el.dispatchEvent(new Event('wf:sync')));
                terapkan();
            });
        })();
    </script>
@endsection
