@extends('layouts.customer')
@section('title', 'Keranjang Reservasi')

@section('content')
    <style>
        /* ══════════════ CHECKOUT LAYOUT ══════════════ */
        .ck-summary { position:sticky; top:6rem; }
        @media (max-width: 991.98px) { .ck-summary { position:static; } }

        /* ─── WRAPPER PER-ITEM (satu kartu per ruangan) ─── */
        .ck-item-wrap { padding:1.15rem 1.35rem; margin-bottom:1rem; }
        .ck-item-wrap .head { display:flex; align-items:center; justify-content:space-between;
            gap:.75rem; padding-bottom:.9rem; margin-bottom:.9rem;
            border-bottom:1px solid var(--line-soft); flex-wrap:wrap; }
        .ck-item-wrap .head .lhs { display:flex; align-items:center; gap:.55rem; flex-wrap:wrap; }
        .ck-item-wrap .head .ttl { font-size:.75rem; font-weight:800; letter-spacing:.1em;
            text-transform:uppercase; color:var(--ink); }
        .ck-item-wrap .head .cnt { font-size:.65rem; font-weight:800; background:var(--surface-2);
            color:var(--muted); padding:.2rem .55rem; border-radius:9999px; border:1px solid var(--line); }
        /* Kartu isi item */
        .ck-item { padding:1.1rem 1.2rem; border:1px solid var(--line); border-radius:1rem;
            background:linear-gradient(135deg, #fbfdfd, #f7fafa);
            transition:border-color .18s ease, box-shadow .18s ease, background .18s ease; }
        .ck-item:hover { border-color:var(--primary-softer); background:#fff;
            box-shadow:0 8px 18px -8px rgba(15,60,73,.12); }

        .ck-item-top { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
        .ck-item-title { display:flex; align-items:flex-start; gap:.85rem; min-width:0; flex:1; }
        .ck-item-title .ic { display:grid; place-items:center; width:2.85rem; height:2.85rem; border-radius:.9rem;
            background:linear-gradient(135deg, var(--primary-soft), var(--primary-tint));
            color:var(--primary-dark); font-size:1.25rem; flex:none;
            border:1px solid var(--primary-softer); }
        .ck-item-title .body { min-width:0; }
        .ck-item-title .nm-row { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; margin-bottom:.15rem; }
        .ck-item-title h4 { font-size:1rem; font-weight:800; margin:0; color:var(--ink); letter-spacing:-.01em; }
        .ck-item-title .kat-chip { font-size:.6rem; font-weight:800; letter-spacing:.05em; text-transform:uppercase;
            background:var(--primary-soft); color:var(--primary-dark); padding:.2rem .55rem;
            border-radius:9999px; border:1px solid var(--primary-softer); }
        .ck-item-title .addr { font-size:.72rem; color:var(--muted); }
        .ck-item-title .addr i { color:var(--soft); font-size:.8em; margin-right:.15rem; }

        .ck-item-price { text-align:right; flex:none; }
        .ck-item-price small { display:block; color:var(--soft); font-size:.65rem; font-weight:700;
            text-transform:uppercase; letter-spacing:.06em; }
        .ck-item-price .val { font-weight:800; color:var(--primary-dark); font-size:1rem; letter-spacing:-.01em; margin-top:.2rem; }

        .ck-item-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(9rem, 1fr));
            gap:.75rem 1.25rem; margin-top:1rem; padding-top:1rem;
            border-top:1px dashed var(--line); }
        .ck-item-grid .cell small { display:block; font-size:.62rem; font-weight:800; letter-spacing:.06em;
            text-transform:uppercase; color:var(--soft); margin-bottom:.25rem; }
        .ck-item-grid .cell .val { font-size:.8rem; font-weight:700; color:var(--ink);
            display:inline-flex; align-items:center; gap:.35rem; }
        .ck-item-grid .cell .val i { color:var(--primary); font-size:.85em; }

        .ck-facilities-line { display:flex; flex-wrap:wrap; align-items:center; gap:.4rem;
            margin-top:1rem; padding-top:.85rem; border-top:1px dashed var(--line); }
        .ck-facilities-line:empty { display:none; }
        .ck-fac-chip { display:inline-flex; align-items:center; gap:.35rem; font-size:.7rem; font-weight:600;
            color:#475569; background:#fff; padding:.32rem .65rem; border-radius:.55rem;
            border:1px solid var(--line); }

        /* ─── AKSI ITEM: Ubah / Hapus — dua tombol pill sejajar, tinggi & padding sama ─── */
        .ck-item-actions { display:flex; align-items:center; flex-wrap:wrap; gap:.5rem; margin-top:.85rem;
            padding-top:.85rem; border-top:1px dashed var(--line); }
        .ck-act-btn { display:inline-flex; align-items:center; gap:.35rem; font-size:.75rem; font-weight:700;
            padding:.45rem .85rem; border-radius:.6rem; border:1px solid var(--line); background:#fff;
            color:var(--muted); cursor:pointer; line-height:1.1; transition:all .15s ease; }
        .ck-act-btn:hover { border-color:var(--primary-softer); color:var(--primary-dark); background:var(--primary-soft); }
        .ck-act-btn.danger { color:var(--rose); margin-left:auto; }
        .ck-act-btn.danger:hover { border-color:#fecdd3; background:#fff1f2; color:#be123c; }
        .ck-act-btn i { font-size:.85em; }

        /* ─── PANEL UBAH JADWAL — inline, langsung di kartu item, tanpa pindah halaman ─── */
        .ck-edit-panel { margin-top:.85rem; padding:1.1rem 1.15rem; border-radius:.9rem;
            background:var(--surface); border:1px solid var(--line); }
        .ck-edit-panel[hidden] { display:none; }
        .ck-edit-label { display:block; font-size:.66rem; font-weight:800; letter-spacing:.05em;
            text-transform:uppercase; color:var(--soft); margin-bottom:.3rem; }
        .ck-edit-input { width:100%; border:1px solid var(--line); border-radius:.6rem;
            padding:.55rem .75rem; font-size:.85rem; color:var(--ink); background:#fff; }
        .ck-edit-input:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 .15rem var(--primary-soft); }
        .ck-edit-err { font-size:.74rem; color:#be123c; font-weight:600; margin-top:.3rem; line-height:1.4; }
        .ck-edit-hint { font-size:.72rem; color:var(--muted); margin-top:.3rem; }
        .ck-edit-field { margin-top:.75rem; }
        .ck-edit-actions { display:flex; justify-content:flex-end; align-items:center; flex-wrap:wrap; gap:.6rem; margin-top:1.1rem; }
        .ck-edit-cancel { padding:.55rem 1rem; border-radius:.6rem; border:1px solid var(--line);
            background:#fff; color:var(--muted); font-weight:700; font-size:.78rem; }
        .ck-edit-cancel:hover { background:var(--surface-2); }
        .ck-edit-save { display:inline-flex; align-items:center; gap:.4rem; padding:.6rem 1.25rem;
            border-radius:.6rem; border:0; background:var(--primary); color:#fff; font-weight:800; font-size:.78rem; }
        .ck-edit-save:hover { background:var(--primary-dark); }
        .ck-edit-save:disabled { opacity:.7; cursor:not-allowed; }

        /* ══════════════ RINGKASAN BIAYA (KANAN) ══════════════ */
        .ck-summary-card { padding:1.5rem; }
        .ck-sum-head { display:flex; align-items:center; justify-content:space-between; gap:.5rem;
            padding-bottom:1rem; margin-bottom:.5rem; border-bottom:1px solid var(--line-soft); flex-wrap:wrap; }
        .ck-sum-head h3 { font-size:.85rem; font-weight:800; margin:0; color:var(--ink);
            letter-spacing:.06em; text-transform:uppercase; }
        .ck-sum-head .rid { font-size:.65rem; color:var(--soft); font-weight:700;
            background:var(--surface); padding:.2rem .5rem; border-radius:.4rem; border:1px solid var(--line); }

        .ck-sum-rows { padding:.85rem 0 .75rem; border-bottom:1px solid var(--line-soft); }
        .ck-sum-rows .row-r { display:flex; justify-content:space-between; align-items:center; gap:.5rem;
            font-size:.8rem; padding:.35rem 0; }
        .ck-sum-rows .row-r .lb { color:var(--muted); font-weight:500; }
        .ck-sum-rows .row-r .vl { font-weight:700; color:var(--ink); }

        .ck-total { padding:1.15rem 0; display:flex; align-items:baseline; justify-content:space-between; gap:.5rem; flex-wrap:wrap; }
        .ck-total .lbl { font-size:.68rem; font-weight:800; letter-spacing:.08em;
            text-transform:uppercase; color:var(--soft); display:block; }
        .ck-total .amount { font-size:clamp(1.3rem, 5.5vw, 1.75rem); font-weight:800; color:var(--primary-darker);
            letter-spacing:-.02em; margin-top:.2rem; display:block; line-height:1.1; }
        .ck-total .duration-chip { font-size:.68rem; font-weight:800; color:var(--primary-dark);
            background:var(--primary-soft); border:1px solid var(--primary-softer);
            padding:.35rem .7rem; border-radius:9999px; }

        .ck-actions { display:flex; flex-direction:column; gap:.6rem; }
        .ck-submit-btn { padding:.95rem 1rem; font-size:.9rem; font-weight:800; border-radius:1rem;
            display:inline-flex; align-items:center; justify-content:center; gap:.5rem;
            transition:transform .15s ease, box-shadow .15s ease; }
        .ck-submit-btn:hover { transform:translateY(-1px); box-shadow:0 14px 26px -6px rgba(23,107,135,.5); }
        .ck-submit-btn i { transition:transform .2s ease; }
        .ck-submit-btn:hover i.arrow { transform:translateX(3px); }

        /* DOKUMEN PERSYARATAN — header baris (dipakai juga oleh kartu ringkasan dokumen di bawah) */
        .ck-personal-head { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.5rem;
            padding-bottom:.85rem; border-bottom:1px solid var(--line-soft); margin-bottom:1rem; }
        .ck-personal-head h3 { font-size:.95rem; font-weight:800; margin:0; color:var(--ink);
            display:flex; align-items:center; gap:.5rem; }
        .ck-personal-head h3 i { color:var(--primary); }

        .ck-dok-notice { background:linear-gradient(135deg, var(--amber-tint), #fff8e1);
            border:1px solid #fde68a; border-radius:1rem; padding:1rem 1.15rem;
            display:flex; align-items:flex-start; gap:.85rem; margin-bottom:1rem; }
        .ck-dok-notice .ic { display:grid; place-items:center; width:2.3rem; height:2.3rem;
            border-radius:.7rem; background:#fde68a; color:#78350f; font-size:1rem; flex:none; }
        .ck-dok-notice p { font-size:.75rem; color:#78350f; margin:0; line-height:1.55; }
        .ck-dok-notice b { color:#5a2b0d; }
        .ck-dok-notice p a { color:#5a2b0d; font-weight:700; text-decoration:underline; }
        .ck-dok-notice.ck-dok-ok { background:linear-gradient(135deg, var(--emerald-soft), #ecfdf5); border-color:#a7f3d0; }
        .ck-dok-notice.ck-dok-ok .ic { background:#a7f3d0; color:#047857; }
        .ck-dok-notice.ck-dok-ok p { color:#065f46; }
        .ck-dok-notice.ck-dok-ok b { color:#047857; }
        .ck-dok-notice.ck-dok-ok p a { color:#047857; }

        @media (max-width: 575.98px) {
            .ck-item-wrap { padding:1rem; }
            .ck-item { padding:.95rem; }
            .ck-item-price { text-align:left; flex:1 1 100%; }
            .ck-summary-card { padding:1.15rem; }
            .ck-edit-panel { padding:.95rem; }
            .ck-edit-actions > * { flex:1; justify-content:center; }
            .ck-edit-input { font-size:1rem; }
        }

        /* EMPTY STATE */
        .ck-empty { padding:4rem 2rem; text-align:center; }
        .ck-empty .ic-wrap { display:grid; place-items:center; width:5.5rem; height:5.5rem; margin:0 auto 1.5rem;
            border-radius:1.4rem; background:var(--primary-soft); color:var(--primary);
            font-size:2rem; border:1px solid var(--primary-softer); }
        .ck-empty h2 { font-size:1.35rem; font-weight:800; color:var(--ink); margin:0 0 .5rem; }
        .ck-empty p { font-size:.85rem; color:var(--muted); max-width:24rem; margin:0 auto 1.5rem; line-height:1.6; }
    </style>

    @if (count($items) === 0)
        {{-- ── EMPTY STATE ────────────────────────────── --}}
        <div class="xcard ck-empty mx-auto" style="max-width:640px;" data-reveal>
            <div class="ic-wrap"><i class="bi bi-bag"></i></div>
            <h2>Keranjang Masih Kosong</h2>
            <p>Anda belum memilih fasilitas untuk direservasi. Silakan pilih fasilitas yang tersedia melalui denah gedung BITC.</p>
            <a href="{{ route('reservasi.index') }}" class="btn btn-brand">
                <i class="bi bi-building me-1"></i>
                Jelajahi Fasilitas &amp; Ruangan
                <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
    @else
        <div class="row g-4">
            {{-- ══════════════ KOLOM KIRI ══════════════ --}}
            <div class="col-lg-7" data-reveal>
                {{-- Per item: wrapper card TERSENDIRI (sesuai gambar referensi) --}}
                @foreach ($items as $index => $item)
                    @php
                        $meta = \App\Support\KategoriMeta::get($item['kategori']);
                        $lantaiNomor = $item['lantai_nomor'] ?? null;
                        $sedangDiedit = old('edit_index') !== null && (int) old('edit_index') === $index;
                        $sehariSaja = $item['satuan'] === 'Hari' && $item['kategori'] === 'Convention Hall';
                    @endphp
                    <div class="xcard ck-item-wrap">
                        <div class="head">
                            <div class="lhs">
                                <span class="ttl">Item Ruangan</span>
                                <span class="cnt">1 Ruangan</span>
                            </div>
                        </div>

                        <div class="ck-item">
                            <div class="ck-item-top">
                                <div class="ck-item-title">
                                    <span class="ic"><i class="bi {{ $meta['ikon'] ?? 'bi-door-open' }}"></i></span>
                                    <div class="body">
                                        <div class="nm-row">
                                            <h4>{{ $item['nama_fasilitas'] }}</h4>
                                            <span class="kat-chip">{{ $item['kategori'] }}</span>
                                        </div>
                                        <div class="addr">
                                            <i class="bi bi-geo-alt-fill"></i>
                                            @if ($lantaiNomor) Lt. {{ $lantaiNomor }} @if($item['sayap'] ?? null) Sayap {{ $item['sayap'] }} @endif • @endif
                                            BITC Cimahi
                                        </div>
                                    </div>
                                </div>
                                <div class="ck-item-price">
                                    <small>Tarif Sewa</small>
                                    <div class="val">Rp {{ number_format($item['total_biaya'] / max(1, $item['durasi']), 0, ',', '.') }}<span style="font-size:.68rem;color:var(--muted);font-weight:600;">/{{ strtolower($item['satuan']) }}</span></div>
                                </div>
                            </div>

                            <div class="ck-item-grid">
                                <div class="cell">
                                    <small>Tanggal</small>
                                    <span class="val">
                                        <i class="bi bi-calendar3"></i>
                                        {{ \Illuminate\Support\Carbon::parse($item['tanggal_mulai'])->translatedFormat('j M Y') }}@if($item['tanggal_selesai'] !== $item['tanggal_mulai']) – {{ \Illuminate\Support\Carbon::parse($item['tanggal_selesai'])->translatedFormat('j M Y') }}@endif
                                    </span>
                                </div>
                                <div class="cell">
                                    <small>Durasi</small>
                                    <span class="val">
                                        <i class="bi bi-clock"></i>
                                        @if ($item['jam_mulai'])
                                            {{ str_replace(':', '.', substr($item['jam_mulai'],0,5)) }}–{{ str_replace(':', '.', substr($item['jam_selesai'],0,5)) }} ({{ $item['durasi'] }} jam)
                                        @else
                                            {{ $item['durasi'] }} {{ strtolower($item['satuan']) }}
                                        @endif
                                    </span>
                                </div>
                                <div class="cell">
                                    <small>Jumlah Pengguna</small>
                                    <span class="val"><i class="bi bi-people"></i>{{ $item['jumlah_pengguna'] }} orang</span>
                                </div>
                                <div class="cell">
                                    <small>Subtotal</small>
                                    <span class="val">Rp {{ number_format($item['total_biaya'], 0, ',', '.') }}</span>
                                </div>
                            </div>

                            {{-- Fasilitas bawaan chip (baris tersendiri) --}}
                            <div class="ck-facilities-line">
                                @foreach ($item['fasilitas_bawaan'] ?? [] as $fasilitas)
                                    <span class="ck-fac-chip">{{ $fasilitas }}</span>
                                @endforeach
                            </div>

                            {{-- Tombol aksi: Ubah membuka panel jadwal INLINE (tetap di Keranjang, bukan pindah halaman) --}}
                            <div class="ck-item-actions">
                                <button type="button" class="ck-act-btn" data-ck-edit-toggle="{{ $index }}" aria-expanded="{{ $sedangDiedit ? 'true' : 'false' }}">
                                    <i class="bi bi-pencil-square"></i>Ubah
                                </button>
                                <form method="POST" action="{{ route('reservasi.keranjang.hapus', $index) }}"
                                      data-confirm="{{ $item['nama_fasilitas'] }} akan dikeluarkan dari keranjang."
                                      data-confirm-title="Hapus item ini?" data-icon="warning"
                                      data-confirm-text="Ya, hapus" data-confirm-color="#e11d48" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ck-act-btn danger">
                                        <i class="bi bi-trash3"></i>Hapus
                                    </button>
                                </form>
                            </div>

                            {{-- ═══ PANEL UBAH JADWAL — inline, per item, tanpa pindah ke halaman detail fasilitas ═══ --}}
                            <div class="ck-edit-panel" id="ckEditPanel{{ $index }}" @if (! $sedangDiedit) hidden @endif>
                                <form method="POST" action="{{ route('reservasi.keranjang.tambah') }}" enctype="multipart/form-data" class="ck-edit-form" data-ck-edit-form>
                                    @csrf
                                    <input type="hidden" name="id_fasilitas" value="{{ $item['id_fasilitas'] }}">
                                    <input type="hidden" name="id_tarif_sewa" value="{{ $item['id_tarif_sewa'] }}">
                                    <input type="hidden" name="edit_index" value="{{ $index }}">

                                    <input type="hidden" name="nama_lengkap" value="{{ $pemesan->nama_lengkap }}">
                                    <input type="hidden" name="alamat" value="{{ $pemesan->alamat }}">
                                    <input type="hidden" name="usia" value="{{ $pemesan->usia }}">
                                    <input type="hidden" name="pekerjaan" value="{{ $pemesan->pekerjaan }}">
                                    <input type="hidden" name="no_telepon" value="{{ $pemesan->no_telepon }}">
                                    @if ($item['satuan'] === 'Bulan')
                                        @foreach (app(\App\Services\CartService::class)->dokumen() as $d)
                                            <input type="hidden" name="dokumen_pertahankan[]" value="{{ $d['path'] }}">
                                        @endforeach
                                    @endif

                                    <label class="ck-edit-label">Keperluan / Kegiatan</label>
                                    <textarea name="keperluan" class="ck-edit-input" rows="2" maxlength="1000" required>{{ $sedangDiedit ? old('keperluan', $item['keperluan']) : $item['keperluan'] }}</textarea>
                                    @if ($sedangDiedit) @error('keperluan') <div class="ck-edit-err">{{ $message }}</div> @enderror @endif

                                    @php $kapItem = $item['kapasitas'] ?? \App\Models\Fasilitas::whereKey($item['id_fasilitas'])->value('kapasitas'); @endphp
                                    <div class="ck-edit-field">
                                        <label class="ck-edit-label">Jumlah Pengguna</label>
                                        <input type="number" name="jumlah_pengguna" class="ck-edit-input" inputmode="numeric" min="1" max="{{ $kapItem }}" step="1" required
                                               data-label="Jumlah pengguna"
                                               data-pesan-maks="Jumlah pengguna melebihi kapasitas maksimal {{ $kapItem }} orang."
                                               value="{{ $sedangDiedit ? old('jumlah_pengguna', $item['jumlah_pengguna']) : $item['jumlah_pengguna'] }}">
                                        @if ($sedangDiedit) @error('jumlah_pengguna') <div class="ck-edit-err">{{ $message }}</div> @enderror @endif
                                        <div class="ck-edit-hint">Kapasitas maksimal: {{ $kapItem }} orang</div>
                                    </div>

                                    @if ($item['satuan'] === 'Jam')
                                        <div class="row g-2 mt-1">
                                            <div class="col-12">
                                                <label class="ck-edit-label">Tanggal Pemakaian</label>
                                                <input type="date" name="tanggal_mulai" class="ck-edit-input" required
                                                       min="{{ now()->toDateString() }}"
                                                       value="{{ $sedangDiedit ? old('tanggal_mulai', $item['tanggal_mulai']) : $item['tanggal_mulai'] }}">
                                                @if ($sedangDiedit) @error('tanggal_mulai') <div class="ck-edit-err">{{ $message }}</div> @enderror @endif
                                            </div>
                                            <div class="col-6">
                                                <label class="ck-edit-label">Jam Mulai</label>
                                                @include('reservasi.partials.pilih-jam', [
                                                    'name' => 'jam_mulai',
                                                    'value' => $sedangDiedit ? old('jam_mulai', $item['jam_mulai']) : $item['jam_mulai'],
                                                    'fasilitasId' => $item['id_fasilitas'], 'kecil' => true,
                                                ])
                                                @if ($sedangDiedit) @error('jam_mulai') <div class="ck-edit-err">{{ $message }}</div> @enderror @endif
                                            </div>
                                            <div class="col-6">
                                                <label class="ck-edit-label">Jam Selesai</label>
                                                @include('reservasi.partials.pilih-jam', [
                                                    'name' => 'jam_selesai',
                                                    'value' => $sedangDiedit ? old('jam_selesai', $item['jam_selesai']) : $item['jam_selesai'],
                                                    'kecil' => true, 'kanan' => true,
                                                ])
                                                @if ($sedangDiedit) @error('jam_selesai') <div class="ck-edit-err">{{ $message }}</div> @enderror @endif
                                            </div>
                                        </div>
                                    @elseif ($item['satuan'] === 'Bulan')
                                        <div class="row g-2 mt-1">
                                            <div class="col-md-6">
                                                <label class="ck-edit-label">Tanggal Mulai</label>
                                                <input type="date" name="tanggal_mulai" class="ck-edit-input" required
                                                       min="{{ now()->toDateString() }}"
                                                       value="{{ $sedangDiedit ? old('tanggal_mulai', $item['tanggal_mulai']) : $item['tanggal_mulai'] }}">
                                                @if ($sedangDiedit) @error('tanggal_mulai') <div class="ck-edit-err">{{ $message }}</div> @enderror @endif
                                            </div>
                                            <div class="col-md-6">
                                                <label class="ck-edit-label">Tanggal Berakhir</label>
                                                <input type="date" name="tanggal_selesai" class="ck-edit-input" required
                                                       value="{{ $sedangDiedit ? old('tanggal_selesai', $item['tanggal_selesai']) : $item['tanggal_selesai'] }}">
                                                @if ($sedangDiedit) @error('tanggal_selesai') <div class="ck-edit-err">{{ $message }}</div> @enderror @endif
                                            </div>
                                        </div>
                                    @else
                                        <div class="row g-2 mt-1">
                                            <div class="col-md-{{ $sehariSaja ? 12 : 6 }}">
                                                <label class="ck-edit-label">{{ $sehariSaja ? 'Tanggal Pemakaian' : 'Tanggal Mulai' }}</label>
                                                <input type="date" name="tanggal_mulai" class="ck-edit-input" required
                                                       min="{{ now()->toDateString() }}"
                                                       value="{{ $sedangDiedit ? old('tanggal_mulai', $item['tanggal_mulai']) : $item['tanggal_mulai'] }}">
                                                @if ($sedangDiedit) @error('tanggal_mulai') <div class="ck-edit-err">{{ $message }}</div> @enderror @endif
                                            </div>
                                            @if (! $sehariSaja)
                                                <div class="col-md-6">
                                                    <label class="ck-edit-label">Tanggal Selesai</label>
                                                    <input type="date" name="tanggal_selesai" class="ck-edit-input" required
                                                           value="{{ $sedangDiedit ? old('tanggal_selesai', $item['tanggal_selesai']) : $item['tanggal_selesai'] }}">
                                                    @if ($sedangDiedit) @error('tanggal_selesai') <div class="ck-edit-err">{{ $message }}</div> @enderror @endif
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="ck-edit-actions">
                                        <button type="button" class="ck-edit-cancel" data-ck-edit-toggle="{{ $index }}">Batal</button>
                                        <button type="submit" class="ck-edit-save"><i class="bi bi-check2"></i>Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- Dokumen persyaratan (bulanan only) — RINGKASAN saja, tidak ada unggah di
                     sini. Keranjang cuma untuk mengirim reservasi; unggah/ubah dokumen dilakukan
                     lewat "Ubah" pada item bulanan (form "Isi Jadwal"), lihat atur-jadwal-modal. --}}
                @if ($hasBulan)
                    @php
                        $ruangBulan = collect($items)->where('satuan', 'Bulan')->pluck('nama_fasilitas');
                        $itemBulanPertama = collect($items)->search(fn ($i) => $i['satuan'] === 'Bulan');
                    @endphp
                    <div class="xcard p-4 mt-3">
                        <div class="ck-personal-head">
                            <h3><i class="bi bi-paperclip"></i>Dokumen Persyaratan <span class="text-muted fw-normal ms-1" style="font-size:.75rem;">(Wajib untuk sewa bulanan)</span></h3>
                        </div>
                        @if (count($dokumenBulan) > 0)
                            <div class="ck-dok-notice ck-dok-ok">
                                <span class="ic"><i class="bi bi-check-circle-fill"></i></span>
                                <p><b>{{ count($dokumenBulan) }} dokumen sudah terlampir</b><br>
                                    Berlaku untuk {{ $ruangBulan->count() > 1 ? 'semua ruangan bulanan (' . $ruangBulan->implode(', ') . ')' : $ruangBulan->first() }}.
                                    Ingin menambah atau mengganti berkas? Buka
                                    <a href="{{ route('reservasi.fasilitas.show', ['fasilitas' => $items[$itemBulanPertama]['id_fasilitas'], 'jenis' => $items[$itemBulanPertama]['id_jenis_sewa'], 'edit_index' => $itemBulanPertama]) }}">"Ubah"</a>
                                    pada item bulanan di atas.
                                </p>
                            </div>
                        @else
                            <div class="ck-dok-notice">
                                <span class="ic"><i class="bi bi-exclamation-triangle-fill"></i></span>
                                <p><b>Dokumen belum dilampirkan</b><br>
                                    Buka
                                    <a href="{{ route('reservasi.fasilitas.show', ['fasilitas' => $items[$itemBulanPertama]['id_fasilitas'], 'jenis' => $items[$itemBulanPertama]['id_jenis_sewa'], 'edit_index' => $itemBulanPertama]) }}">"Ubah"</a>
                                    pada item bulanan di atas untuk melampirkan Company Profile / Legalitas / KTP Penanggung Jawab sebelum mengirim reservasi.
                                </p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- ══════════════ KOLOM KANAN: RINGKASAN ══════════════ --}}
            <div class="col-lg-5" data-reveal>
                <div class="xcard ck-summary ck-summary-card">
                    <div class="ck-sum-head">
                        <h3>Ringkasan Biaya</h3>
                        <span class="rid">{{ count($items) }} item</span>
                    </div>

                    <div class="ck-sum-rows">
                        @foreach ($items as $item)
                            <div class="row-r">
                                <span class="lb">Tarif {{ \Illuminate\Support\Str::limit($item['nama_fasilitas'], 20) }} ({{ $item['durasi'] }} {{ strtolower($item['satuan']) }})</span>
                                <span class="vl">Rp {{ number_format($item['total_biaya'], 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="ck-total">
                        <div>
                            <span class="lbl">Total Tagihan</span>
                            <span class="amount">Rp {{ number_format($total, 0, ',', '.') }}</span>
                        </div>
                        <span class="duration-chip">{{ count($items) }} Fasilitas</span>
                    </div>

                    <div class="ck-actions">
                        <form method="POST" action="{{ route('reservasi.checkout') }}"
                              data-confirm="Reservasi akan dikirim untuk diverifikasi admin. Pastikan fasilitas dan jadwal sudah benar."
                              data-confirm-title="Ajukan reservasi?" data-icon="question" data-confirm-text="Ya, ajukan">
                            @csrf
                            <button type="submit" class="btn btn-brand ck-submit-btn w-100">
                                <span>Ajukan Reservasi</span>
                                <i class="bi bi-arrow-right arrow"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @php
            // Error umum (bukan milik field jadwal di panel Ubah, mis. kapasitas/jadwal bentrok/
            // item tidak ditemukan) ditampilkan lewat pop-up, sama seperti di form "Isi Jadwal".
            $medanDikenalCk = ['keperluan', 'jumlah_pengguna', 'tanggal_mulai', 'tanggal_selesai', 'jam_mulai', 'jam_selesai'];
            $errUmumCk = collect($errors->getMessages())
                ->filter(fn ($pesan, $kunci) => ! collect($medanDikenalCk)->contains(fn ($m) => $kunci === $m || str_starts_with((string) $kunci, $m.'.')))
                ->flatten();
        @endphp
        @if ($errUmumCk->isNotEmpty())
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    Swal.fire({
                        icon: 'warning',
                        title: @json($errors->has('jadwal') ? 'Jadwal Tidak Tersedia' : 'Reservasi Belum Dapat Diproses'),
                        html: @json($errUmumCk->map(fn ($p) => e($p))->implode('<br><br>')),
                        confirmButtonColor: '#176b87',
                        confirmButtonText: 'Mengerti',

                    });
                });
            </script>
        @endif

        <script>
        (function() {
            // Buka/tutup panel Ubah jadwal per item (tombol "Ubah" & "Batal" di dalam panel
            // sama-sama pakai data-ck-edit-toggle dengan index item yang sama).
            document.querySelectorAll('[data-ck-edit-toggle]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const idx = btn.dataset.ckEditToggle;
                    const panel = document.getElementById('ckEditPanel' + idx);
                    if (!panel) return;
                    const akanTerbuka = panel.hasAttribute('hidden');
                    panel.toggleAttribute('hidden', !akanTerbuka);
                    document.querySelectorAll('[data-ck-edit-toggle="' + idx + '"]').forEach((b) => {
                        b.setAttribute('aria-expanded', akanTerbuka ? 'true' : 'false');
                    });
                    if (akanTerbuka) panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                });
            });

            // Cegah submit ganda saat menyimpan perubahan jadwal (sama seperti form "Isi Jadwal").
            document.querySelectorAll('[data-ck-edit-form]').forEach((form) => {
                form.addEventListener('submit', (e) => {
                    if (form.dataset.submitting === '1') {
                        e.preventDefault();
                        return;
                    }
                    form.dataset.submitting = '1';
                    const btn = form.querySelector('.ck-edit-save');
                    if (btn) {
                        btn.disabled = true;
                        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Menyimpan…';
                    }
                });
            });
        })();
        </script>
    @endif
@endsection