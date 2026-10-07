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
                <a href="{{ route('reservasi.denah', ['kategori' => $kategori, 'lantai' => $lantai->id_lantai, 'jenis' => $j->id_jenis_sewa, 'tanggal_mulai' => $slot['tanggal_mulai']]) }}"
                   data-jenis-pill class="dn-jenis-pill {{ $jenis?->id_jenis_sewa === $j->id_jenis_sewa ? 'active' : '' }}">
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

    <style>
        .dn-filter-galat { display:flex; align-items:flex-start; gap:.4rem; margin-top:.75rem; padding:.6rem .8rem; border-radius:.65rem; background:#fff1f2; border:1px solid #fecdd3; color:#be123c; font-size:.8rem; font-weight:600; line-height:1.45; }
        .dn-filter-galat[hidden] { display:none; }
        .dn-filter-galat i { margin-top:.1rem; flex:none; }
    </style>
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
                        // Pil jenis sewa ikut membawa tanggal yang baru dipilih.
                        const tgl = form.querySelector('[name="tanggal_mulai"]')?.value;
                        document.querySelectorAll('[data-jenis-pill]').forEach((a) => {
                            const u = new URL(a.href);
                            tgl ? u.searchParams.set('tanggal_mulai', tgl) : u.searchParams.delete('tanggal_mulai');
                            a.href = u.toString();
                        });
                    })
                    .catch((err) => {
                        if (err.name !== 'AbortError') hasil.classList.remove('opacity-50');
                    });
            };

            // ── Validasi tanggal: tidak boleh lampau (termasuk yang diketik manual) dan harus
            //    hari kerja. Pilihan yang ditolak dikembalikan ke nilai sebelumnya, denah tidak berubah. ──
            const hariIni = form.querySelector('input[type="date"]')?.min || '';
            const akhirPekan = (v) => { const [y, m, d] = v.split('-').map(Number); const h = new Date(Date.UTC(y, m - 1, d)).getUTCDay(); return h === 0 || h === 6; };
            let galat = form.querySelector('[data-filter-galat]');
            if (!galat) {
                galat = document.createElement('div');
                galat.className = 'dn-filter-galat'; galat.dataset.filterGalat = ''; galat.setAttribute('role', 'alert'); galat.hidden = true;
                form.appendChild(galat);
            }
            const tampilGalat = (pesan) => { galat.innerHTML = pesan ? '<i class="bi bi-exclamation-circle-fill"></i><span></span>' : ''; if (pesan) galat.querySelector('span').textContent = pesan; galat.hidden = !pesan; };
            const pesanTanggal = (el) => {
                const v = el.value;
                if (!v) return el.name === 'tanggal_mulai' ? 'Tanggal wajib dipilih.' : '';
                if (hariIni && v < hariIni) return 'Tanggal yang sudah lewat tidak dapat dipilih. Pilih hari ini atau tanggal sesudahnya.';
                if (akhirPekan(v)) return 'Gedung tidak beroperasi pada hari Sabtu dan Minggu. Silakan pilih hari kerja (Senin–Jumat).';
                const mulai = form.querySelector('[name="tanggal_mulai"]')?.value;
                if (el.name === 'tanggal_selesai' && mulai && v < mulai) return 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
                return '';
            };
            const tanggalSah = () => {
                for (const el of form.querySelectorAll('input[type="date"]')) {
                    const p = pesanTanggal(el);
                    if (p) { tampilGalat(p); return false; }
                }
                tampilGalat('');
                return true;
            };

            form.querySelectorAll('input[type="date"]').forEach((el) => {
                el.dataset.nilaiSah = el.value;
                el.addEventListener('change', () => {
                    if (!tanggalSah()) { el.value = el.dataset.nilaiSah || ''; return; }
                    form.querySelectorAll('input[type="date"]').forEach((x) => { x.dataset.nilaiSah = x.value; });
                    terapkan();
                });
            });

            form.addEventListener('submit', (e) => { e.preventDefault(); if (tanggalSah()) terapkan(); });
            // Status ruangan ikut berubah begitu ada pemesanan baru (lihat partials/pantau-status);
            // ditunda bila pemesan sedang membuka dialog atur jadwal.
            document.addEventListener('realtime:berubah', () => {
                if (!document.querySelector('.modal.show, .swal2-container') && tanggalSah()) terapkan();
            });
        })();
    </script>
@endsection
