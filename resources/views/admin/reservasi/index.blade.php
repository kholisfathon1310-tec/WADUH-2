@extends('admin.layouts.app')
@section('title', 'Data Reservasi')

@section('content')
    <style>
        /* position:relative + z-index SENGAJA dipasang — animasi "muncul saat scroll" (atribut
           data-reveal, dipakai layout) memberi elemen ini transform, dan transform apa pun
           (walau cuma identity/selesai animasi) otomatis membuat stacking context baru. Tanpa
           z-index eksplisit, kartu filter ini (termasuk dropdown di dalamnya) ikut tertata
           berdasarkan urutan DOM biasa, jadi kalah tumpuk oleh kartu hasil (#hasil-reservasi)
           yang letaknya SETELAH form ini — dropdown pun tampak tumpang tindih/tak kelihatan. */
        [data-filter-form] { position:relative; z-index:5; }

        /* ─── DROPDOWN FILTER MODERN — pengganti <select> browser polos ─── */
        .fancy-select { position:relative; }
        .fancy-select-native { position:absolute; inset:0; opacity:0; pointer-events:none; }
        .fancy-select-btn { display:flex; align-items:center; gap:.5rem; width:100%;
            border:1px solid var(--line); border-radius:.6rem; background:#fff;
            padding:.4rem .7rem; font-size:.82rem; font-weight:600; color:var(--ink);
            cursor:pointer; transition:border-color .15s ease, box-shadow .15s ease; }
        .fancy-select-btn .lbl { flex:1; text-align:left; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .fancy-select-btn i { font-size:.75rem; color:var(--soft); transition:transform .18s ease; flex:none; }
        .fancy-select.buka .fancy-select-btn { border-color:var(--primary); box-shadow:0 0 0 3px rgba(23,107,135,.1); }
        .fancy-select.buka .fancy-select-btn i { transform:rotate(180deg); }
        .fancy-select-menu { display:none; position:absolute; top:calc(100% + 6px); left:0; z-index:1040;
            min-width:100%; width:max-content; max-width:16rem; background:#fff; border:1px solid var(--line);
            border-radius:.85rem; box-shadow:0 18px 40px -12px rgba(15,23,42,.18); padding:.4rem; }
        .fancy-select.buka .fancy-select-menu { display:block; animation:fancySelectPop .15s ease; }
        @keyframes fancySelectPop { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }
        .fancy-select-opt { display:flex; align-items:center; gap:.55rem; width:100%; border:0; background:none;
            border-radius:.55rem; padding:.5rem .6rem; font-size:.8rem; font-weight:600; color:var(--muted);
            cursor:pointer; transition:background .12s ease, color .12s ease; text-align:left; white-space:nowrap; }
        .fancy-select-opt:hover { background:var(--surface-2); color:var(--ink); }
        .fancy-select-opt.active { background:var(--primary-soft); color:var(--primary-dark); font-weight:800; }
        .fancy-select-opt .dot { width:.5rem; height:.5rem; border-radius:50%; flex:none; }
        .fancy-select-opt .dot.menunggu { background:#f59e0b; }
        .fancy-select-opt .dot.disetujui { background:var(--emerald); }
        .fancy-select-opt .dot.ditolak { background:var(--rose); }
        .fancy-select-opt .dot.dibatalkan { background:#94a3b8; }
        .fancy-select-opt .dot.selesai { background:#3b82f6; }
        .fancy-select-opt .dot.kadaluwarsa { background:#8b5cf6; }
    </style>

    <form method="GET" class="xcard p-3 p-md-4 mb-4" data-filter-form data-reveal>
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <label class="form-label mb-1">Nama pemesan</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input name="pemesan" class="form-control border-start-0 ps-0" placeholder="Cari nama…" value="{{ $filter['pemesan'] ?? '' }}" autocomplete="off">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1">Kategori</label>
                <div class="fancy-select" data-fancy-select>
                    <select name="kategori" class="fancy-select-native">
                        <option value="">Semua</option>
                        @foreach ($daftarKategori as $k)<option value="{{ $k }}" @selected(($filter['kategori'] ?? '')===$k)>{{ $k }}</option>@endforeach
                    </select>
                    <button type="button" class="fancy-select-btn"><span class="lbl">Semua</span><i class="bi bi-chevron-down"></i></button>
                    <div class="fancy-select-menu"></div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1">Jenis sewa</label>
                <div class="fancy-select" data-fancy-select>
                    <select name="jenis_sewa" class="fancy-select-native">
                        <option value="">Semua</option>
                        @foreach ($daftarJenis as $j)<option value="{{ $j->satuan->value }}" @selected(($filter['jenis_sewa'] ?? '')===$j->satuan->value)>Per {{ $j->satuan->value }}</option>@endforeach
                    </select>
                    <button type="button" class="fancy-select-btn"><span class="lbl">Semua</span><i class="bi bi-chevron-down"></i></button>
                    <div class="fancy-select-menu"></div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1">Status</label>
                <div class="fancy-select" data-fancy-select>
                    <select name="status" class="fancy-select-native">
                        <option value="">Semua</option>
                        @foreach ($daftarStatus as $s)<option value="{{ $s->value }}" @selected(($filter['status'] ?? '')===$s->value)>{{ $s->value }}</option>@endforeach
                    </select>
                    <button type="button" class="fancy-select-btn"><span class="lbl">Semua</span><i class="bi bi-chevron-down"></i></button>
                    <div class="fancy-select-menu"></div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1">Tanggal reservasi</label>
                <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ $filter['tanggal'] ?? '' }}">
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-3 pt-3" style="border-top:1px solid var(--line)">
            <a href="{{ route('admin.reservasi.index') }}" class="btn btn-brand-outline btn-sm px-3" data-filter-reset><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</a>
        </div>
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

            // Submit manual (tombol/​Enter) tetap dipertahankan, tapi tanpa reload halaman.
            form.addEventListener('submit', (e) => { e.preventDefault(); terapkan(); });

            // Tombol Reset: kosongkan SEMUA field ke default ("Semua"/kosong), bukan form.reset()
            // (yang hanya kembali ke filter awal saat halaman dimuat, mis. dari link ?status=Menunggu).
            const reset = form.querySelector('[data-filter-reset]');
            reset?.addEventListener('click', (e) => {
                e.preventDefault();
                form.querySelectorAll('input').forEach((el) => { el.value = ''; });
                form.querySelectorAll('select').forEach((el) => {
                    el.selectedIndex = 0;
                    el.dispatchEvent(new Event('sync-fancy'));
                });
                terapkan();
            });
        })();
    </script>

    <script>
        {{-- Bungkus tampilan <select> asli (statusquo tetap disembunyikan lewat CSS, tidak
             dihapus) supaya perilaku AJAX filter yang sudah ada (listener 'change' & tombol
             Reset di atas) tidak perlu diubah sama sekali — cukup "dandani" tampilannya saja. --}}
        (function () {
            const statusDot = { 'Menunggu': 'menunggu', 'Disetujui': 'disetujui', 'Ditolak': 'ditolak', 'Selesai': 'selesai', 'Dibatalkan': 'dibatalkan', 'Kadaluwarsa': 'kadaluwarsa' };

            const tutupSemua = (kecuali) => {
                document.querySelectorAll('.fancy-select.buka').forEach((el) => { if (el !== kecuali) el.classList.remove('buka'); });
            };

            document.querySelectorAll('[data-fancy-select]').forEach((wrap) => {
                const select = wrap.querySelector('.fancy-select-native');
                const btn = wrap.querySelector('.fancy-select-btn .lbl');
                const menu = wrap.querySelector('.fancy-select-menu');
                const isStatus = select.name === 'status';

                const render = () => {
                    menu.innerHTML = '';
                    [...select.options].forEach((opt) => {
                        const row = document.createElement('button');
                        row.type = 'button';
                        row.className = 'fancy-select-opt' + (opt.value === select.value ? ' active' : '');
                        const dotCls = isStatus ? (statusDot[opt.value] ?? '') : null;
                        row.innerHTML = (dotCls !== null ? `<span class="dot ${dotCls || 'menunggu'}" style="${dotCls ? '' : 'background:var(--primary)'}"></span>` : '') + `<span>${opt.text}</span>`;
                        row.addEventListener('click', () => {
                            select.value = opt.value;
                            select.dispatchEvent(new Event('change', { bubbles: true }));
                            btn.textContent = opt.text;
                            wrap.classList.remove('buka');
                            render();
                        });
                        menu.appendChild(row);
                    });
                    const selected = select.options[select.selectedIndex];
                    if (selected) btn.textContent = selected.text;
                };
                render();
                select.addEventListener('sync-fancy', render);

                wrap.querySelector('.fancy-select-btn').addEventListener('click', (e) => {
                    e.stopPropagation();
                    const akanTerbuka = !wrap.classList.contains('buka');
                    tutupSemua(wrap);
                    wrap.classList.toggle('buka', akanTerbuka);
                });
                menu.addEventListener('click', (e) => e.stopPropagation());
            });

            document.addEventListener('click', () => tutupSemua(null));
        })();
    </script>
@endsection
