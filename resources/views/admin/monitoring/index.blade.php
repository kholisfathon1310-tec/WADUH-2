@extends('admin.layouts.app')
@section('title', 'Monitoring Lantai '.($lantai?->nomor_lantai ?? ''))

@section('content')
    @include('admin.partials.filter-ui')
    <form method="GET" class="xcard wf-filter mb-4" data-filter-form data-reveal>
        <input type="hidden" name="lantai" value="{{ $lantai?->id_lantai }}">
        <div class="wf-filter-head">
            <div class="wf-filter-title">
                <span class="ic"><i class="bi bi-calendar-event"></i></span>
                <div>{{ $fasilitas->first()?->kategori_fasilitas ?? 'Fasilitas' }}<small>{{ $fasilitas->count() }} ruangan · status mengikuti tanggal pemantauan</small></div>
            </div>
            <div class="wf-filter-aksi">
                <span class="avail hijau"><i class="bi bi-check-circle"></i> Tersedia</span>
                <span class="avail kuning"><i class="bi bi-exclamation-circle"></i> Sebagian Terisi</span>
                <span class="avail merah"><i class="bi bi-x-circle"></i> Terisi</span>
            </div>
        </div>
        <div class="wf-filter-grid" style="grid-template-columns:minmax(0, 15rem);">
            <div class="wf-field">
                <label class="wf-label" for="fTanggalPantau">Tanggal</label>
                <input type="date" id="fTanggalPantau" name="tanggal_mulai" class="wf-control" value="{{ $slot['tanggal_mulai'] }}">
            </div>
        </div>
    </form>

    {{-- Denah interaktif SVG — klik ruangan untuk membuka detail monitoring --}}
    <div id="hasil-monitoring" data-filter-hasil>
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