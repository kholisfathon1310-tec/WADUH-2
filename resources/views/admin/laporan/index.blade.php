@extends('admin.layouts.app')
@section('title', 'Laporan Data Reservasi')

@section('content')
    @include('admin.partials.filter-ui')
    <style>
        .wf-grid-laporan { grid-template-columns:repeat(2, minmax(0, 15rem)); }
        @media (max-width: 575.98px) { .wf-grid-laporan { grid-template-columns:minmax(0, 1.35fr) minmax(0, 1fr); } }

        @media (max-width: 359.98px) { .wf-grid-laporan { grid-template-columns:minmax(0, 1fr); } }
    </style>

    <form method="GET" action="{{ route('admin.laporan') }}" class="xcard wf-filter mb-4" data-filter-form data-reveal>
        <div class="wf-filter-head">
            <div class="wf-filter-title">
                <span class="ic"><i class="bi bi-calendar-range"></i></span>
                <div>Periode Laporan</div>
            </div>
            <div class="wf-filter-aksi">
                <a href="{{ route('admin.laporan') }}" class="btn btn-brand-outline wf-btn" data-filter-reset><i class="bi bi-calendar-check"></i>Bulan Ini</a>
            </div>
        </div>

        <div class="wf-filter-grid wf-grid-laporan">
            <div class="wf-field">
                <label class="wf-label" for="fBulan">Bulan</label>
                <select id="fBulan" name="bulan" data-wf-select data-wf-netral data-ikon="bi-calendar3">
                    @foreach ($daftarBulan as $angka => $nama)
                        <option value="{{ $angka }}" @selected($bulan === $angka)>{{ $nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="wf-field">
                <label class="wf-label" for="fTahun">Tahun</label>
                <select id="fTahun" name="tahun" data-wf-select data-wf-netral data-ikon="bi-calendar4">
                    @foreach ($daftarTahun as $t)
                        <option value="{{ $t }}" @selected($tahun === $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>
    {{-- Tombol Ekspor PDF dipindah ke bawah tabel --}}

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
            if (info) info.textContent = (semuaTampil ? 'seluruh ' : '10 dari ') + document.querySelectorAll('#hasil-laporan tbody tr:not(.fw-bold)').length + ' data';
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
