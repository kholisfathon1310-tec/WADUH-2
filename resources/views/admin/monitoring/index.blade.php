@extends('admin.layouts.app')
@section('title', 'Monitoring Fasilitas')

@section('content')
    <div class="page-hero mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3" data-reveal>
        <div class="d-flex align-items-center gap-3">
            <span class="d-none d-sm-grid" style="place-items:center; width:3.2rem; height:3.2rem; border-radius:1rem; background:rgba(255,255,255,.16); font-weight:800; font-size:1.15rem;">L{{ $lantai?->nomor_lantai }}</span>
            <div>
                <p class="mb-1" style="opacity:.8; font-size:.85rem"><i class="bi bi-grid-3x3-gap me-1"></i>Monitoring Fasilitas</p>
                <h2 class="h4 mb-0">Lantai {{ $lantai?->nomor_lantai }} · {{ $fasilitas->first()?->kategori_fasilitas ?? '-' }}</h2>
            </div>
        </div>
        <div class="text-center px-3 py-2 rounded-3" style="background:rgba(255,255,255,.16)">
            <div class="fs-4 fw-bold brand-font">{{ $fasilitas->count() }}</div>
            <small>ruang di lantai ini</small>
        </div>
    </div>

    <form method="GET" class="xcard p-3 p-md-4 mb-4" data-filter-form data-reveal>
        <input type="hidden" name="lantai" value="{{ $lantai?->id_lantai }}">
        <div class="row g-2 g-md-3 align-items-end">
            <div class="col-6 col-md-3"><label class="form-label mb-1">Tanggal</label><input type="date" name="tanggal_mulai" class="form-control form-control-sm" value="{{ $slot['tanggal_mulai'] }}"></div>
            <div class="col-12 col-md-auto ms-md-auto d-flex flex-wrap align-items-center gap-2">
                <span class="avail hijau"><i class="bi bi-check-circle"></i> Tersedia</span>
                <span class="avail kuning"><i class="bi bi-exclamation-circle"></i> Sebagian terisi</span>
                <span class="avail merah"><i class="bi bi-x-circle"></i> Penuh</span>
            </div>
        </div>
        <small class="text-muted d-block mt-2"><i class="bi bi-info-circle me-1"></i>Pilih lantai lewat submenu Monitoring di sidebar.</small>
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