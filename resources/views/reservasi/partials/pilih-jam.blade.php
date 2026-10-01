{{--
    Pemilih jam (jam operasional gedung 08.00–16.00) — dropdown berisi grid jam.
    Variabel: $name ('jam_mulai' | 'jam_selesai'), $value (terpilih, format "08:00"),
              $required (default true), $kecil (ukuran kecil), $kanan (panel rata kanan),
              $fasilitasId (HANYA utk name="jam_mulai" — sumber data jam terisi per tanggal),
              $antrian (opsional, id ruangan lain pada multi-pilih, dipisah koma),

              $terisiAwal (opsional, rentang jam terisi awal dari server:
              array of ['mulai' => 'HH:MM', 'selesai' => 'HH:MM']).

    Jam yang sudah terisi reservasi lain (dari database) tidak dapat dipilih:
      - Jam Mulai   : jam yang berada di dalam rentang terisi.
      - Jam Selesai : jam yang membuat rentang pemakaian menabrak rentang terisi.
--}}
@php
    $required = $required ?? true;
    $kecil = $kecil ?? false;
    $kanan = $kanan ?? false;
    $value = $value ? substr($value, 0, 5) : '';
    $labelTerpilih = $value ? str_replace(':', '.', $value) : null;
    $fasilitasId = $fasilitasId ?? null;
    $terisiAwal = $terisiAwal ?? [];
    // $antrian (opsional): ruangan lain pada multi-pilih — jam terisi digabung dari semua ruangan.
    $terisiUrl = $fasilitasId
        ? route('reservasi.fasilitas.jam-terisi', array_filter(['fasilitas' => $fasilitasId, 'antrian' => $antrian ?? null]))
        : '';
    // Kedua pemilih menampilkan seluruh jam operasional 08.00–16.00; jam yang tidak mungkin
    // (mulai pukul 16.00 / selesai pukul 08.00) dinonaktifkan oleh skrip di bawah.
    $jamAwal = 8;
    $jamAkhir = 16;
@endphp
<div class="jampicker {{ $kecil ? 'jam-kecil' : '' }} {{ $kanan ? 'jam-kanan' : '' }}" data-jampicker
     data-terisi-url="{{ $terisiUrl }}" data-terisi-awal="{{ json_encode(array_values($terisiAwal)) }}">
    <input type="hidden" name="{{ $name }}" value="{{ $value }}" @if($required) required @endif>
    <button type="button" class="form-control {{ $kecil ? 'form-control-sm' : '' }} jam-btn {{ $labelTerpilih ? '' : 'jam-kosong' }}"
            aria-haspopup="listbox" aria-expanded="false">
        <i class="bi bi-clock"></i><span class="jam-label">{{ $labelTerpilih ?? 'Pilih jam' }}</span>
        <i class="bi bi-chevron-down jam-caret"></i>
    </button>
    <div class="jam-panel" role="listbox">
        <div class="jam-head">{{ $name === 'jam_selesai' ? 'Jam selesai' : 'Jam mulai' }}</div>
        <div class="jam-terisi-hint" data-jam-terisi-hint hidden><i class="bi bi-lock-fill"></i><span></span></div>
        <div class="jam-grid">
            @for ($h = $jamAwal; $h <= $jamAkhir; $h++)
                @php $v = sprintf('%02d:00', $h); @endphp
                <button type="button" role="option" class="jam-opt {{ $value === $v ? 'aktif' : '' }}" data-val="{{ $v }}">{{ sprintf('%02d.00', $h) }}</button>
            @endfor
        </div>
        <div class="jam-legend"><span class="sw sw-terisi"></span>Terisi<span class="sw sw-lewat"></span>Tidak dapat dipilih</div>
    </div>
</div>

@once
<style>
    .jampicker { position:relative; }
    .jam-btn { display:flex; align-items:center; gap:.45rem; text-align:left; background:#fff; cursor:pointer;
        min-height:2.75rem; border:1px solid var(--line); border-radius:.7rem; font-size:.9rem; }
    .jam-btn > .bi-clock { color:var(--muted); }
    .jam-btn .jam-label { flex:1; font-weight:600; color:var(--ink); }
    .jam-btn.jam-kosong .jam-label { color:var(--soft); font-weight:500; }
    .jam-btn .jam-caret { font-size:.7rem; color:var(--soft); transition:transform .2s; }
    .jampicker.buka .jam-btn { border-color:var(--primary); box-shadow:0 0 0 3px rgba(23,107,135,.12); }
    .jampicker.buka .jam-caret { transform:rotate(180deg); }
    .jam-kecil .jam-btn { min-height:2.4rem; font-size:.85rem; }

    .jam-panel { display:none; position:absolute; top:calc(100% + 6px); left:0; z-index:1050;
        width:max(100%, 15rem); max-width:calc(100vw - 2rem);
        background:#fff; border:1px solid var(--line); border-radius:.9rem; box-shadow:0 18px 40px rgba(21,36,59,.18);
        padding:.7rem; animation:jamMuncul .16s ease; }
    .jampicker.buka .jam-panel { display:block; }
    @media (min-width: 480px) { .jam-kanan .jam-panel { left:auto; right:0; } }
    @keyframes jamMuncul { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }
    .jam-head { font-size:.68rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:var(--muted); padding:0 .15rem .5rem; }
    .jam-terisi-hint { display:flex; align-items:flex-start; gap:.35rem; font-size:.72rem; font-weight:600; color:#9a3412;
        background:#fff7ed; border:1px solid #fed7aa; border-radius:.5rem; padding:.4rem .55rem; margin:0 0 .55rem; line-height:1.4; }
    .jam-terisi-hint[hidden] { display:none; }
    .jam-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:.35rem; }
    .jam-opt { border:1px solid var(--line); background:#fff; border-radius:.55rem; padding:.5rem .2rem; font-family:inherit;
        font-weight:700; font-size:.82rem; color:var(--ink); cursor:pointer; transition:border-color .12s, background .12s, color .12s; }
    .jam-opt:hover:not(:disabled) { border-color:var(--primary); background:var(--primary-soft); color:var(--primary-dark); }
    .jam-opt.aktif { background:var(--primary); border-color:var(--primary); color:#fff; }
    .jam-opt:disabled { cursor:not-allowed; color:#a3adba; background:var(--surface); }
    .jam-opt.opt-terisi:disabled { color:#b45309; text-decoration:line-through;
        background:repeating-linear-gradient(45deg, #fff7ed, #fff7ed 4px, #ffedd5 4px, #ffedd5 8px); border-color:#fed7aa; }
    .jam-legend { display:flex; align-items:center; gap:.35rem; font-size:.66rem; font-weight:600; color:var(--muted); margin-top:.55rem; }
    .jam-legend .sw { width:.7rem; height:.7rem; border-radius:.2rem; display:inline-block; border:1px solid var(--line); }
    .jam-legend .sw + .sw, .jam-legend .sw-lewat { margin-left:.5rem; }
    .jam-legend .sw-terisi { background:#ffedd5; border-color:#fed7aa; }
    .jam-legend .sw-lewat { background:var(--surface); }
</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tutupSemua = () => document.querySelectorAll('[data-jampicker].buka').forEach((p) => {
            p.classList.remove('buka');
            p.querySelector('.jam-btn')?.setAttribute('aria-expanded', 'false');
        });
        const titik = (j) => j.replace(':', '.');

        // Satu "grup" per form: pemilih Jam Mulai + Jam Selesai berbagi data jam terisi milik
        // tanggal yang sedang dipilih, dan saling memengaruhi pilihan yang masih sah.
        const grup = new Map();
        document.querySelectorAll('[data-jampicker]').forEach((picker) => {
            const form = picker.closest('form');
            if (!form) return;
            if (!grup.has(form)) grup.set(form, { pickers: {}, terisi: [], url: '' });
            const g = grup.get(form);
            const hidden = picker.querySelector('input[type="hidden"]');
            g.pickers[hidden.name] = picker;
            if (picker.dataset.terisiUrl) {
                g.url = picker.dataset.terisiUrl;
                try { g.terisi = JSON.parse(picker.dataset.terisiAwal || '[]'); } catch (e) { g.terisi = []; }
            }
        });

        const kosongkan = (picker) => {
            const hidden = picker.querySelector('input[type="hidden"]');
            if (!hidden.value) return;
            hidden.value = '';
            picker.querySelector('.jam-label').textContent = 'Pilih jam';
            picker.querySelector('.jam-btn').classList.add('jam-kosong');
            picker.querySelectorAll('.jam-opt.aktif').forEach((o) => o.classList.remove('aktif'));
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
        };

        const segarkan = (form) => {
            const g = grup.get(form);
            const inputTanggal = form.querySelector('input[name="tanggal_mulai"]');
            const now = new Date();
            const hariIni = now.toLocaleDateString('sv-SE'); // YYYY-MM-DD lokal
            const jamSekarang = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
            const adalahHariIni = !!inputTanggal && inputTanggal.value === hariIni;
            const seharian = g.terisi.some((r) => r.mulai <= '08:00' && r.selesai >= '16:00');
            const mulai = g.pickers.jam_mulai?.querySelector('input[type="hidden"]').value || '';

            Object.entries(g.pickers).forEach(([nama, picker]) => {
                let pilihanGugur = false;
                picker.querySelectorAll('.jam-opt').forEach((opt) => {
                    const v = opt.dataset.val;
                    let terisi, lain;
                    if (nama === 'jam_mulai') {
                        terisi = g.terisi.some((r) => v >= r.mulai && v < r.selesai);
                        lain = v >= '16:00' || (adalahHariIni && v < jamSekarang);
                    } else {
                        // Rentang [mulai, v] tidak boleh menabrak blok terisi mana pun.
                        terisi = mulai
                            ? g.terisi.some((r) => r.mulai < v && r.selesai > mulai)
                            : g.terisi.some((r) => v > r.mulai && v <= r.selesai);
                        lain = v <= '08:00' || (mulai && v <= mulai) || (adalahHariIni && v <= jamSekarang);
                    }
                    opt.disabled = terisi || lain;
                    opt.classList.toggle('opt-terisi', terisi);
                    opt.title = terisi ? 'Sudah terisi reservasi lain'
                        : (nama === 'jam_mulai' && v >= '16:00' ? 'Pukul 16.00 adalah batas jam selesai'
                        : (nama === 'jam_selesai' && v <= '08:00' ? 'Pukul 08.00 adalah jam mulai paling awal' : ''));
                    if (opt.disabled && opt.classList.contains('aktif')) pilihanGugur = true;
                });

                const hint = picker.querySelector('[data-jam-terisi-hint]');
                if (hint) {
                    hint.hidden = g.terisi.length === 0;
                    hint.querySelector('span').textContent = seharian
                        ? 'Tanggal ini sudah terisi sepanjang hari.'
                        : 'Jam terisi: ' + g.terisi.map((r) => titik(r.mulai) + '–' + titik(r.selesai)).join(', ');
                }
                if (pilihanGugur) kosongkan(picker);
            });
        };

        const muat = (form) => {
            const g = grup.get(form);
            const inputTanggal = form.querySelector('input[name="tanggal_mulai"]');
            if (!g.url) { segarkan(form); return; }
            if (!inputTanggal?.value) { g.terisi = []; segarkan(form); return; }
            fetch(g.url + (g.url.includes('?') ? '&' : '?') + 'tanggal=' + encodeURIComponent(inputTanggal.value), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then((r) => r.ok ? r.json() : [])
                .then((data) => { g.terisi = Array.isArray(data) ? data : []; segarkan(form); })
                .catch(() => segarkan(form));
        };

        grup.forEach((g, form) => {
            segarkan(form);
            muat(form);
            form.querySelector('input[name="tanggal_mulai"]')?.addEventListener('change', () => muat(form));

            Object.entries(g.pickers).forEach(([nama, picker]) => {
                const tombol = picker.querySelector('.jam-btn');
                const hidden = picker.querySelector('input[type="hidden"]');
                const label = picker.querySelector('.jam-label');

                tombol.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const terbuka = picker.classList.contains('buka');
                    tutupSemua();
                    if (!terbuka) { picker.classList.add('buka'); tombol.setAttribute('aria-expanded', 'true'); }
                });

                picker.querySelectorAll('.jam-opt').forEach((opt) => {
                    opt.addEventListener('click', (e) => {
                        e.stopPropagation();
                        if (opt.disabled) return;
                        hidden.value = opt.dataset.val;
                        label.textContent = titik(opt.dataset.val);
                        tombol.classList.remove('jam-kosong');
                        picker.querySelectorAll('.jam-opt').forEach((o) => o.classList.toggle('aktif', o === opt));
                        tutupSemua();
                        hidden.dispatchEvent(new Event('input', { bubbles: true }));
                        hidden.dispatchEvent(new Event('change', { bubbles: true }));
                        if (nama === 'jam_mulai') segarkan(form); // jam selesai yang sah ikut berubah
                    });
                });
            });
        });

        document.addEventListener('click', tutupSemua);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') tutupSemua(); });
    });
</script>
@endonce
