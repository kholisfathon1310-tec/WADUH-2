@extends('admin.layouts.app')
@section('pantau_status', '1')
@section('title', 'Monitoring Lantai '.($lantai?->nomor_lantai ?? ''))

@section('content')
    @include('admin.partials.filter-ui')
    <style>
        /* Filter ringkas: kategori di kiri, tanggal pemantauan di kanan dalam satu baris. */
        .mon-filter { display:flex; align-items:center; justify-content:space-between; gap:.75rem 1.25rem; flex-wrap:wrap; padding:.9rem 1.15rem; }
        .mon-filter-tanggal { display:flex; align-items:center; gap:.6rem; }
        .mon-filter-tanggal .wf-label { margin:0; }
        .mon-filter-tanggal .wf-control { width:11.5rem; }
        @media (max-width: 575.98px) {
            .mon-filter-tanggal { width:100%; }
            .mon-filter-tanggal .wf-control { width:auto; flex:1; }
        }
    </style>
    <form method="GET" class="xcard wf-filter mon-filter mb-4" data-filter-form data-reveal>
        <input type="hidden" name="lantai" value="{{ $lantai?->id_lantai }}">
        <div class="wf-filter-title">
            <span class="ic"><i class="bi bi-calendar-event"></i></span>
            <div>{{ $fasilitas->first()?->kategori_fasilitas ?? 'Fasilitas' }}</div>
        </div>
        <div class="mon-filter-tanggal">
            <label class="wf-label" for="fTanggalPantau">Tanggal</label>
            <input type="date" id="fTanggalPantau" name="tanggal_mulai" class="wf-control" value="{{ $slot['tanggal_mulai'] }}">
        </div>
    </form>

    {{-- Denah interaktif SVG — klik ruangan untuk membuka detail monitoring --}}
    <div id="hasil-monitoring" data-filter-hasil data-live="monitoring">
        @include('admin.monitoring.partials.hasil')
    </div>

    <script>
        (function () {
            const form = document.querySelector('[data-filter-form]');
            const hasil = document.getElementById('hasil-monitoring');
            if (!form || !hasil) return;

            let controller = null;

            const terapkan = () => {
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
                        window.initDenah?.(hasil);
                        window.history.replaceState(null, '', urlLengkap);
                    })
                    .catch((err) => {
                        if (err.name !== 'AbortError') hasil.classList.remove('opacity-50');
                    });
            };

            // Tanggal: langsung terapkan begitu berubah.
            form.querySelectorAll('input[type="date"]').forEach((el) => {
                el.addEventListener('change', terapkan);
            });

            // Submit manual (tombol Cek) tetap dipertahankan, tapi tanpa reload halaman.
            form.addEventListener('submit', (e) => { e.preventDefault(); terapkan(); });
        })();
    </script>
@endsection