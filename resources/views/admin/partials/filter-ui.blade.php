{{--
    Komponen filter admin — dipakai bersama oleh semua halaman admin yang punya baris filter
    (Data Reservasi, Laporan, Monitoring), supaya tampilannya satu gaya.

    Cara pakai:
      @include('admin.partials.filter-ui')            ← sekali per halaman (CSS + JS, @once)
      <form class="xcard wf-filter" data-filter-form>
          <div class="wf-filter-head"> … judul + tombol … </div>
          <div class="wf-filter-grid">
              <div class="wf-field">
                  <label class="wf-label" for="fStatus">Status</label>
                  <select id="fStatus" name="status" data-wf-select data-wf-chip="Status"> … </select>
              </div>
          </div>
          <div class="wf-chips" data-wf-chips hidden></div>   ← chip filter aktif (opsional)
      </form>

    Dropdown: <select data-wf-select> ASLI tetap ada di dalam form (nilainya yang dikirim, dan
    tetap berfungsi bila JavaScript mati). Skrip di bawah hanya menambahkan tombol pemicu +
    panel opsi di atasnya, lalu menyinkronkan pilihan ke <select> dan memicu event `change`
    — jadi filter AJAX tiap halaman tidak perlu tahu apa pun tentang komponen ini.
      - data-ikon="bi-…"        ikon di tombol pemicu
      - data-wf-netral          jangan tandai sebagai "filter aktif" (mis. Bulan/Tahun)
      - <option data-dot="…">   titik warna status (menunggu|disetujui|ditolak|selesai|dibatalkan|kadaluwarsa)
      - data-wf-chip="Label"    ikutkan kolom ini pada chip filter aktif
    Menyegarkan tampilan setelah nilai diubah lewat skrip: select.dispatchEvent(new Event('wf:sync'))
    atau form.dispatchEvent(new Event('wf:refresh')).
--}}
@once
<style>
    /* ══════ KARTU FILTER ══════ */
    .wf-filter { padding:1.1rem 1.25rem 1.25rem; }
    .wf-filter-head { display:flex; align-items:center; justify-content:space-between; gap:.6rem 1rem; flex-wrap:wrap; margin-bottom:.95rem; }
    .wf-filter-title { display:flex; align-items:center; gap:.65rem; min-width:0; font-size:.95rem; font-weight:800; color:var(--ink); }
    .wf-filter-title .ic { display:grid; place-items:center; width:2.25rem; height:2.25rem; border-radius:.7rem; flex:none;
        background:var(--primary-soft); color:var(--primary-dark); border:1px solid var(--primary-softer); font-size:.95rem; }
    .wf-filter-title small { display:block; font-size:.76rem; font-weight:500; color:var(--muted); margin-top:.05rem; }
    .wf-filter-aksi { display:flex; align-items:center; flex-wrap:wrap; gap:.5rem; }
    .wf-filter-grid { display:grid; gap:.85rem 1rem; align-items:end; grid-template-columns:repeat(auto-fit, minmax(min(100%, 11rem), 1fr)); }
    .wf-field { display:flex; flex-direction:column; gap:.35rem; min-width:0; }
    .wf-label { margin:0; font-size:.76rem; font-weight:700; color:#475569; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .wf-hint { font-size:.76rem; color:var(--muted); margin:.85rem 0 0; }

    /* ══════ KONTROL — tinggi & gaya sama untuk input cari, tanggal, dan pemicu dropdown ══════ */
    .wf-control, .wf-select-btn, select[data-wf-select] {
        display:flex; align-items:center; width:100%; min-height:2.75rem; margin:0;
        border:1px solid var(--line); border-radius:.75rem; background-color:#fff;
        padding:0 .85rem; font:inherit; font-size:.86rem; font-weight:600; color:var(--ink); line-height:1.2;
        transition:border-color .15s ease, box-shadow .15s ease, background-color .15s ease; }
    .wf-control::placeholder { color:var(--soft); font-weight:500; }
    .wf-control:hover, .wf-select-btn:hover { border-color:#cbd5e1; }
    .wf-control:focus, .wf-select-btn:focus-visible, .wf-select.buka .wf-select-btn {
        outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(23,107,135,.14); }
    input[type="date"].wf-control { min-width:0; }
    input[type="date"].wf-control::-webkit-calendar-picker-indicator { cursor:pointer; opacity:.6; }
    input[type="search"].wf-control::-webkit-search-cancel-button { cursor:pointer; }
    .wf-input-ic { position:relative; }
    .wf-input-ic > i { position:absolute; left:.85rem; top:50%; transform:translateY(-50%); color:var(--soft); font-size:.9rem; pointer-events:none; }
    .wf-input-ic > .wf-control { padding-left:2.35rem; }
    .wf-control.ada-nilai { border-color:var(--primary-softer); background-color:var(--primary-tint); }

    .wf-btn { display:inline-flex; align-items:center; justify-content:center; gap:.4rem; min-height:2.5rem; padding:0 .95rem;
        font-size:.82rem; border-radius:.75rem; white-space:nowrap; }

    /* ══════ DROPDOWN ══════ */
    .wf-select { position:relative; min-width:0; }
    /* <select> asli: tetap di DOM (nilai form), disembunyikan secara visual setelah komponen terpasang. */
    .wf-select > select { position:absolute; left:0; bottom:0; width:1px; height:1px; min-height:0; padding:0; border:0; opacity:0; pointer-events:none; }
    .wf-select-btn { gap:.5rem; cursor:pointer; text-align:left; }
    .wf-select-lead { display:none; flex:none; color:var(--soft); font-size:.9rem; line-height:1; }
    .wf-select-lead.tampil { display:inline-flex; align-items:center; }
    .wf-select-val { flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .wf-select-caret { flex:none; font-size:.72rem; color:var(--soft); transition:transform .18s ease; }
    .wf-select.buka .wf-select-caret { transform:rotate(180deg); color:var(--primary); }
    .wf-select.ada-nilai .wf-select-btn { border-color:var(--primary-softer); background-color:var(--primary-tint); color:var(--primary-dark); }
    .wf-select.ada-nilai .wf-select-lead { color:var(--primary); }

    /* Panel opsi dipasang langsung di <body> (position:fixed, dihitung dari posisi tombol),
       jadi tidak pernah terpotong overflow kartu atau kalah tumpuk dari kartu di bawahnya. */
    .wf-menu { position:fixed; z-index:1056; box-sizing:border-box; max-width:calc(100vw - 2rem);
        background:#fff; border:1px solid var(--line); border-radius:.9rem; padding:.35rem;
        box-shadow:0 22px 44px -14px rgba(15,23,42,.28), 0 4px 12px -4px rgba(15,23,42,.08);
        overflow-y:auto; overscroll-behavior:contain; animation:wfMenuMuncul .14s ease; }
    .wf-menu[hidden] { display:none; }
    @keyframes wfMenuMuncul { from { opacity:0; transform:translateY(-4px); } to { opacity:1; transform:none; } }
    @media (prefers-reduced-motion: reduce) { .wf-menu { animation:none; } }
    .wf-menu-judul { padding:.4rem .7rem .35rem; font-size:.66rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:var(--soft); }
    .wf-opt { display:flex; align-items:center; gap:.6rem; min-height:2.5rem; padding:.45rem .7rem; border-radius:.6rem;
        font-size:.85rem; font-weight:600; color:#334155; cursor:pointer; user-select:none; }
    .wf-opt + .wf-opt { margin-top:1px; }
    .wf-opt .teks { flex:1 1 auto; min-width:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .wf-opt .centang { flex:none; font-size:.95rem; color:var(--primary); visibility:hidden; }
    .wf-opt.fokus { background:var(--surface-2); color:var(--ink); }
    .wf-opt.terpilih { color:var(--primary-dark); font-weight:800; background:var(--primary-soft); }
    .wf-opt.terpilih.fokus { background:var(--primary-softer); }
    .wf-opt.terpilih .centang { visibility:visible; }
    .wf-opt[aria-disabled="true"] { color:var(--soft); cursor:not-allowed; }
    .wf-dot { width:.55rem; height:.55rem; border-radius:50%; flex:none; background:var(--soft); }
    .wf-dot.semua { background:transparent; box-shadow:inset 0 0 0 2px var(--soft); }
    .wf-dot.menunggu { background:#f59e0b; } .wf-dot.disetujui { background:var(--emerald); }
    .wf-dot.ditolak { background:var(--rose); } .wf-dot.dibatalkan { background:#94a3b8; }
    .wf-dot.selesai { background:#3b82f6; } .wf-dot.kadaluwarsa { background:#8b5cf6; }

    /* ══════ CHIP FILTER AKTIF ══════ */
    .wf-chips { display:flex; align-items:center; flex-wrap:wrap; gap:.4rem; margin-top:.95rem; padding-top:.85rem; border-top:1px solid var(--line-soft); }
    .wf-chips[hidden] { display:none; }
    .wf-chips-lbl { font-size:.74rem; font-weight:700; color:var(--muted); margin-right:.15rem; }
    .wf-chip { display:inline-flex; align-items:center; gap:.35rem; max-width:100%; min-height:1.9rem; padding:.2rem .35rem .2rem .7rem;
        border:1px solid var(--primary-softer); background:var(--primary-soft); color:var(--primary-dark);
        border-radius:2rem; font:inherit; font-size:.76rem; font-weight:700; cursor:pointer; transition:background .15s ease, border-color .15s ease; }
    .wf-chip span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .wf-chip span b { font-weight:600; opacity:.75; }
    .wf-chip i { display:grid; place-items:center; width:1.3rem; height:1.3rem; border-radius:50%; font-size:.85rem; flex:none; }
    .wf-chip:hover { border-color:var(--primary); }
    .wf-chip:hover i { background:var(--primary); color:#fff; }

    @media (max-width: 575.98px) {
        .wf-filter { padding:1rem; }
        .wf-filter-title small { display:none; }
        .wf-control, .wf-select-btn, select[data-wf-select] { font-size:1rem; } /* cegah zoom otomatis iOS saat fokus */
        .wf-opt { min-height:2.75rem; font-size:.92rem; }
    }
</style>
<script>
(function () {
    if (window.WaduhSelect) return;

    let aktif = null; // instance dropdown yang sedang terbuka
    let urut = 0;

    const tutupAktif = (fokuskan) => { if (aktif) aktif.tutup(fokuskan); };

    function pasang(select) {
        if (select.dataset.wfSiap) return;
        select.dataset.wfSiap = '1';
        const id = 'wfsel' + (++urut);

        const wrap = document.createElement('div');
        wrap.className = 'wf-select';
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');

        const label = select.id ? document.querySelector('label[for="' + select.id + '"]') : null;
        if (label && !label.id) label.id = id + 'lbl';

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'wf-select-btn';
        btn.id = id + 'btn';
        btn.setAttribute('role', 'combobox');
        btn.setAttribute('aria-haspopup', 'listbox');
        btn.setAttribute('aria-expanded', 'false');
        btn.setAttribute('aria-controls', id + 'menu');
        btn.innerHTML = '<span class="wf-select-lead" aria-hidden="true"></span><span class="wf-select-val" id="' + id + 'val"></span>'
            + '<i class="bi bi-chevron-down wf-select-caret" aria-hidden="true"></i>';
        if (label) btn.setAttribute('aria-labelledby', label.id + ' ' + id + 'val');
        wrap.appendChild(btn);
        // Label mengarah ke <select> asli (tersembunyi) — alihkan kliknya ke tombol pemicu.
        label?.addEventListener('click', (e) => { e.preventDefault(); btn.focus(); });

        const lead = btn.querySelector('.wf-select-lead');
        const val = btn.querySelector('.wf-select-val');

        const menu = document.createElement('div');
        menu.className = 'wf-menu';
        menu.id = id + 'menu';
        menu.setAttribute('role', 'listbox');
        menu.hidden = true;
        if (label) menu.setAttribute('aria-labelledby', label.id);

        let items = [];
        let fokus = -1;
        let ketik = '', ketikTimer = null;

        const setFokus = (i, gulir) => {
            fokus = i;
            items.forEach((el, n) => el.classList.toggle('fokus', n === i));
            if (i >= 0) {
                btn.setAttribute('aria-activedescendant', items[i].id);
                // Gulir DI DALAM panel saja (bukan scrollIntoView, yang bisa ikut menggeser halaman).
                if (gulir) {
                    const el = items[i];
                    const atas = el.offsetTop, bawah = atas + el.offsetHeight;
                    if (atas < menu.scrollTop) menu.scrollTop = atas - 6;
                    else if (bawah > menu.scrollTop + menu.clientHeight) menu.scrollTop = bawah - menu.clientHeight + 6;
                }
            } else {
                btn.removeAttribute('aria-activedescendant');
            }
        };

        const render = () => {
            const dipilih = select.options[select.selectedIndex];
            val.textContent = dipilih ? dipilih.text : '';
            btn.title = dipilih ? dipilih.text : '';

            const dot = dipilih?.dataset.dot;
            const ikon = dipilih?.dataset.ikon || select.dataset.ikon;
            lead.innerHTML = dot ? '<span class="wf-dot ' + dot + '"></span>' : (ikon ? '<i class="bi ' + ikon + '"></i>' : '');
            lead.classList.toggle('tampil', !!(dot || ikon));
            wrap.classList.toggle('ada-nilai', !select.hasAttribute('data-wf-netral') && !!dipilih && dipilih.value !== '');

            menu.innerHTML = '';
            if (label) {
                const judul = document.createElement('div');
                judul.className = 'wf-menu-judul';
                judul.setAttribute('aria-hidden', 'true');
                judul.textContent = label.textContent.trim();
                menu.appendChild(judul);
            }
            items = [...select.options].map((opt, i) => {
                const el = document.createElement('div');
                el.className = 'wf-opt' + (i === select.selectedIndex ? ' terpilih' : '');
                el.id = id + 'opt' + i;
                el.setAttribute('role', 'option');
                el.setAttribute('aria-selected', i === select.selectedIndex ? 'true' : 'false');
                if (opt.disabled) el.setAttribute('aria-disabled', 'true');
                if (opt.dataset.dot) {
                    const d = document.createElement('span');
                    d.className = 'wf-dot ' + opt.dataset.dot;
                    el.appendChild(d);
                }
                const teks = document.createElement('span');
                teks.className = 'teks';
                teks.textContent = opt.text;
                el.appendChild(teks);
                const centang = document.createElement('i');
                centang.className = 'bi bi-check-lg centang';
                centang.setAttribute('aria-hidden', 'true');
                el.appendChild(centang);

                el.addEventListener('mousemove', () => { if (fokus !== i && !opt.disabled) setFokus(i, false); });
                el.addEventListener('click', (e) => { e.stopPropagation(); if (!opt.disabled) pilih(i); });
                menu.appendChild(el);
                return el;
            });
        };

        const posisi = () => {
            const r = btn.getBoundingClientRect();
            const vw = document.documentElement.clientWidth;
            const vh = window.innerHeight;
            const tepi = 16;
            const gulirSebelum = menu.scrollTop; // mengukur tinggi asli mereset gulir — kembalikan di akhir

            menu.style.maxHeight = 'none';
            menu.style.minWidth = Math.min(r.width, vw - tepi * 2) + 'px';
            menu.style.width = 'max-content';
            const lebar = Math.min(menu.offsetWidth, 352, vw - tepi * 2);
            menu.style.width = lebar + 'px';

            // Rata kiri dengan tombol; kalau mepet tepi kanan layar, rata kanan.
            let kiri = r.left;
            if (kiri + lebar > vw - tepi) kiri = r.right - lebar;
            kiri = Math.max(tepi, Math.min(kiri, vw - tepi - lebar));

            const tinggi = menu.scrollHeight + 2;
            const bawah = vh - r.bottom - 12;
            const atas = r.top - 12;
            const keBawah = bawah >= Math.min(tinggi, 220) || bawah >= atas;
            const maks = Math.max(140, Math.min(336, (keBawah ? bawah : atas) - 6));
            menu.style.maxHeight = maks + 'px';
            menu.style.left = kiri + 'px';
            menu.style.top = (keBawah ? r.bottom + 6 : r.top - 6 - Math.min(tinggi, maks)) + 'px';
            menu.scrollTop = gulirSebelum;

        };

        const diGulir = (e) => { if (e.target !== menu && !menu.contains(e.target)) posisi(); };

        const inst = {
            buka() {
                if (aktif === inst) return;
                tutupAktif(false);
                aktif = inst;
                render();
                document.body.appendChild(menu);
                menu.hidden = false;
                wrap.classList.add('buka');
                btn.setAttribute('aria-expanded', 'true');
                posisi();
                setFokus(select.selectedIndex, false);
                // Opsi terpilih ditampilkan di tengah panel saat daftar panjang (mis. 12 bulan).
                const terpilih = items[select.selectedIndex];
                if (terpilih) menu.scrollTop = Math.max(0, terpilih.offsetTop - (menu.clientHeight - terpilih.offsetHeight) / 2);
                window.addEventListener('resize', posisi);
                window.addEventListener('scroll', diGulir, true);
            },
            tutup(fokuskan) {
                if (aktif !== inst) return;
                aktif = null;
                menu.hidden = true;
                menu.remove();
                wrap.classList.remove('buka');
                btn.setAttribute('aria-expanded', 'false');
                setFokus(-1, false);
                window.removeEventListener('resize', posisi);
                window.removeEventListener('scroll', diGulir, true);
                if (fokuskan) btn.focus();
            },
            wrap, menu,
        };

        function pilih(i) {
            const berubah = select.selectedIndex !== i;
            select.selectedIndex = i;
            inst.tutup(true);
            render();
            if (berubah) {
                select.dispatchEvent(new Event('input', { bubbles: true }));
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        const geser = (arah) => {
            const n = items.length;
            let i = fokus;
            for (let k = 0; k < n; k++) {
                i = (i + arah + n) % n;
                if (!select.options[i].disabled) { setFokus(i, true); return; }
            }
        };

        btn.addEventListener('click', (e) => { e.stopPropagation(); aktif === inst ? inst.tutup(false) : inst.buka(); });

        btn.addEventListener('keydown', (e) => {
            const terbuka = aktif === inst;
            switch (e.key) {
                case 'ArrowDown': e.preventDefault(); terbuka ? geser(1) : inst.buka(); break;
                case 'ArrowUp': e.preventDefault(); terbuka ? geser(-1) : inst.buka(); break;
                case 'Home': if (terbuka) { e.preventDefault(); setFokus(-1, false); geser(1); } break;
                case 'End': if (terbuka) { e.preventDefault(); setFokus(0, false); geser(-1); } break;
                case 'Enter': case ' ':
                    e.preventDefault();
                    if (!terbuka) inst.buka(); else if (fokus >= 0) pilih(fokus);
                    break;
                case 'Escape': if (terbuka) { e.preventDefault(); e.stopPropagation(); inst.tutup(true); } break;
                case 'Tab': if (terbuka) inst.tutup(false); break;
                default:
                    // Ketik huruf/angka → lompat ke opsi yang diawali ketikan itu.
                    if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
                        ketik += e.key.toLowerCase();
                        clearTimeout(ketikTimer);
                        ketikTimer = setTimeout(() => { ketik = ''; }, 600);
                        const i = [...select.options].findIndex((o) => !o.disabled && o.text.trim().toLowerCase().startsWith(ketik));
                        if (i >= 0) { if (!terbuka) inst.buka(); setFokus(i, true); }
                    }
            }
        });

        // Nilai <select> diubah dari luar (tombol atur ulang, chip filter, skrip halaman).
        ['change', 'wf:sync', 'sync-fancy'].forEach((ev) => select.addEventListener(ev, render));
        render();
    }

    document.addEventListener('click', (e) => {
        if (aktif && !aktif.wrap.contains(e.target) && !aktif.menu.contains(e.target)) tutupAktif(false);
    });

    // ─── Chip filter aktif ───
    function pasangChips(wadah) {
        const form = wadah.closest('form');
        if (!form || wadah.dataset.wfSiap) return;
        wadah.dataset.wfSiap = '1';
        const kolom = [...form.querySelectorAll('[data-wf-chip]')];

        const nilaiTampil = (el) => {
            if (el.tagName === 'SELECT') return el.value !== '' ? el.options[el.selectedIndex].text : '';
            if (el.type === 'date' && el.value) {
                const [y, m, d] = el.value.split('-').map(Number);
                return new Date(Date.UTC(y, m - 1, d)).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' });
            }
            return el.value.trim();
        };

        // Chip hanya dibangun ulang kalau isinya memang berubah. Tanpa ini, event `change` yang
        // muncul saat kolom cari kehilangan fokus (tepat ketika chip ditekan) mengganti elemen
        // chip di antara mousedown dan mouseup, sehingga kliknya hilang dan harus diulang.
        let sidik = null;
        const render = () => {
            const nilai = kolom.map((el) => nilaiTampil(el));
            kolom.forEach((el, i) => {
                if (el.type === 'date' || el.type === 'search' || el.type === 'text') el.classList.toggle("ada-nilai", !!nilai[i]);
            });
            const sidikBaru = JSON.stringify(nilai);
            if (sidikBaru === sidik) return;
            sidik = sidikBaru;

            wadah.innerHTML = '';
            kolom.forEach((el, i) => {
                const teks = nilai[i];
                if (!teks) return;

                const chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'wf-chip';
                chip.setAttribute('aria-label', 'Hapus filter ' + el.dataset.wfChip + ': ' + teks);
                const isi = document.createElement('span');
                const nama = document.createElement('b');
                nama.textContent = el.dataset.wfChip + ': ';
                isi.appendChild(nama);
                isi.appendChild(document.createTextNode(teks));
                chip.appendChild(isi);
                chip.insertAdjacentHTML('beforeend', '<i class="bi bi-x" aria-hidden="true"></i>');
                chip.addEventListener('click', () => {
                    if (el.tagName === 'SELECT') el.selectedIndex = 0; else el.value = '';
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                });
                wadah.appendChild(chip);
            });
            if (wadah.children.length) {
                const lbl = document.createElement('span');
                lbl.className = 'wf-chips-lbl';
                lbl.textContent = 'Filter aktif:';
                wadah.prepend(lbl);
            }
            wadah.hidden = wadah.children.length === 0;
        };

        form.addEventListener('input', render);
        form.addEventListener('change', render);
        form.addEventListener('wf:refresh', () => {
            form.querySelectorAll('select[data-wf-select]').forEach((s) => s.dispatchEvent(new Event('wf:sync')));
            render();
        });
        render();
    }

    const init = (root) => {
        (root || document).querySelectorAll('select[data-wf-select]').forEach(pasang);
        (root || document).querySelectorAll('[data-wf-chips]').forEach(pasangChips);
    };

    window.WaduhSelect = { init, pasang };
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', () => init()) : init();
})();
</script>
@endonce
