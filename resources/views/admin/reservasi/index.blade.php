@extends('admin.layouts.app')
@section('pantau_status', '1')
@section('title', 'Data Reservasi')

@section('content')
    <style>
        .rv-pagination { display:flex; flex-wrap:wrap; justify-content:center; align-items:center; gap:.35rem; margin:.5rem 0 1.5rem; }
        .rv-page { min-width:2.35rem; height:2.35rem; padding:0 .7rem; display:inline-flex; align-items:center; justify-content:center; border:1px solid var(--line); border-radius:.65rem; background:#fff; color:var(--ink); font-weight:700; font-size:.85rem; text-decoration:none; transition:background .15s ease, border-color .15s ease; }
        .rv-page:hover { border-color:var(--primary); color:var(--primary); }
        .rv-page.active { background:var(--primary); border-color:var(--primary); color:#fff; }
        .rv-page.disabled { opacity:.45; pointer-events:none; }
        .rv-page-gap { color:var(--muted); padding:0 .2rem; }
    </style>
    @include('admin.partials.filter-ui')
    <style>
        /* Susunan kolom filter halaman ini. Kolom Kategori dibuat cukup lebar supaya nama
           kategori terpanjang ("Co-Working Space") tidak terpotong di tombol pemicu. */
        .wf-grid-reservasi { grid-template-columns:minmax(0, 1.3fr) minmax(0, 1.25fr) repeat(3, minmax(0, 1fr)); }
        @media (max-width: 1199.98px) {
            /* Tablet: baris 1 = cari + kategori, baris 2 = jenis sewa, status, tanggal. */
            .wf-grid-reservasi { grid-template-columns:repeat(6, minmax(0, 1fr)); }
            .wf-grid-reservasi > .wf-field { grid-column:span 2; }
            .wf-grid-reservasi > .wf-field-cari, .wf-grid-reservasi > .wf-field-kategori { grid-column:span 3; }
        }
        @media (max-width: 575.98px) {
            .wf-grid-reservasi { grid-template-columns:repeat(2, minmax(0, 1fr)); }
            .wf-grid-reservasi > .wf-field { grid-column:auto; }
            .wf-grid-reservasi > .wf-field-cari, .wf-grid-reservasi > .wf-field-kategori { grid-column:1 / -1; }
            .wf-grid-reservasi > .wf-field-tanggal { grid-column:1 / -1; }
        }
        @media (max-width: 419.98px) {
            .wf-grid-reservasi { grid-template-columns:minmax(0, 1fr); }
        }
    </style>

    <form method="GET" class="xcard wf-filter mb-4" data-filter-form data-reveal>
        <div class="wf-filter-head">
            <div class="wf-filter-title">
                <span class="ic"><i class="bi bi-funnel"></i></span>
                <div>Filter Reservasi</div>
            </div>
            <div class="wf-filter-aksi">
                <a href="{{ route('admin.reservasi.index') }}" class="btn btn-brand-outline wf-btn" data-filter-reset><i class="bi bi-arrow-counterclockwise"></i>Atur Ulang</a>
            </div>
        </div>

        <div class="wf-filter-grid wf-grid-reservasi">
            <div class="wf-field wf-field-cari">
                <label class="wf-label" for="fPemesan">Nama Pemesan</label>
                <div class="wf-input-ic">
                    <i class="bi bi-search"></i>
                    <input type="search" id="fPemesan" name="pemesan" class="wf-control" placeholder="Cari nama pemesan" value="{{ $filter['pemesan'] ?? '' }}" autocomplete="off" data-wf-chip="Pemesan">
                </div>
            </div>
            <div class="wf-field wf-field-kategori">
                <label class="wf-label" for="fKategori">Kategori</label>
                <select id="fKategori" name="kategori" data-wf-select data-wf-chip="Kategori">
                    <option value="">Semua</option>
                    @foreach ($daftarKategori as $k)<option value="{{ $k }}" @selected(($filter['kategori'] ?? '')===$k)>{{ $k }}</option>@endforeach
                </select>
            </div>
            <div class="wf-field">
                <label class="wf-label" for="fJenisSewa">Jenis Sewa</label>
                <select id="fJenisSewa" name="jenis_sewa" data-wf-select data-wf-chip="Jenis sewa">
                    <option value="">Semua</option>
                    @foreach ($daftarJenis as $j)<option value="{{ $j->satuan->value }}" @selected(($filter['jenis_sewa'] ?? '')===$j->satuan->value)>Per {{ $j->satuan->value }}</option>@endforeach
                </select>
            </div>
            <div class="wf-field">
                <label class="wf-label" for="fStatus">Status</label>
                <select id="fStatus" name="status" data-wf-select data-wf-chip="Status">
                    <option value="" data-dot="semua">Semua</option>
                    @foreach ($daftarStatus as $s)<option value="{{ $s->value }}" data-dot="{{ strtolower($s->value) }}" @selected(($filter['status'] ?? '')===$s->value)>{{ $s->value }}</option>@endforeach
                </select>
            </div>
            <div class="wf-field wf-field-tanggal">
                <label class="wf-label" for="fTanggal">Tanggal Pemakaian</label>

                <input type="date" id="fTanggal" name="tanggal" class="wf-control" value="{{ $filter['tanggal'] ?? '' }}" data-wf-chip="Tanggal">
            </div>
        </div>

        <div class="wf-chips" data-wf-chips hidden></div>
    </form>

    <div id="hasil-reservasi" data-filter-hasil>
        @include('admin.reservasi.partials.hasil')
    </div>

    <script>
        (function () {
            const form = document.querySelector('[data-filter-form]');
            const hasil = document.getElementById('hasil-reservasi');
            if (!form || !hasil) return;

            let timer = null;
            let controller = null;

            const terapkan = () => {
                clearTimeout(timer);
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

            // Dropdown & tanggal: langsung terapkan begitu berubah.
            form.querySelectorAll('select, input[type="date"]').forEach((el) => {
                el.addEventListener('change', terapkan);
            });

            // Nama pemesan: pencarian langsung sambil mengetik (debounce 350ms).
            const pencarian = form.querySelector('input[name="pemesan"]');
            pencarian?.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(terapkan, 350);
            });

            // Pindah halaman daftar tanpa memuat ulang; filter aktif ikut terbawa di URL tautan.
            hasil.addEventListener('click', (e) => {
                const a = e.target.closest('[data-pagination] a');
                if (!a) return;
                e.preventDefault();
                if (a.classList.contains('disabled') || a.classList.contains('active')) return;
                controller?.abort();
                controller = new AbortController();
                hasil.classList.add('opacity-50');
                fetch(a.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
                    .then((r) => r.text())
                    .then((html) => {
                        hasil.innerHTML = html;
                        hasil.classList.remove('opacity-50');
                        window.history.replaceState(null, '', a.href);
                        hasil.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    })
                    .catch((err) => { if (err.name !== 'AbortError') window.location.href = a.href; });
            });

            // Submit manual (tombol/​Enter) tetap dipertahankan, tapi tanpa reload halaman.
            form.addEventListener('submit', (e) => { e.preventDefault(); terapkan(); });

            // Tombol Reset: kosongkan SEMUA field ke default ("Semua"/kosong), bukan form.reset()
            // (yang hanya kembali ke filter awal saat halaman dimuat, mis. dari link ?status=Menunggu).
            const reset = form.querySelector('[data-filter-reset]');
            reset?.addEventListener('click', (e) => {
                e.preventDefault();
                form.querySelectorAll('input').forEach((el) => { el.value = ''; });
                form.querySelectorAll('select').forEach((el) => { el.selectedIndex = 0; });
                form.dispatchEvent(new Event('wf:refresh')); // segarkan dropdown & chip filter aktif
                terapkan();
            });
        })();
    </script>
@endsection
