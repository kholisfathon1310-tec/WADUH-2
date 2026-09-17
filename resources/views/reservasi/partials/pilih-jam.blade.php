{{--
    Pemilih jam Indonesia — dropdown custom yang SELALU membuka ke bawah,
    berisi grid pill "07.00 WIB" s/d "21.00 WIB".
    Variabel: $name, $value (terpilih, format "08:00"), $required (default true), $kecil (ukuran kecil),
              $fasilitasId (opsional, HANYA utk name="jam_mulai" — supaya jam yang sudah terisi
              ditandai & tidak bisa diklik), $terisiAwal (opsional, rentang jam terisi awal dari server:
              array of ['mulai' => 'HH:MM', 'selesai' => 'HH:MM']).
--}}
@php
    $required = $required ?? true;
    $kecil = $kecil ?? false;
    $value = $value ?? '';
    $labelTerpilih = $value ? str_replace(':', '.', $value) : null;
    $fasilitasId = $fasilitasId ?? null;
    $terisiAwal = $terisiAwal ?? [];
    $terisiUrl = $fasilitasId ? route('reservasi.fasilitas.jam-terisi', $fasilitasId) : '';
@endphp
<div class="jampicker {{ $kecil ? 'jam-kecil' : '' }}" data-jampicker
     data-terisi-url="{{ $terisiUrl }}" data-terisi-awal="{{ json_encode(array_values($terisiAwal)) }}">
    <input type="hidden" name="{{ $name }}" value="{{ $value }}" @if($required) required @endif>
    <button type="button" class="form-control {{ $kecil ? 'form-control-sm' : '' }} jam-btn {{ $labelTerpilih ? '' : 'jam-kosong' }}">
        <i class="bi bi-clock me-1"></i><span class="jam-label">{{ $labelTerpilih ?? 'Pilih jam' }}</span>
        <i class="bi bi-chevron-down jam-caret"></i>
    </button>
    <div class="jam-panel">
        <div class="jam-head"><i class="bi bi-clock-history me-1"></i>Jam operasional 08.00–16.00</div>
        <div class="jam-terisi-hint" data-jam-terisi-hint hidden><i class="bi bi-lock-fill me-1"></i><span></span></div>
        <div class="jam-grid">
            @for ($h = 8; $h <= 16; $h++)
                @php $v = sprintf('%02d:00', $h); @endphp
                <button type="button" class="jam-opt {{ $value === $v ? 'aktif' : '' }}" data-val="{{ $v }}">{{ sprintf('%02d.00', $h) }}</button>
            @endfor
        </div>
    </div>
</div>

@once
<style>
    .jampicker { position:relative; }
    .jam-btn { display:flex; align-items:center; gap:.25rem; text-align:left; background:#fff; cursor:pointer; }
    .jam-btn .jam-label { flex:1; font-weight:600; color:var(--ink); }
    .jam-btn.jam-kosong .jam-label { color:#8a97a5; font-weight:500; }
    .jam-btn .jam-caret { font-size:.7rem; color:#8a97a5; transition:transform .2s; }
    .jampicker.buka .jam-btn { border-color:var(--primary); box-shadow:0 0 0 .2rem rgba(14,107,125,.12); }
    .jampicker.buka .jam-caret { transform:rotate(180deg); }

    /* Panel SELALU membuka ke bawah */
    .jam-panel { display:none; position:absolute; top:calc(100% + 6px); left:0; z-index:1050; min-width:238px;
        background:#fff; border:1px solid #dbe6ee; border-radius:.9rem; box-shadow:0 18px 40px rgba(21,36,59,.18);
        padding:.6rem; animation:jamMuncul .16s ease; }
    .jampicker.buka .jam-panel { display:block; }
    @keyframes jamMuncul { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }
    .jam-head { font-size:.72rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:#5b7286; padding:0 .25rem .45rem; }
    .jam-terisi-hint { display:flex; align-items:flex-start; gap:.3rem; font-size:.7rem; font-weight:600; color:#b3541e;
        background:#fef3e8; border:1px solid #f5d9bd; border-radius:.5rem; padding:.35rem .5rem; margin:0 .25rem .5rem; line-height:1.35; }
    .jam-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:.35rem; max-height:222px; overflow-y:auto; padding-right:.15rem; }
    .jam-grid::-webkit-scrollbar { width:6px; } .jam-grid::-webkit-scrollbar-thumb { background:#cfdde8; border-radius:3px; }
    .jam-opt { border:1.5px solid #dbe6ee; background:#f8fbfd; border-radius:.6rem; padding:.42rem .25rem;
        font-weight:700; font-size:.85rem; color:#33475c; cursor:pointer; transition:all .12s; }
    .jam-opt:hover { border-color:var(--teal); background:#e9faf3; color:#0d8a5f; transform:translateY(-1px); }
    .jam-opt.aktif { background:var(--primary); border-color:var(--primary); color:#fff; box-shadow:0 6px 14px rgba(14,107,125,.3); }
    .jam-opt:disabled { opacity:.4; cursor:not-allowed; background:#f1f4f7; }
    .jam-opt:disabled:hover { border-color:#dbe6ee; background:#f1f4f7; color:#33475c; transform:none; }
    .jam-opt.opt-terisi:disabled { background:repeating-linear-gradient(45deg, #f1f4f7, #f1f4f7 4px, #e7ebee 4px, #e7ebee 8px); }
    .jam-kecil .jam-panel { min-width:210px; }
</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tutupSemua = () => document.querySelectorAll('[data-jampicker].buka').forEach(p => p.classList.remove('buka'));

        // Jam mulai tidak boleh dipilih kalau: (a) sudah lewat waktu sekarang — KALAU tanggal
        // pemakaian yang dipilih adalah hari ini, ATAU (b) sudah terisi reservasi lain pada
        // tanggal yang dipilih (rentang `terisi`, di-refresh dari server tiap tanggal diganti,
        // lihat listener 'change' di bawah) — supaya kelihatan jelas jam yang bentrok & sampai
        // jam berapa (lewat highlight + teks "Sudah terisi: ...").
        const perbaruiJamPicker = (picker, hidden, label, tombol, terisi) => {
            const hiddenName = hidden.getAttribute('name');
            if (hiddenName !== 'jam_mulai') return;

            const form = picker.closest('form');
            const inputTanggal = form ? form.querySelector('input[name="tanggal_mulai"]') : null;

            const now = new Date();
            const hariIni = now.toLocaleDateString('sv-SE'); // format YYYY-MM-DD lokal, tanpa geser zona waktu
            const jamSekarang = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
            const adalahHariIni = !! inputTanggal && inputTanggal.value === hariIni;

            let pilihanTerhapus = false;
            picker.querySelectorAll('.jam-opt').forEach(opt => {
                const lewat = adalahHariIni && opt.dataset.val < jamSekarang;
                const terisiJam = terisi.some(r => opt.dataset.val >= r.mulai && opt.dataset.val < r.selesai);
                opt.disabled = lewat || terisiJam;
                opt.classList.toggle('opt-terisi', terisiJam);
                opt.title = terisiJam ? 'Sudah dipesan, tidak bisa dipilih' : '';
                if (opt.disabled && opt.classList.contains('aktif')) pilihanTerhapus = true;
            });

            const hint = picker.querySelector('[data-jam-terisi-hint]');
            if (hint) {
                if (terisi.length) {
                    hint.hidden = false;
                    hint.querySelector('span').textContent = 'Sudah terisi: '
                        + terisi.map(r => r.mulai.replace(':', '.') + '–' + r.selesai.replace(':', '.')).join(', ');
                } else {
                    hint.hidden = true;
                }
            }

            if (pilihanTerhapus) {
                hidden.value = '';
                label.textContent = 'Pilih jam';
                tombol.classList.add('jam-kosong');
                picker.querySelectorAll('.jam-opt.aktif').forEach(o => o.classList.remove('aktif'));
            }
        };

        document.querySelectorAll('[data-jampicker]').forEach(picker => {
            const tombol = picker.querySelector('.jam-btn');
            const hidden = picker.querySelector('input[type="hidden"]');
            const label = picker.querySelector('.jam-label');
            const terisiUrl = picker.dataset.terisiUrl;
            let terisi = [];
            try { terisi = JSON.parse(picker.dataset.terisiAwal || '[]'); } catch (e) { terisi = []; }

            const refresh = () => perbaruiJamPicker(picker, hidden, label, tombol, terisi);
            refresh();

            const form = picker.closest('form');
            const inputTanggal = form ? form.querySelector('input[name="tanggal_mulai"]') : null;
            inputTanggal?.addEventListener('change', () => {
                if (! terisiUrl) { refresh(); return; }
                fetch(terisiUrl + '?tanggal=' + encodeURIComponent(inputTanggal.value), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.ok ? r.json() : [])
                    .then(data => { terisi = Array.isArray(data) ? data : []; refresh(); })
                    .catch(() => refresh());
            });

            tombol.addEventListener('click', e => {
                e.stopPropagation();
                const buka = picker.classList.contains('buka');
                tutupSemua();
                if (! buka) picker.classList.add('buka');
            });

            picker.querySelectorAll('.jam-opt').forEach(opt => {
                opt.addEventListener('click', e => {
                    e.stopPropagation();
                    if (opt.disabled) return;
                    hidden.value = opt.dataset.val;
                    label.textContent = opt.dataset.val.replace(':', '.');
                    tombol.classList.remove('jam-kosong');
                    picker.querySelectorAll('.jam-opt').forEach(o => o.classList.toggle('aktif', o === opt));
                    picker.classList.remove('buka');
                    hidden.dispatchEvent(new Event('input', { bubbles: true })); // bersihkan tanda error
                });
            });
        });

        document.addEventListener('click', tutupSemua);
    });
</script>
@endonce
