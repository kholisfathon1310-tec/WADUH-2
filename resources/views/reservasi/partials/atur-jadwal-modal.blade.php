{{--
    Modal "Atur Jadwal Sewa" — auto-open dari halaman fasilitas.

    Fitur:
    - Total biaya update OTOMATIS saat pilih jam / hari / bulan
    - Upload dokumen persyaratan untuk sewa bulanan (KTP + Proposal)
    - Terminology match sama sistem (tarif × durasi = total, no fake fee)
    - Data pemesan disembunyikan (hidden input) — auto dari profil

    Variabel dari fasilitas.blade.php:
    - $fasilitas, $tarif, $jenis, $satuan, $sehariSaja
    - $isMulti, $jumlahRuangan, $totalTarif (= tarif->harga × jumlahRuangan)
    - $semuaTarif (untuk tabs Jenis Sewa)
    - $bawaan, $kapMin, $pemesan
--}}

<div class="fp-form-overlay" id="fpFormOverlay">
    <div class="aj-modal">
        <button type="button" class="aj-close" id="fpFormClose" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>

        {{-- ══════ HEADER ══════ --}}
        <div class="aj-head">
            <div class="aj-head-l">
                <span class="aj-head-ic"><i class="bi bi-calendar2-week-fill"></i></span>
                <div class="aj-head-body">
                    <h2 class="aj-head-title">
                        Atur Jadwal Sewa
                        <span class="aj-head-tag">
                            <i class="bi bi-geo-alt-fill"></i>
                            {{ $isMulti ? "$jumlahRuangan Ruangan Terpilih" : $fasilitas->nama_fasilitas }}
                        </span>
                    </h2>
                    <p class="aj-head-sub">Lengkapi jadwal &amp; keperluan pemakaian untuk memasukkan ke keranjang.</p>
                </div>
            </div>
        </div>

        {{-- ══════ FACILITY INFO BAR ══════ --}}
        <div class="aj-fac-bar">
            <div class="aj-fac-l">
                <span class="aj-fac-ic"><i class="bi {{ $meta['ikon'] ?? 'bi-door-open-fill' }}"></i></span>
                <div>
                    <div class="aj-fac-nm">
                        {{ $isMulti ? "$jumlahRuangan Ruangan" : $fasilitas->nama_fasilitas }}
                        <span class="aj-fac-sep">·</span>
                        <span class="aj-fac-luas">{{ number_format($fasilitas->luas, 2, ',', '.') }} m²</span>
                    </div>
                    <div class="aj-fac-sub">
                        <i class="bi bi-people"></i>Kapasitas maks {{ $kapMin }} orang
                        · Lantai {{ $fasilitas->lantai->nomor_lantai ?? '-' }}
                    </div>
                </div>
            </div>
            <div class="aj-fac-r">
                <small>Tarif</small>
                <div class="aj-fac-price">
                    Rp {{ number_format($tarif->harga, 0, ',', '.') }}
                    <span class="aj-fac-satuan">/ {{ $satuan }}</span>
                </div>
            </div>
        </div>

        {{-- ══════ JENIS SEWA TABS (kalau ada > 1 jenis) — tersedia juga untuk multi-ruangan,
             jenis yang dipilih berlaku untuk semua ruangan dalam antrian yang sama ══════ --}}
        @if ($semuaTarif->count() > 1)
            <div class="aj-section-head">
                <span class="aj-section-label"><i class="bi bi-tag-fill"></i>Jenis Sewa</span>
                <span class="aj-section-hint">Pilih salah satu tarif</span>
            </div>
            <div class="aj-jenis-tabs">
                @foreach ($semuaTarif as $t)
                    @php
                        $s = $t->jenisSewa->satuan->value;
                        $ikon = ['Jam' => 'bi-clock', 'Hari' => 'bi-calendar-date', 'Bulan' => 'bi-calendar3-range'][$s] ?? 'bi-tag';
                        $aktif = $t->id_jenis_sewa === $jenis->id_jenis_sewa;
                        $jenisTabParams = ['fasilitas' => $fasilitas->id_fasilitas, 'jenis' => $t->id_jenis_sewa, 'buka' => 1];
                        if (request()->filled('antrian')) $jenisTabParams['antrian'] = request('antrian');
                        if (($editIndex ?? null) !== null) $jenisTabParams['edit_index'] = $editIndex;
                        $jenisTabUrl = route('reservasi.fasilitas.show', $jenisTabParams);
                    @endphp
                    <a href="{{ $jenisTabUrl }}"
                       class="aj-jenis-tab {{ $aktif ? 'active' : '' }}">
                        <i class="bi {{ $ikon }}"></i>
                        <span>Per {{ $s }}</span>
                        @if ($aktif) <i class="bi bi-check-lg aj-jenis-check"></i> @endif
                    </a>
                @endforeach
            </div>
        @endif

        {{-- ══════ FORM ══════ --}}
        <form method="POST" action="{{ route('reservasi.keranjang.tambah') }}"
              class="aj-form" enctype="multipart/form-data"
              data-tarif="{{ $tarif->harga }}"
              data-jumlah-ruangan="{{ $jumlahRuangan }}"
              data-satuan="{{ $satuan }}"
              data-sehari-saja="{{ $sehariSaja ? '1' : '0' }}">
            @csrf
            <input type="hidden" name="id_fasilitas" value="{{ $fasilitas->id_fasilitas }}">
            <input type="hidden" name="id_tarif_sewa" value="{{ $tarif->id_tarif_sewa }}">
            <input type="hidden" name="antrian" value="{{ request('antrian') }}">
            @if (($editIndex ?? null) !== null)
                <input type="hidden" name="edit_index" value="{{ $editIndex }}">
            @endif

            @php
                // Error per-field (keperluan/tanggal/jam/dokumen) sudah tampil di bawah masing-
                // masing input — sisanya (mis. kapasitas/bentrok jadwal per ruangan, kunci numerik
                // dari $galat, atau 'antrian'/'edit_index') ditampilkan di sini sebagai daftar umum.
                $medanDikenal = ['keperluan', 'tanggal_mulai', 'tanggal_selesai', 'jam_mulai', 'jam_selesai', 'dokumen'];
                $errUmum = collect($errors->getMessages())
                    ->filter(fn ($pesan, $kunci) => ! collect($medanDikenal)->contains(fn ($m) => $kunci === $m || str_starts_with((string) $kunci, $m.'.')))
                    ->flatten();
            @endphp
            @if ($errUmum->isNotEmpty())
                {{-- Error umum (bukan milik satu field, mis. jadwal bentrok/kapasitas) ditampilkan
                     lewat pop-up, bukan kotak di atas form — biar form tetap ringkas, pesan field
                     lain tetap cukup di bawah masing-masing input. --}}
                <script>
                    {{-- Ditunggu sampai DOMContentLoaded karena script ini dirender sebelum
                         <script src="sweetalert2.all.min.js"> di layout. --}}
                    document.addEventListener('DOMContentLoaded', () => {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Tidak bisa disimpan',
                            html: @json($errUmum->map(fn ($p) => e($p))->implode('<br>')),
                            confirmButtonColor: '#176b87',
                            confirmButtonText: 'Oke, mengerti',
                        });
                    });
                </script>
            @endif

            {{-- Data pemesan dari profil (hidden — bisa diubah di halaman Profil) --}}
            <input type="hidden" name="nama_lengkap" value="{{ $pemesan->nama_lengkap }}">
            <input type="hidden" name="usia" value="{{ $pemesan->usia }}">
            <input type="hidden" name="no_telepon" value="{{ $pemesan->no_telepon }}">
            <input type="hidden" name="pekerjaan" value="{{ $pemesan->pekerjaan }}">
            <input type="hidden" name="alamat" value="{{ $pemesan->alamat }}">
            <input type="hidden" name="jumlah_pengguna" value="1">

            {{-- ── KEPERLUAN ── --}}
            <div class="aj-section-head">
                <span class="aj-section-label"><i class="bi bi-chat-square-text-fill"></i>Keperluan / Kegiatan</span>
                <span class="aj-section-hint">Maksimal 200 karakter</span>
            </div>
            <textarea name="keperluan" class="aj-input @error('keperluan') is-invalid @enderror" rows="2"
                      placeholder="Contoh: Pengerjaan proyek pengembangan aplikasi mobile"
                      maxlength="200" required>{{ old('keperluan', $editItem['keperluan'] ?? '') }}</textarea>
            @error('keperluan') <div class="aj-field-err">{{ $message }}</div> @enderror

            {{-- ══════ JADWAL PEMAKAIAN ══════ --}}
            @if ($satuan === 'Jam')
                {{-- MODE PER JAM --}}
                <div class="aj-section-head mt-3">
                    <span class="aj-section-label"><i class="bi bi-calendar-check-fill"></i>Tanggal Pemakaian</span>
                    <span class="aj-section-hint">Layanan gedung 08:00 – 16:00 WIB</span>
                </div>
                <input type="date" name="tanggal_mulai" class="aj-input @error('tanggal_mulai') is-invalid @enderror" id="ajTanggal" required
                       min="{{ now()->toDateString() }}"
                       value="{{ old('tanggal_mulai', $editItem['tanggal_mulai'] ?? request('tanggal_mulai')) }}">
                @error('tanggal_mulai') <div class="aj-field-err">{{ $message }}</div> @enderror

                <div class="aj-section-head mt-3">
                    <span class="aj-section-label"><i class="bi bi-clock-fill"></i>Jam Pemakaian</span>
                    <span class="aj-section-hint">Pilih jam mulai &amp; selesai</span>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="aj-sublabel">Jam Mulai</label>
                        @include('reservasi.partials.pilih-jam', [
                            'name' => 'jam_mulai', 'value' => old('jam_mulai', $editItem['jam_mulai'] ?? null),
                            'fasilitasId' => $fasilitas->id_fasilitas, 'terisiAwal' => $jamTerisi,
                        ])
                        @error('jam_mulai') <div class="aj-field-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="aj-sublabel">Jam Selesai</label>
                        @include('reservasi.partials.pilih-jam', ['name' => 'jam_selesai', 'value' => old('jam_selesai', $editItem['jam_selesai'] ?? null)])
                        @error('jam_selesai') <div class="aj-field-err">{{ $message }}</div> @enderror
                    </div>
                </div>

            @elseif ($satuan === 'Bulan')
                {{-- MODE BULANAN --}}
                <div class="aj-section-head mt-3">
                    <span class="aj-section-label"><i class="bi bi-calendar3-range-fill"></i>Periode Sewa Bulanan</span>
                    <span class="aj-section-hint">Minimal {{ $jenis->durasi_minimum ?? 1 }} bulan</span>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="aj-sublabel">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="aj-input @error('tanggal_mulai') is-invalid @enderror" id="ajTanggalMulai" required
                               min="{{ now()->toDateString() }}"
                               value="{{ old('tanggal_mulai', $editItem['tanggal_mulai'] ?? request('tanggal_mulai')) }}">
                        @error('tanggal_mulai') <div class="aj-field-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="aj-sublabel">Tanggal Berakhir</label>
                        <input type="date" name="tanggal_selesai" class="aj-input @error('tanggal_selesai') is-invalid @enderror" id="ajTanggalSelesai" required
                               value="{{ old('tanggal_selesai', $editItem['tanggal_selesai'] ?? request('tanggal_selesai')) }}">
                        @error('tanggal_selesai') <div class="aj-field-err">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- ── UPLOAD DOKUMEN untuk BULANAN — satu unggahan, boleh pilih beberapa berkas
                     sekaligus (Ctrl/Shift+klik), bukan slot terpisah per jenis dokumen ── --}}
                <div class="aj-section-head mt-3">
                    <span class="aj-section-label"><i class="bi bi-file-earmark-arrow-up-fill"></i>Dokumen Persyaratan</span>
                    <span class="aj-section-hint">PDF/JPG/PNG · maks 5 MB per file</span>
                </div>
                <input type="file" name="dokumen[]" class="aj-input @error('dokumen') is-invalid @enderror"
                       accept=".pdf,.jpg,.jpeg,.png" multiple required>
                <div class="aj-upload-note"><i class="bi bi-info-circle"></i>Boleh pilih beberapa berkas sekaligus (mis. KTP, Proposal/NIB).</div>
                @error('dokumen') <div class="aj-field-err">{{ $message }}</div> @enderror

            @else
                {{-- MODE HARIAN --}}
                <div class="aj-section-head mt-3">
                    <span class="aj-section-label"><i class="bi bi-calendar-check-fill"></i>Tanggal Pemakaian</span>
                    <span class="aj-section-hint">
                        @if ($sehariSaja) 1 hari (08:00 – 16:00 WIB) @else Pilih tanggal mulai &amp; selesai @endif
                    </span>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="aj-sublabel">{{ $sehariSaja ? 'Tanggal Pemakaian' : 'Tanggal Mulai' }}</label>
                        <input type="date" name="tanggal_mulai" class="aj-input @error('tanggal_mulai') is-invalid @enderror" id="ajTanggalMulai" required
                               min="{{ now()->toDateString() }}"
                               value="{{ old('tanggal_mulai', $editItem['tanggal_mulai'] ?? request('tanggal_mulai')) }}">
                        @error('tanggal_mulai') <div class="aj-field-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        @if (! $sehariSaja)
                            <label class="aj-sublabel">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" class="aj-input @error('tanggal_selesai') is-invalid @enderror" id="ajTanggalSelesai" required
                                   value="{{ old('tanggal_selesai', $editItem['tanggal_selesai'] ?? request('tanggal_selesai')) }}">
                            @error('tanggal_selesai') <div class="aj-field-err">{{ $message }}</div> @enderror
                        @else
                            <label class="aj-sublabel">Jam Layanan</label>
                            <div class="aj-info-slot">
                                <i class="bi bi-clock-history"></i>
                                08:00 – 16:00 WIB (8 jam)
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- ══════ RINCIAN BIAYA ══════ --}}
            <div class="aj-section-head mt-3">
                <span class="aj-section-label"><i class="bi bi-receipt-cutoff"></i>Rincian Biaya</span>
                <span class="aj-section-hint">Diperbarui otomatis</span>
            </div>

            <div class="aj-rincian">
                <div class="aj-rincian-row">
                    <div class="aj-rincian-lbl">
                        Tarif {{ $isMulti ? "($jumlahRuangan ruangan)" : '' }}
                        <span class="aj-rincian-sub">Rp {{ number_format($tarif->harga, 0, ',', '.') }} / {{ $satuan }}@if($isMulti) × {{ $jumlahRuangan }} @endif</span>
                    </div>
                    <div class="aj-rincian-val" id="ajTarifSubtotal">
                        Rp {{ number_format($totalTarif, 0, ',', '.') }}
                    </div>
                </div>
                <div class="aj-rincian-row">
                    <div class="aj-rincian-lbl">
                        Durasi Sewa
                        <span class="aj-rincian-sub" id="ajDurasiSub">Pilih jadwal untuk melihat durasi</span>
                    </div>
                    <div class="aj-rincian-val" id="ajDurasiVal">– {{ strtolower($satuan) }}</div>
                </div>
            </div>

            <div class="aj-total-row">
                <div>
                    <span class="aj-total-lbl">Total Biaya</span>
                    <span class="aj-total-sub">Sudah termasuk fasilitas bawaan</span>
                </div>
                <div class="aj-total-val" id="ajTotalVal">
                    Rp {{ number_format($totalTarif, 0, ',', '.') }}
                </div>
            </div>

            {{-- ══════ FOOTER BUTTONS ══════ --}}
            <div class="aj-foot">
                <button type="button" class="aj-btn-cancel" id="fpFormCloseBottom">Batal</button>
                <button type="submit" class="aj-btn-submit">
                    <i class="bi bi-cart-plus-fill"></i>
                    Masukkan ke Keranjang
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    /* ══════════════════════════════════════════════════════════
       MODAL ATUR JADWAL SEWA — clean & refined
       ══════════════════════════════════════════════════════════ */

    .fp-form-overlay {
        position:fixed; inset:0; z-index:1990; background:rgba(8,15,25,.55); backdrop-filter:blur(3px);
        display:none; align-items:flex-start; justify-content:center; padding:2.5rem 1rem; overflow-y:auto;
    }
    .fp-form-overlay.show { display:flex; }

    .aj-modal {
        position:relative; background:#fff; border-radius:1.5rem; width:100%; max-width:640px;
        margin:auto 0; box-shadow:0 30px 70px -12px rgba(8,15,25,.5);
        animation:ajIn .25s cubic-bezier(.2,.8,.3,1) both;
        border:1px solid var(--line); overflow:hidden;
    }
    @keyframes ajIn { from { opacity:0; transform:translateY(16px) scale(.98); } to { opacity:1; transform:none; } }

    .aj-close {
        position:absolute; top:1rem; right:1rem; width:2rem; height:2rem; border-radius:50%;
        border:0; background:rgba(255,255,255,.9); color:var(--muted);
        display:grid; place-items:center; cursor:pointer; z-index:5;
        transition:background .15s ease, color .15s ease; box-shadow:0 4px 10px rgba(15,23,42,.1);
    }
    .aj-close:hover { background:var(--rose-tint); color:var(--rose); }

    /* ─── HEADER ─── */
    .aj-head { padding:1.5rem 1.75rem 1rem;
        background:linear-gradient(135deg, var(--surface), #fff); border-bottom:1px solid var(--line-soft); }
    .aj-head-l { display:flex; align-items:flex-start; gap:.85rem; }
    .aj-head-ic { display:grid; place-items:center; width:2.75rem; height:2.75rem; border-radius:.85rem;
        background:linear-gradient(135deg, var(--primary-dark), var(--primary));
        color:#fff; font-size:1.15rem; flex:none;
        box-shadow:0 8px 18px -4px rgba(23,107,135,.45); }
    .aj-head-body { min-width:0; flex:1; padding-right:2rem; }
    .aj-head-title { font-size:1.1rem; font-weight:800; color:var(--ink); margin:0;
        display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; letter-spacing:-.01em; }
    .aj-head-tag { display:inline-flex; align-items:center; gap:.3rem;
        background:var(--primary-soft); color:var(--primary-dark);
        font-size:.68rem; font-weight:800; padding:.2rem .55rem; border-radius:.5rem;
        border:1px solid var(--primary-softer); letter-spacing:.02em; }
    .aj-head-tag i { font-size:.85em; }
    .aj-head-sub { font-size:.78rem; color:var(--muted); margin:.35rem 0 0; }

    /* ─── FACILITY INFO BAR ─── */
    .aj-fac-bar { padding:.95rem 1.75rem; display:flex; align-items:center; justify-content:space-between;
        gap:1rem; background:var(--primary-soft); border-bottom:1px solid var(--primary-softer); flex-wrap:wrap; }
    .aj-fac-l { display:flex; align-items:center; gap:.75rem; min-width:0; flex:1; }
    .aj-fac-ic { display:grid; place-items:center; width:2.2rem; height:2.2rem; border-radius:.6rem;
        background:#fff; color:var(--primary-dark); font-size:.9rem; flex:none;
        border:1px solid var(--primary-softer); }
    .aj-fac-nm { font-size:.85rem; font-weight:800; color:var(--ink); line-height:1.2;
        display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; }
    .aj-fac-sep { color:var(--soft); font-weight:400; }
    .aj-fac-luas { font-size:.72rem; font-weight:600; color:var(--muted); }
    .aj-fac-sub { font-size:.7rem; color:var(--muted); font-weight:600; margin-top:.15rem;
        display:inline-flex; align-items:center; gap:.3rem; }
    .aj-fac-sub i { color:var(--primary); }
    .aj-fac-r { text-align:right; flex:none; }
    .aj-fac-r small { display:block; font-size:.6rem; font-weight:800; letter-spacing:.08em;
        text-transform:uppercase; color:var(--muted); margin-bottom:.15rem; }
    .aj-fac-price { font-size:1rem; font-weight:800; color:var(--primary-dark); letter-spacing:-.01em; }
    .aj-fac-satuan { font-size:.7rem; font-weight:600; color:var(--muted); }

    /* ─── SECTION HEADERS ─── */
    .aj-section-head { display:flex; align-items:center; justify-content:space-between; gap:.5rem;
        padding:1rem 1.75rem .5rem; flex-wrap:wrap; }
    .aj-section-label { display:inline-flex; align-items:center; gap:.4rem;
        font-size:.7rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase;
        color:var(--primary-dark); }
    .aj-section-label i { font-size:.9em; }
    .aj-section-hint { font-size:.65rem; color:var(--soft); font-weight:600; }

    /* ─── JENIS SEWA TABS ─── */
    .aj-jenis-tabs { display:grid; grid-template-columns:repeat(auto-fit, minmax(9rem, 1fr));
        gap:.5rem; padding:0 1.75rem; }
    .aj-jenis-tab { display:flex; align-items:center; justify-content:center; gap:.4rem;
        padding:.7rem 1rem; border-radius:.7rem; border:1px solid var(--line);
        background:#fff; color:var(--muted); font-size:.82rem; font-weight:700; text-decoration:none;
        transition:all .15s ease; position:relative; }
    .aj-jenis-tab:hover { border-color:var(--primary); color:var(--primary-dark);
        background:var(--primary-soft); transform:translateY(-1px); }
    .aj-jenis-tab.active { background:linear-gradient(135deg, var(--primary-dark), var(--primary));
        color:#fff; border-color:var(--primary-dark); font-weight:800;
        box-shadow:0 8px 18px -6px rgba(23,107,135,.45); }
    .aj-jenis-tab.active:hover { background:linear-gradient(135deg, var(--primary-darker), var(--primary-dark)); color:#fff; }
    .aj-jenis-tab i { font-size:1em; }
    .aj-jenis-check { font-size:.9em; }

    /* ─── FORM INPUTS ─── */
    .aj-form { padding:0 1.75rem 1.75rem; }
    .aj-input { display:block; width:100%; padding:.65rem .85rem;
        border:1px solid var(--line); border-radius:.7rem; background:#fff;
        font-size:.85rem; font-weight:600; color:var(--ink);
        transition:border-color .15s ease, box-shadow .15s ease; outline:none; }
    .aj-input:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(23,107,135,.12); }
    .aj-input.is-invalid { border-color:var(--rose); }
    .aj-field-err { font-size:.72rem; font-weight:600; color:var(--rose); margin:.35rem 0 0; }
    textarea.aj-input { resize:vertical; min-height:2.4rem; }
    .aj-form > .aj-input { margin:0 0 .35rem; }
    .aj-sublabel { display:block; font-size:.62rem; font-weight:800; letter-spacing:.08em;
        text-transform:uppercase; color:var(--muted); margin:0 0 .35rem; padding-left:.15rem; }

    .aj-info-slot { display:inline-flex; align-items:center; gap:.4rem; width:100%;
        padding:.65rem .85rem; border:1px solid var(--line); border-radius:.7rem;
        background:var(--surface); font-size:.82rem; color:var(--ink); font-weight:600; }
    .aj-info-slot i { color:var(--primary); }

    /* ─── UPLOAD DOKUMEN — satu input, boleh pilih beberapa berkas sekaligus (bulanan) ─── */
    .aj-upload-note { display:flex; align-items:center; gap:.4rem; font-size:.7rem; font-weight:600;
        color:var(--muted); margin:.4rem 0 0; }
    .aj-upload-note i { color:var(--primary); }

    /* ─── RINCIAN & TOTAL ─── */
    .aj-rincian { background:var(--surface); border:1px solid var(--line);
        border-radius:.9rem; padding:.85rem 1rem; margin-bottom:.85rem; }
    .aj-rincian-row { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem;
        padding:.4rem 0; border-bottom:1px dashed var(--line); }
    .aj-rincian-row:last-child { border-bottom:0; padding-bottom:0; }
    .aj-rincian-row:first-child { padding-top:0; }
    .aj-rincian-lbl { font-size:.8rem; font-weight:700; color:var(--ink); }
    .aj-rincian-sub { display:block; font-size:.68rem; color:var(--muted); font-weight:500; margin-top:.15rem; }
    .aj-rincian-val { font-size:.85rem; font-weight:800; color:var(--ink); text-align:right; }

    .aj-total-row { display:flex; justify-content:space-between; align-items:center; gap:1rem;
        padding:1rem 1.15rem; border-radius:1rem;
        background:linear-gradient(135deg, var(--primary-darker), var(--primary));
        color:#fff; box-shadow:0 10px 22px -8px rgba(23,107,135,.4); }
    .aj-total-lbl { display:block; font-size:.68rem; font-weight:800; letter-spacing:.08em;
        text-transform:uppercase; color:#a7f3d0; margin-bottom:.15rem; }
    .aj-total-sub { display:block; font-size:.65rem; font-weight:500; color:rgba(255,255,255,.7); }
    .aj-total-val { font-size:1.5rem; font-weight:800; color:#fff; letter-spacing:-.02em;
        text-align:right; line-height:1.1; transition:transform .2s ease; }
    .aj-total-val.updated { animation:pulseNum .4s ease; }
    @keyframes pulseNum { 50% { transform:scale(1.05); } }

    /* ─── FOOTER BUTTONS ─── */
    .aj-foot { display:flex; align-items:center; justify-content:space-between; gap:.65rem;
        margin-top:1.25rem; padding-top:1rem; border-top:1px solid var(--line-soft); }
    .aj-btn-cancel { padding:.75rem 1.5rem; border:1px solid var(--line); background:#fff;
        color:var(--muted); font-size:.82rem; font-weight:700; border-radius:.85rem;
        cursor:pointer; transition:all .15s ease; }
    .aj-btn-cancel:hover { background:var(--surface); color:var(--ink); border-color:#cbd5e1; }
    .aj-btn-submit { flex:1; padding:.85rem 1.5rem; border:0;
        background:linear-gradient(135deg, var(--primary-dark), var(--primary));
        color:#fff; font-size:.9rem; font-weight:800; border-radius:.85rem;
        display:inline-flex; align-items:center; justify-content:center; gap:.45rem;
        cursor:pointer; transition:transform .15s ease, box-shadow .15s ease;
        box-shadow:0 10px 22px -6px rgba(23,107,135,.4); }
    .aj-btn-submit:hover { transform:translateY(-1px);
        background:linear-gradient(135deg, var(--primary-darker), var(--primary-dark));
        box-shadow:0 14px 28px -6px rgba(23,107,135,.5); }
    .aj-btn-submit i { font-size:.95em; }

    @media (max-width: 575.98px) {
        .aj-head, .aj-fac-bar, .aj-section-head, .aj-form { padding-left:1.15rem; padding-right:1.15rem; }
        .aj-jenis-tabs { padding-left:1.15rem; padding-right:1.15rem; }
        .aj-total-val { font-size:1.15rem; }
        .aj-head-body { padding-right:1.5rem; }
    }
</style>

<script>
(function() {
    // Tombol Batal bawah = sama seperti tombol X (tutup overlay)
    document.getElementById('fpFormCloseBottom')?.addEventListener('click', () => {
        document.getElementById('fpFormOverlay')?.classList.remove('show');
        document.body.style.overflow = '';
    });

    // ═══ AUTO-CALCULATE TOTAL BIAYA ═══
    // Update rincian + total real-time berdasarkan pilihan jadwal.
    const form = document.querySelector('.aj-form');
    if (!form) return;

    const tarif = parseFloat(form.dataset.tarif) || 0;
    const jumlahRuangan = parseInt(form.dataset.jumlahRuangan) || 1;
    const satuan = form.dataset.satuan; // Jam | Hari | Bulan
    const sehariSaja = form.dataset.sehariSaja === '1';
    const tarifTotal = tarif * jumlahRuangan;

    const durasiSub = document.getElementById('ajDurasiSub');
    const durasiVal = document.getElementById('ajDurasiVal');
    const totalVal = document.getElementById('ajTotalVal');

    const fmt = (n) => 'Rp ' + (n || 0).toLocaleString('id-ID');

    const setTotal = (durasi) => {
        const total = tarifTotal * durasi;
        totalVal.textContent = fmt(total);
        totalVal.classList.remove('updated');
        void totalVal.offsetWidth;
        totalVal.classList.add('updated');

        const satuanLower = satuan.toLowerCase();
        durasiVal.textContent = durasi > 0 ? `${durasi} ${satuanLower}` : `– ${satuanLower}`;
        durasiSub.textContent = durasi > 0
            ? `${fmt(tarifTotal)} × ${durasi}`
            : 'Pilih jadwal untuk melihat durasi';
    };

    // ─── PER JAM ───
    if (satuan === 'Jam') {
        const jamMulai = form.querySelector('[name="jam_mulai"]');
        const jamSelesai = form.querySelector('[name="jam_selesai"]');
        const hitung = () => {
            const m = parseInt((jamMulai?.value || '').split(':')[0], 10);
            const s = parseInt((jamSelesai?.value || '').split(':')[0], 10);
            const durasi = (isFinite(m) && isFinite(s) && s > m) ? s - m : 0;
            setTotal(durasi);
        };
        [jamMulai, jamSelesai].forEach(el => {
            el?.addEventListener('change', hitung);
            el?.addEventListener('input', hitung);
        });
        hitung(); // Panggil awal (kalau ada old value)
    }

    // ─── PER HARI ───
    else if (satuan === 'Hari') {
        if (sehariSaja) {
            // Convention Hall / harian sekali = 1 hari fixed
            const tanggal = document.getElementById('ajTanggalMulai');
            const hitung = () => setTotal(tanggal?.value ? 1 : 0);
            tanggal?.addEventListener('change', hitung);
            hitung();
        } else {
            const mulai = document.getElementById('ajTanggalMulai');
            const selesai = document.getElementById('ajTanggalSelesai');
            const hitung = () => {
                const t1 = mulai?.value ? new Date(mulai.value) : null;
                const t2 = selesai?.value ? new Date(selesai.value) : null;
                let durasi = 0;
                if (t1 && t2 && t2 >= t1) {
                    durasi = Math.round((t2 - t1) / (1000 * 60 * 60 * 24)) + 1;
                }
                setTotal(durasi);
            };
            [mulai, selesai].forEach(el => el?.addEventListener('change', hitung));
            hitung();
        }
    }

    // ─── PER BULAN ───
    else if (satuan === 'Bulan') {
        const mulai = document.getElementById('ajTanggalMulai');
        const selesai = document.getElementById('ajTanggalSelesai');
        const hitung = () => {
            const t1 = mulai?.value ? new Date(mulai.value) : null;
            const t2 = selesai?.value ? new Date(selesai.value) : null;
            let durasi = 0;
            if (t1 && t2 && t2 >= t1) {
                // Selisih bulan (rounded up jika sisa hari > 0)
                const bulan = (t2.getFullYear() - t1.getFullYear()) * 12 + (t2.getMonth() - t1.getMonth());
                durasi = Math.max(1, bulan + (t2.getDate() >= t1.getDate() ? 0 : 0));
                if (bulan === 0) durasi = 1;
                else durasi = bulan + (t2.getDate() > t1.getDate() ? 1 : 0);
                if (durasi < 1) durasi = 1;
            }
            setTotal(durasi);
        };
        [mulai, selesai].forEach(el => el?.addEventListener('change', hitung));
        hitung();

        // Auto-set tanggal berakhir = tanggal mulai + durasi minimum
        const durasiMin = {{ $satuan === 'Bulan' ? ($jenis->durasi_minimum ?? 1) : 1 }};
        mulai?.addEventListener('change', () => {
            if (!mulai.value || selesai.value) return;
            const t = new Date(mulai.value);
            t.setMonth(t.getMonth() + durasiMin);
            selesai.value = t.toISOString().slice(0, 10);
            hitung();
        });
    }

    // ═══ VALIDASI UKURAN FILE (dokumen bulanan, boleh pilih beberapa sekaligus) ═══
    document.querySelectorAll('input[name="dokumen[]"]').forEach(input => {
        input.addEventListener('change', () => {
            const kebesaran = [...input.files].filter(f => f.size > 5 * 1024 * 1024);
            if (kebesaran.length) {
                Swal.fire({
                    icon: 'warning', title: 'File terlalu besar',
                    text: 'Ukuran maksimal 5 MB per file.',
                    confirmButtonColor: '#176b87'
                });
                input.value = '';
            }
        });
    });
})();
</script>