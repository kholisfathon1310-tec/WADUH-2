{{--
    <x-denah.schedule-filter> — ScheduleFilter: form GET Tanggal/Cek Ketersediaan.
    Sama persis secara fungsional dengan form lama (method, name field, action default
    ke URL saat ini) — hanya tampilan & susunan yang dirapikan.
    Props:
      - jenisId    : id_jenis_sewa terpilih (dikirim balik sebagai hidden input)
      - sehariSaja : true = Convention Hall / Per Jam, cukup satu tanggal (otomatis 8 jam)
      - jadwal     : ['tanggal_mulai' => ..., 'tanggal_selesai' => ..., 'jam_mulai' => ..., 'jam_selesai' => ...]
                     (dinamai "jadwal", BUKAN "slot" — "slot" adalah nama variabel bawaan Blade
                     untuk konten slot komponen, jadi tidak bisa dipakai sebagai nama prop)
--}}
@props(['jenisId' => null, 'sehariSaja' => false, 'jadwal'])

<form method="GET" class="dn-filter" data-filter-form>
    <div class="dn-filter-head">
        <span class="dn-filter-ic"><i class="bi bi-funnel"></i></span>
        <div>
            <div class="dn-filter-title">Filter Jadwal</div>
            <div class="dn-filter-sub">Cek ketersediaan ruangan pada tanggal pilihan Anda</div>
        </div>
    </div>
    <input type="hidden" name="jenis" value="{{ $jenisId }}">
    <div class="dn-filter-grid">
        <div class="dn-filter-field">
            <label class="dn-filter-label">{{ $sehariSaja ? 'Tanggal pemakaian' : 'Tanggal mulai' }}</label>
            <input type="date" name="tanggal_mulai" class="form-control dn-filter-input" min="{{ now()->toDateString() }}" value="{{ $jadwal['tanggal_mulai'] }}">
        </div>

        @if ($sehariSaja)
            <div class="dn-filter-field dn-filter-note">
                <i class="bi bi-clock-history"></i><span>1 hari = otomatis 8 jam pemakaian</span>
            </div>
        @else
            <div class="dn-filter-field">
                <label class="dn-filter-label">Tanggal selesai</label>
                <input type="date" name="tanggal_selesai" class="form-control dn-filter-input" min="{{ $jadwal['tanggal_mulai'] ?: now()->toDateString() }}" value="{{ $jadwal['tanggal_selesai'] }}">
            </div>
        @endif

        <div class="dn-filter-field dn-filter-field--btn">
            <button class="btn btn-brand dn-filter-btn"><i class="bi bi-search me-1"></i>Cek Ketersediaan</button>
        </div>
    </div>
</form>

@once
    <style>
        .dn-filter { background:#fff; border:1px solid var(--line); border-radius:1.35rem; padding:1.1rem 1.2rem; margin-bottom:1rem; box-shadow:0 3px 14px rgba(15,23,42,.04); transition:box-shadow .2s ease; }
        .dn-filter:hover { box-shadow:0 8px 22px -8px rgba(15,23,42,.1); }
        .dn-filter-head { display:flex; align-items:center; gap:.7rem; margin-bottom:.9rem; }
        .dn-filter-ic { display:grid; place-items:center; width:2.35rem; height:2.35rem; border-radius:.75rem; background:var(--primary-soft); color:var(--primary); font-size:1rem; flex:none; }
        .dn-filter-title { font-weight:800; font-size:.88rem; color:var(--ink); font-family:'Plus Jakarta Sans',sans-serif; }
        .dn-filter-sub { font-size:.76rem; color:var(--muted); }
        .dn-filter-grid { display:flex; flex-wrap:wrap; align-items:end; gap:.85rem; }
        .dn-filter-field { display:flex; flex-direction:column; gap:.35rem; flex:1 1 9.5rem; min-width:8.5rem; }
        .dn-filter-field--sm { flex-basis:7.5rem; }
        .dn-filter-field--btn { flex:0 0 auto; align-self:end; }
        .dn-filter-label { font-size:.76rem; font-weight:700; color:#475569; margin:0; }
        .dn-filter-input, .dn-filter-field .form-control, .dn-filter-field .form-select { height:2.6rem; border-radius:.65rem; border-color:var(--line); }
        .dn-filter-input:focus, .dn-filter-field .form-control:focus { border-color:var(--primary); box-shadow:0 0 0 .18rem rgba(14,107,125,.14); }
        .dn-filter-btn { height:2.6rem; padding:0 1.2rem; border-radius:.65rem; font-weight:700; white-space:nowrap; }
        .dn-filter-note { flex-direction:row; align-items:center; gap:.4rem; color:var(--muted); font-size:.82rem; height:2.6rem; }
        .dn-filter-note i { color:var(--primary); }
    </style>
@endonce
