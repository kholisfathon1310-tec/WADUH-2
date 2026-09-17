@extends('layouts.customer')
@section('title', 'Denah Lantai '.$lantai->nomor_lantai)

@php
    // Per Jam & Convention Hall: pemakaian selalu 1 hari (otomatis 8 jam) — filter cukup satu tanggal,
    // tidak perlu pilih jam mulai/selesai secara manual.
    $sehariSaja = $kategori === 'Convention Hall' || ($jenis && $jenis->satuan->value === 'Jam');
@endphp

@section('content')
    <nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('reservasi.index') }}">Fasilitas</a></li>
        <li class="breadcrumb-item"><a href="{{ route('reservasi.index', ['kategori' => $kategori]) }}">{{ $kategori }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('reservasi.lantai', ['kategori' => $kategori, 'jenis' => $jenis?->id_jenis_sewa]) }}">Lantai</a></li>
        <li class="breadcrumb-item active">Lantai {{ $lantai->nomor_lantai }}</li>
    </ol></nav>

    @if ($semuaJenis->count() > 1)
        <div class="dn-jenis-switch mb-3" data-reveal>
            <span class="dn-jenis-switch-label"><i class="bi bi-funnel"></i>Jenis Sewa</span>
            @foreach ($semuaJenis as $j)
                <a href="{{ route('reservasi.denah', ['kategori' => $kategori, 'lantai' => $lantai->id_lantai, 'jenis' => $j->id_jenis_sewa]) }}"
                   class="dn-jenis-pill {{ $jenis?->id_jenis_sewa === $j->id_jenis_sewa ? 'active' : '' }}">
                    Per {{ $j->satuan->value }}
                </a>
            @endforeach
        </div>
        <style>
            .dn-jenis-switch { display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; }
            .dn-jenis-switch-label { display:flex; align-items:center; gap:.4rem; font-size:.78rem; font-weight:700; color:var(--muted); }
            .dn-jenis-switch-label i { color:var(--primary); }
            .dn-jenis-pill { border:1.5px solid var(--line); background:#fff; color:var(--ink); font-weight:700; font-size:.82rem; padding:.4rem .95rem; border-radius:2rem; text-decoration:none; transition:all .15s ease; }
            .dn-jenis-pill:hover { border-color:var(--primary); color:var(--primary); }
            .dn-jenis-pill.active { background:var(--primary); border-color:var(--primary); color:#fff; box-shadow:0 8px 18px -6px rgba(14,107,125,.4); }
        </style>
    @endif

    <div data-reveal>
        <x-denah.schedule-filter
            :jenis-id="$jenis?->id_jenis_sewa"
            :sehari-saja="$sehariSaja"
            :jadwal="$slot"
        />
    </div>

    <div id="hasil-denah" data-filter-hasil>
        @include('reservasi.partials.denah-hasil')
    </div>

    <script>
        (function () {
            const form = document.querySelector('[data-filter-form]');
            const hasil = document.getElementById('hasil-denah');
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

            form.querySelectorAll('input[type="date"]').forEach((el) => {
                el.addEventListener('change', terapkan);
            });

            form.addEventListener('submit', (e) => { e.preventDefault(); terapkan(); });
        })();
    </script>
@endsection
