{{--
    Modal "Atur Jadwal Sewa" — dibuka dari halaman Detail Fasilitas.

    Variabel dari fasilitas.blade.php:
    - $fasilitas, $tarif, $jenis, $satuan, $sehariSaja
    - $isMulti, $jumlahRuangan, $totalTarif (jumlah tarif seluruh ruangan terpilih)
    - $semuaTarif (tab Jenis Sewa), $kapMin (kapasitas terkecil di antara ruangan terpilih)
    - $kondisi (kondisi fasilitas pada $tanggalAcuan: status, label, bisa[satuan], jam_terisi)
    - $pemesan, $editItem, $editIndex
--}}

@php
    $durasiMin = $satuan === 'Bulan' ? max(1, (int) ($jenis->durasi_minimum ?? 1)) : 1;
    $tanggalDiminta = request()->filled('tanggal_mulai') ? $tanggalAcuan : null;
    $nilaiTanggalMulai = old('tanggal_mulai', $editItem['tanggal_mulai'] ?? $tanggalDiminta);
    $nilaiTanggalSelesai = old('tanggal_selesai', $editItem['tanggal_selesai'] ?? ($satuan === 'Jam' ? null : request('tanggal_selesai')));
    $ikonSatuan = ['Jam' => 'bi-clock', 'Hari' => 'bi-calendar-date', 'Bulan' => 'bi-calendar3-range'];
    $pilihanBulan = collect([$durasiMin, $durasiMin + 1, 6, 12])->filter(fn ($b) => $b >= $durasiMin)->unique()->sort()->values();

    // Error yang sudah punya tempat sendiri di form (di bawah kolomnya / kotak jadwal) tidak
    // diulang di pop-up; sisanya (mis. data profil belum lengkap, item keranjang tidak
    // ditemukan) ditampilkan lewat pop-up.
    $medanDikenal = ['keperluan', 'jumlah_pengguna', 'tanggal_mulai', 'tanggal_selesai', 'jam_mulai', 'jam_selesai', 'dokumen', 'jadwal'];
    $errUmum = collect($errors->getMessages())
        ->filter(fn ($pesan, $kunci) => ! collect($medanDikenal)->contains(fn ($m) => $kunci === $m || str_starts_with((string) $kunci, $m.'.')))
        ->flatten();
    $errJadwal = collect($errors->get('jadwal'))->flatten();
@endphp

<div class="fp-form-overlay" id="fpFormOverlay">
    <div class="aj-modal" role="dialog" aria-modal="true" aria-labelledby="ajJudul">
        <button type="button" class="aj-close" id="fpFormClose" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>

        {{-- ══════ HEADER ══════ --}}
        <div class="aj-head">
            <span class="aj-head-ic"><i class="bi bi-calendar2-week-fill"></i></span>
            <div class="aj-head-body">
                <h2 class="aj-head-title" id="ajJudul">Atur Jadwal Sewa</h2>
                <p class="aj-head-sub">Lengkapi jadwal dan keperluan pemakaian, lalu masukkan ke keranjang.</p>
            </div>
        </div>

        {{-- ══════ INFO FASILITAS ══════ --}}
        <div class="aj-fac-bar">
            <div class="aj-fac-l">
                <span class="aj-fac-ic"><i class="bi {{ $meta['ikon'] ?? 'bi-door-open-fill' }}"></i></span>
                <div class="aj-fac-txt">
                    <div class="aj-fac-nm">{{ $isMulti ? "$jumlahRuangan Fasilitas Terpilih" : $fasilitas->nama_fasilitas }}</div>
                    <div class="aj-fac-sub">
                        <span><i class="bi bi-people"></i>Maks. {{ $kapMin }} orang</span>
                        @unless ($isMulti)
                            <span><i class="bi bi-geo-alt"></i>Lantai {{ $fasilitas->lantai->nomor_lantai ?? '-' }}</span>
                        @endunless
                    </div>
                </div>
            </div>
            <div class="aj-fac-r">
                <small>Tarif{{ $isMulti ? ' total' : '' }}</small>
                <div class="aj-fac-price">
                    Rp {{ number_format($totalTarif, 0, ',', '.') }}<span class="aj-fac-satuan">/ {{ strtolower($satuan) }}</span>
                </div>
            </div>
        </div>

        {{-- ══════ JENIS SEWA — tab dinonaktifkan kalau kondisi fasilitas pada tanggal yang
             dipilih tidak mengizinkan jenis sewa itu (lihat AvailabilityService::bisaDipesan) ══════ --}}
        @if ($semuaTarif->count() > 1)
            <div class="aj-block">
                <span class="aj-label"><i class="bi bi-tag-fill"></i>Jenis Sewa</span>
                <div class="aj-jenis-tabs" id="ajJenisTabs">
                    @foreach ($semuaTarif as $t)
                        @php
                            $s = $t->jenisSewa->satuan->value;
                            $aktif = $t->id_jenis_sewa === $jenis->id_jenis_sewa;
                            // Tanpa tanggal terpilih belum ada yang perlu dikunci (diperbarui JS begitu tanggal diisi).
                            $bisa = $nilaiTanggalMulai ? ($kondisi['bisa'][$s] ?? true) : true;
                            $jenisTabParams = ['fasilitas' => $fasilitas->id_fasilitas, 'jenis' => $t->id_jenis_sewa, 'buka' => 1];
                            if (request()->filled('antrian')) $jenisTabParams['antrian'] = request('antrian');
                            if (($editIndex ?? null) !== null) $jenisTabParams['edit_index'] = $editIndex;
                        @endphp
                        <a href="{{ route('reservasi.fasilitas.show', $jenisTabParams) }}"
                           data-satuan="{{ $s }}"
                           class="aj-jenis-tab {{ $aktif ? 'active' : '' }} {{ $bisa ? '' : 'is-disabled' }}"
                           @unless ($bisa) aria-disabled="true" @endunless>
                            <i class="bi {{ $ikonSatuan[$s] ?? 'bi-tag' }}"></i>
                            <span>Per {{ $s }}</span>
                        </a>
                    @endforeach
                </div>
                <p class="aj-hint" id="ajJenisHint" hidden></p>
            </div>
        @endif

        {{-- ══════ FORM ══════ --}}
        <form method="POST" action="{{ route('reservasi.keranjang.tambah') }}"
              class="aj-form" enctype="multipart/form-data"
              data-tarif-total="{{ (float) $totalTarif }}"
              data-satuan="{{ $satuan }}"
              data-sehari-saja="{{ $sehariSaja ? '1' : '0' }}"
              data-durasi-min="{{ $durasiMin }}"
              data-cek-url="{{ route('reservasi.fasilitas.cek-jadwal', $fasilitas) }}"
              data-antrian="{{ request('antrian') }}"
              data-edit-index="{{ $editIndex ?? '' }}">
            @csrf
            <input type="hidden" name="id_fasilitas" value="{{ $fasilitas->id_fasilitas }}">
            <input type="hidden" name="id_tarif_sewa" value="{{ $tarif->id_tarif_sewa }}">
            <input type="hidden" name="antrian" value="{{ request('antrian') }}">
            @if (($editIndex ?? null) !== null)
                <input type="hidden" name="edit_index" value="{{ $editIndex }}">
            @endif

            {{-- Data pemesan diambil dari profil (dapat diubah di halaman Profil). --}}
            <input type="hidden" name="nama_lengkap" value="{{ $pemesan->nama_lengkap }}">
            <input type="hidden" name="usia" value="{{ $pemesan->usia }}">
            <input type="hidden" name="no_telepon" value="{{ $pemesan->no_telepon }}">
            <input type="hidden" name="pekerjaan" value="{{ $pemesan->pekerjaan }}">
            <input type="hidden" name="alamat" value="{{ $pemesan->alamat }}">

            {{-- ── KEPERLUAN ── --}}
            <div class="aj-field">
                <label class="aj-sublabel" for="ajKeperluan">Keperluan / Kegiatan</label>
                <textarea name="keperluan" id="ajKeperluan" class="aj-input @error('keperluan') is-invalid @enderror" rows="2"
                          placeholder="Contoh: Rapat koordinasi tim pengembangan aplikasi"
                          minlength="5" maxlength="1000" required>{{ old('keperluan', $editItem['keperluan'] ?? '') }}</textarea>
                @error('keperluan') <div class="aj-field-err">{{ $message }}</div> @enderror
            </div>

            {{-- ── JUMLAH PENGGUNA — dibatasi kapasitas fasilitas (validasi ulang di server) ── --}}
            <div class="aj-field">
                <label class="aj-sublabel" for="ajJumlahPengguna">Jumlah Pengguna</label>
                <div class="aj-input-suffix">
                    <input type="number" name="jumlah_pengguna" id="ajJumlahPengguna" inputmode="numeric"
                           class="aj-input @error('jumlah_pengguna') is-invalid @enderror"
                           min="1" max="{{ $kapMin }}" step="1" required
                           placeholder="Masukkan jumlah orang"
                           data-pesan-maks="Jumlah pengguna melebihi kapasitas maksimal {{ $kapMin }} orang."
                           data-pesan-min="Jumlah pengguna minimal 1 orang."
                           value="{{ old('jumlah_pengguna', $editItem['jumlah_pengguna'] ?? '') }}">
                    <span class="aj-suffix">orang</span>
                </div>
                @error('jumlah_pengguna') <div class="aj-field-err">{{ $message }}</div> @enderror
                <p class="aj-hint"><i class="bi bi-people-fill"></i>Kapasitas maksimal: <b>{{ $kapMin }} orang</b>{{ $isMulti ? ' (kapasitas terkecil di antara fasilitas terpilih)' : '' }}</p>
            </div>

            {{-- ══════ JADWAL PEMAKAIAN ══════ --}}
            @if ($satuan === 'Jam')
                <div class="aj-field">
                    <label class="aj-sublabel" for="ajTanggalMulai">Tanggal Pemakaian</label>
                    <input type="date" name="tanggal_mulai" class="aj-input @error('tanggal_mulai') is-invalid @enderror" id="ajTanggalMulai" required
                           min="{{ now()->toDateString() }}" value="{{ $nilaiTanggalMulai }}">
                    @error('tanggal_mulai') <div class="aj-field-err">{{ $message }}</div> @enderror
                </div>

                <div class="aj-row">
                    <div class="aj-field">
                        <label class="aj-sublabel">Jam Mulai</label>
                        @include('reservasi.partials.pilih-jam', [
                            'name' => 'jam_mulai', 'value' => old('jam_mulai', $editItem['jam_mulai'] ?? null),
                            'fasilitasId' => $fasilitas->id_fasilitas, 'antrian' => request('antrian'),
                            'terisiAwal' => $nilaiTanggalMulai ? $jamTerisi : [],

                        ])
                        @error('jam_mulai') <div class="aj-field-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="aj-field">
                        <label class="aj-sublabel">Jam Selesai</label>
                        @include('reservasi.partials.pilih-jam', ['name' => 'jam_selesai', 'value' => old('jam_selesai', $editItem['jam_selesai'] ?? null), 'kanan' => true])
                        @error('jam_selesai') <div class="aj-field-err">{{ $message }}</div> @enderror
                    </div>
                </div>
                <p class="aj-hint"><i class="bi bi-clock-history"></i>Jam operasional 08.00–16.00 WIB. Jam yang sudah terisi tidak dapat dipilih.</p>

            @elseif ($satuan === 'Bulan')
                <div class="aj-row">
                    <div class="aj-field">
                        <label class="aj-sublabel" for="ajTanggalMulai">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="aj-input @error('tanggal_mulai') is-invalid @enderror" id="ajTanggalMulai" required
                               min="{{ now()->toDateString() }}" value="{{ $nilaiTanggalMulai }}">
                        @error('tanggal_mulai') <div class="aj-field-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="aj-field">
                        <label class="aj-sublabel" for="ajTanggalSelesai">Tanggal Berakhir</label>
                        <input type="date" name="tanggal_selesai" class="aj-input @error('tanggal_selesai') is-invalid @enderror" id="ajTanggalSelesai" required
                               value="{{ $nilaiTanggalSelesai }}">
                        @error('tanggal_selesai') <div class="aj-field-err">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="aj-durasi-chips" role="group" aria-label="Pilih lama sewa">
                    <span class="aj-durasi-lbl">Lama sewa</span>
                    @foreach ($pilihanBulan as $b)
                        <button type="button" class="aj-durasi-chip" data-bulan="{{ $b }}">{{ $b }} bulan</button>
                    @endforeach
                </div>
                <p class="aj-hint"><i class="bi bi-info-circle"></i>Masa sewa minimal {{ $durasiMin }} bulan dan dapat diperpanjang sesuai kebutuhan. Tanggal berakhir diisi otomatis dan tetap dapat diubah.</p>

                {{-- ── DOKUMEN PERSYARATAN — satu daftar berlaku untuk SEMUA fasilitas sewa bulanan
                     di keranjang (lihat CartService::dokumen()). ── --}}
                @php $dokumenTersimpan = app(\App\Services\CartService::class)->dokumen(); @endphp
                <div class="aj-field">
                    <span class="aj-sublabel">Dokumen Persyaratan</span>
                    <div class="aj-dok-drop" id="ajDokDrop" tabindex="0" role="button" aria-label="Unggah dokumen persyaratan">
                        <i class="bi bi-cloud-arrow-up-fill"></i>
                        <p><b>Klik atau seret berkas ke sini</b><br>Company Profile, legalitas, atau KTP penanggung jawab.</p>
                    </div>
                    <input type="file" name="dokumen[]" id="ajDokInput" class="d-none" accept=".pdf,.jpg,.jpeg,.png" multiple>

                    <div class="aj-dok-list" id="ajDokList">
                        @foreach ($dokumenTersimpan as $d)
                            <div class="aj-dok-item" data-existing="1">
                                <i class="bi bi-file-earmark-check-fill"></i>
                                <span class="aj-dok-nama">{{ $d['nama'] }}</span>
                                <button type="button" class="aj-dok-remove" title="Hapus dokumen ini" aria-label="Hapus dokumen"><i class="bi bi-x-lg"></i></button>
                                <input type="hidden" name="dokumen_pertahankan[]" value="{{ $d['path'] }}">
                            </div>
                        @endforeach
                    </div>
                    <p class="aj-dok-empty-hint" id="ajDokEmptyHint" @if (count($dokumenTersimpan)) hidden @endif>Belum ada dokumen yang diunggah.</p>
                    @error('dokumen') <div class="aj-field-err">{{ $message }}</div> @enderror
                    @error('dokumen.*') <div class="aj-field-err">{{ $message }}</div> @enderror
                    <p class="aj-hint"><i class="bi bi-info-circle"></i>Format PDF, JPG, atau PNG, maksimal 5 MB per berkas. Cukup diunggah sekali untuk seluruh sewa bulanan di keranjang.</p>
                </div>

            @else
                <div class="aj-row">
                    <div class="aj-field">
                        <label class="aj-sublabel" for="ajTanggalMulai">{{ $sehariSaja ? 'Tanggal Pemakaian' : 'Tanggal Mulai' }}</label>
                        <input type="date" name="tanggal_mulai" class="aj-input @error('tanggal_mulai') is-invalid @enderror" id="ajTanggalMulai" required
                               min="{{ now()->toDateString() }}" value="{{ $nilaiTanggalMulai }}">
                        @error('tanggal_mulai') <div class="aj-field-err">{{ $message }}</div> @enderror
                    </div>
                    <div class="aj-field">
                        @if (! $sehariSaja)
                            <label class="aj-sublabel" for="ajTanggalSelesai">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" class="aj-input @error('tanggal_selesai') is-invalid @enderror" id="ajTanggalSelesai" required
                                   min="{{ now()->toDateString() }}" value="{{ $nilaiTanggalSelesai }}">
                            @error('tanggal_selesai') <div class="aj-field-err">{{ $message }}</div> @enderror
                        @else
                            <span class="aj-sublabel">Jam Layanan</span>
                            <div class="aj-info-slot"><i class="bi bi-clock-history"></i>08.00–16.00 WIB (8 jam)</div>
                        @endif
                    </div>
                </div>
                <p class="aj-hint"><i class="bi bi-info-circle"></i>Sewa harian berlaku pada hari kerja (Senin–Jumat), 08.00–16.00 WIB.</p>
            @endif

            {{-- ══════ PEMBERITAHUAN JADWAL — hasil pengecekan terhadap jadwal tersimpan, tampil
                 DI DALAM form supaya selalu terbaca (tidak tertutup lapisan lain) ══════ --}}
            <div class="aj-alert" id="ajJadwalAlert" role="alert" @if ($errJadwal->isEmpty()) hidden @endif>
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>
                    <b>Jadwal tidak tersedia</b>
                    <ul id="ajJadwalAlertList">
                        @foreach ($errJadwal as $p)<li>{{ $p }}</li>@endforeach
                    </ul>
                </div>
            </div>

            {{-- ══════ RINCIAN BIAYA ══════ --}}
            <div class="aj-rincian">
                <div class="aj-rincian-row">
                    <div class="aj-rincian-lbl">
                        Tarif{{ $isMulti ? " ($jumlahRuangan fasilitas)" : '' }}
                        <span class="aj-rincian-sub">per {{ strtolower($satuan) }}</span>
                    </div>
                    <div class="aj-rincian-val">Rp {{ number_format($totalTarif, 0, ',', '.') }}</div>
                </div>
                <div class="aj-rincian-row">
                    <div class="aj-rincian-lbl">
                        Durasi Sewa
                        <span class="aj-rincian-sub" id="ajDurasiSub">Pilih jadwal untuk menghitung durasi</span>
                    </div>
                    <div class="aj-rincian-val" id="ajDurasiVal">–</div>
                </div>
            </div>

            <div class="aj-total-row">
                <div>
                    <span class="aj-total-lbl">Total Biaya</span>
                    <span class="aj-total-sub" id="ajTotalSub">Tarif × durasi sewa</span>
                </div>
                <div class="aj-total-val" id="ajTotalVal">Rp 0</div>
            </div>

            {{-- ══════ TOMBOL ══════ --}}
            <div class="aj-foot">
                <button type="button" class="aj-btn-cancel" id="fpFormCloseBottom">Batal</button>
                <button type="submit" class="aj-btn-submit" id="ajSubmitBtn">
                    <i class="bi bi-cart-plus-fill"></i>
                    <span>{{ ($editIndex ?? null) !== null && ($editItem ?? null) ? 'Simpan Perubahan' : 'Masukkan ke Keranjang' }}</span>
                </button>
            </div>
        </form>
    </div>
</div>

@if ($errJadwal->isNotEmpty() || $errUmum->isNotEmpty())
    {{-- Pop-up ringkas atas hasil penyimpanan yang gagal. Lapisan SweetAlert berada di atas
         overlay form (z-index diatur di layout), jadi pesannya terbaca jelas; rincian bentrok
         juga tetap tampil di kotak pemberitahuan dalam form setelah pop-up ditutup. --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            Swal.fire({
                icon: 'warning',
                title: @json($errJadwal->isNotEmpty() ? 'Jadwal Tidak Tersedia' : 'Reservasi Belum Dapat Disimpan'),
                html: @json($errJadwal->merge($errUmum)->map(fn ($p) => e($p))->implode('<br><br>')),
                confirmButtonColor: '#176b87',
                confirmButtonText: 'Mengerti',
            });
        });
    </script>
@endif

<style>
    /* ══════════════════════════════════════════════════════════
       MODAL ATUR JADWAL SEWA
       ══════════════════════════════════════════════════════════ */
    .fp-form-overlay {
        position:fixed; inset:0; z-index:1990; background:rgba(8,15,25,.55); backdrop-filter:blur(3px);
        display:none; align-items:flex-start; justify-content:center; padding:2rem 1rem; overflow-y:auto;
        -webkit-overflow-scrolling:touch;
    }
    .fp-form-overlay.show { display:flex; }

    /* overflow SENGAJA visible — panel pemilih jam (dropdown) boleh melewati tepi modal. */
    .aj-modal {
        position:relative; background:#fff; border-radius:1.25rem; width:100%; max-width:600px;
        margin:auto 0; box-shadow:0 30px 70px -12px rgba(8,15,25,.5);
        animation:ajIn .22s cubic-bezier(.2,.8,.3,1) both; border:1px solid var(--line);
    }
    @keyframes ajIn { from { opacity:0; transform:translateY(14px); } to { opacity:1; transform:none; } }
    @media (prefers-reduced-motion: reduce) { .aj-modal { animation:none; } }

    .aj-close {
        position:absolute; top:.9rem; right:.9rem; width:2.25rem; height:2.25rem; border-radius:50%;
        border:1px solid var(--line); background:#fff; color:var(--muted);
        display:grid; place-items:center; cursor:pointer; z-index:5;
        transition:background .15s ease, color .15s ease, border-color .15s ease;
    }
    .aj-close:hover { background:var(--rose-tint); color:var(--rose); border-color:#fecdd3; }

    .aj-head { display:flex; align-items:center; gap:.85rem; padding:1.35rem 1.5rem 1.1rem;
        border-bottom:1px solid var(--line-soft); border-radius:1.25rem 1.25rem 0 0; }
    .aj-head-ic { display:grid; place-items:center; width:2.6rem; height:2.6rem; border-radius:.8rem;
        background:linear-gradient(135deg, var(--primary-dark), var(--primary)); color:#fff; font-size:1.05rem; flex:none; }
    .aj-head-body { min-width:0; flex:1; padding-right:2.5rem; }
    .aj-head-title { font-size:1.05rem; font-weight:800; color:var(--ink); margin:0; letter-spacing:-.01em; }
    .aj-head-sub { font-size:.78rem; color:var(--muted); margin:.15rem 0 0; }

    .aj-fac-bar { padding:.85rem 1.5rem; display:flex; align-items:center; justify-content:space-between;
        gap:.75rem 1rem; background:var(--primary-tint); border-bottom:1px solid var(--primary-softer); flex-wrap:wrap; }
    .aj-fac-l { display:flex; align-items:center; gap:.7rem; min-width:0; flex:1 1 12rem; }
    .aj-fac-ic { display:grid; place-items:center; width:2.2rem; height:2.2rem; border-radius:.6rem;
        background:#fff; color:var(--primary-dark); font-size:.95rem; flex:none; border:1px solid var(--primary-softer); }
    .aj-fac-txt { min-width:0; }
    .aj-fac-nm { font-size:.88rem; font-weight:800; color:var(--ink); line-height:1.25; overflow-wrap:anywhere; }
    .aj-fac-sub { font-size:.72rem; color:var(--muted); font-weight:600; margin-top:.15rem;
        display:flex; flex-wrap:wrap; gap:.15rem .75rem; }
    .aj-fac-sub span { display:inline-flex; align-items:center; gap:.3rem; }
    .aj-fac-sub i { color:var(--primary); }
    .aj-fac-r { flex:none; }
    .aj-fac-r small { display:block; font-size:.62rem; font-weight:800; letter-spacing:.08em;
        text-transform:uppercase; color:var(--muted); }
    .aj-fac-price { font-size:1rem; font-weight:800; color:var(--primary-dark); white-space:nowrap; }
    .aj-fac-satuan { font-size:.72rem; font-weight:600; color:var(--muted); margin-left:.2rem; }

    .aj-block { padding:1rem 1.5rem 0; }
    .aj-label, .aj-sublabel { display:flex; align-items:center; gap:.4rem; margin:0 0 .4rem;
        font-size:.72rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:var(--primary-dark); }

    /* Tiga jenis sewa muat sebaris di ponsel (bukan 2 + 1). */
    .aj-jenis-tabs { display:grid; grid-template-columns:repeat(auto-fit, minmax(6.1rem, 1fr)); gap:.45rem; }
    .aj-jenis-tab { display:flex; align-items:center; justify-content:center; gap:.4rem;
        padding:.65rem .75rem; border-radius:.7rem; border:1px solid var(--line);
        background:#fff; color:var(--muted); font-size:.82rem; font-weight:700; text-decoration:none;
        transition:border-color .15s ease, background .15s ease, color .15s ease; }
    .aj-jenis-tab:hover { border-color:var(--primary); color:var(--primary-dark); background:var(--primary-soft); }
    .aj-jenis-tab.active { background:var(--primary); color:#fff; border-color:var(--primary); font-weight:800; }
    .aj-jenis-tab.is-disabled { background:var(--surface); color:var(--soft); border-style:dashed;
        cursor:not-allowed; text-decoration:line-through; }
    .aj-jenis-tab.is-disabled:hover { border-color:var(--line); background:var(--surface); color:var(--soft); }
    .aj-jenis-tab.active.is-disabled { background:#fff; color:var(--rose); border-color:#fecdd3; border-style:solid; }

    .aj-form { padding:1rem 1.5rem 1.5rem; }
    .aj-field { margin-bottom:.95rem; min-width:0; }
    .aj-row { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
    @media (max-width: 479.98px) { .aj-row { grid-template-columns:1fr; gap:0; } }
    .aj-input { display:block; width:100%; padding:.65rem .85rem; min-height:2.75rem;
        border:1px solid var(--line); border-radius:.7rem; background:#fff;
        font-size:.9rem; font-weight:600; color:var(--ink); font-family:inherit;
        transition:border-color .15s ease, box-shadow .15s ease; outline:none; }
    .aj-input::placeholder { color:var(--soft); font-weight:500; }
    .aj-input:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(23,107,135,.12); }
    .aj-input.is-invalid { border-color:var(--rose); }
    textarea.aj-input { resize:vertical; min-height:4.2rem; line-height:1.45; }
    .aj-input-suffix { position:relative; }
    .aj-input-suffix .aj-input { padding-right:4rem; }
    .aj-suffix { position:absolute; right:.9rem; top:50%; transform:translateY(-50%);
        font-size:.8rem; font-weight:700; color:var(--muted); pointer-events:none; }
    .aj-field-err { display:flex; align-items:flex-start; gap:.3rem; font-size:.76rem; font-weight:600;
        color:#be123c; margin:.35rem 0 0; line-height:1.4; }
    .aj-field-err::before { content:"\F33A"; font-family:"bootstrap-icons"; flex:none; line-height:1.35; }
    .aj-hint { display:flex; align-items:flex-start; gap:.4rem; font-size:.74rem; font-weight:500;
        color:var(--muted); margin:.4rem 0 0; line-height:1.45; }
    .aj-hint i { color:var(--primary); flex:none; margin-top:.08rem; }
    .aj-hint b { color:var(--ink); font-weight:800; }
    .aj-form > .aj-hint { margin:-.45rem 0 .95rem; }
    #ajJenisHint { color:#be123c; margin:.5rem 0 0; }

    .aj-info-slot { display:flex; align-items:center; gap:.45rem; width:100%; min-height:2.75rem;
        padding:.65rem .85rem; border:1px solid var(--line); border-radius:.7rem;
        background:var(--surface); font-size:.85rem; color:var(--ink); font-weight:600; }
    .aj-info-slot i { color:var(--primary); }

    .aj-durasi-chips { display:flex; flex-wrap:wrap; align-items:center; gap:.4rem; margin:-.2rem 0 .95rem; }
    .aj-durasi-lbl { font-size:.74rem; font-weight:700; color:var(--muted); margin-right:.15rem; }
    .aj-durasi-chip { border:1px solid var(--line); background:#fff; color:var(--ink); font-family:inherit;
        font-size:.78rem; font-weight:700; padding:.4rem .8rem; border-radius:2rem; cursor:pointer;
        transition:border-color .15s ease, background .15s ease, color .15s ease; }
    .aj-durasi-chip:hover { border-color:var(--primary); color:var(--primary-dark); }
    .aj-durasi-chip.active { background:var(--primary); border-color:var(--primary); color:#fff; }

    .aj-dok-drop { display:flex; flex-direction:column; align-items:center; text-align:center;
        gap:.35rem; padding:1.1rem 1rem; border:1.5px dashed #cbd5e1; border-radius:.85rem;
        background:var(--surface); cursor:pointer; transition:border-color .15s ease, background .15s ease; }
    .aj-dok-drop:hover, .aj-dok-drop:focus-visible, .aj-dok-drop.dragover { border-color:var(--primary); background:var(--primary-soft); outline:none; }
    .aj-dok-drop i { font-size:1.4rem; color:var(--primary); }
    .aj-dok-drop p { margin:0; font-size:.78rem; color:var(--muted); line-height:1.5; }
    .aj-dok-drop p b { color:var(--ink); font-weight:700; }
    .aj-dok-list { display:flex; flex-direction:column; gap:.4rem; margin-top:.55rem; }
    .aj-dok-list:empty { margin-top:0; }
    .aj-dok-item { display:flex; align-items:center; gap:.55rem; padding:.5rem .7rem;
        border:1px solid var(--line); border-radius:.65rem; background:#fff; }
    .aj-dok-item > i { font-size:1rem; flex:none; color:var(--primary); }
    .aj-dok-item[data-existing] > i { color:var(--emerald); }
    .aj-dok-nama { flex:1; min-width:0; font-size:.8rem; font-weight:600; color:var(--ink);
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .aj-dok-remove { flex:none; width:2rem; height:2rem; display:grid; place-items:center;
        border:0; background:none; color:var(--muted); border-radius:50%; cursor:pointer;
        transition:color .15s ease, background .15s ease; }
    .aj-dok-remove:hover { color:var(--rose); background:var(--rose-tint); }
    .aj-dok-empty-hint { font-size:.75rem; color:var(--soft); margin:.5rem 0 0; text-align:center; }

    .aj-alert { display:flex; align-items:flex-start; gap:.65rem; padding:.8rem .95rem; margin-bottom:.95rem;
        border-radius:.8rem; background:var(--rose-tint); border:1px solid #fecdd3; color:#9f1239;
        font-size:.8rem; line-height:1.5; }
    .aj-alert[hidden] { display:none; }
    .aj-alert > i { font-size:1rem; flex:none; margin-top:.1rem; color:var(--rose); }
    .aj-alert b { display:block; font-weight:800; }
    .aj-alert ul { margin:.15rem 0 0; padding:0; list-style:none; }
    .aj-alert li + li { margin-top:.3rem; }
    .aj-alert span { display:block; }
    .aj-alert.goyang { animation:goyang .3s; }

    .aj-rincian { background:var(--surface); border:1px solid var(--line);
        border-radius:.85rem; padding:.2rem .95rem; margin-bottom:.75rem; }
    .aj-rincian-row { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; padding:.65rem 0; }
    .aj-rincian-row + .aj-rincian-row { border-top:1px dashed var(--line); }
    .aj-rincian-lbl { font-size:.82rem; font-weight:700; color:var(--ink); min-width:0; }
    .aj-rincian-sub { display:block; font-size:.72rem; color:var(--muted); font-weight:500; margin-top:.1rem; }
    .aj-rincian-val { font-size:.88rem; font-weight:800; color:var(--ink); text-align:right; white-space:nowrap; }

    .aj-total-row { display:flex; justify-content:space-between; align-items:center; gap:.5rem 1rem; flex-wrap:wrap;
        padding:.95rem 1.1rem; border-radius:.9rem;
        background:linear-gradient(135deg, var(--primary-darker), var(--primary)); color:#fff; }
    .aj-total-lbl { display:block; font-size:.7rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:rgba(255,255,255,.85); }
    .aj-total-sub { display:block; font-size:.7rem; font-weight:500; color:rgba(255,255,255,.75); margin-top:.1rem; }
    .aj-total-val { font-size:1.4rem; font-weight:800; color:#fff; letter-spacing:-.02em; line-height:1.1; margin-left:auto; }

    .aj-foot { display:flex; align-items:stretch; gap:.6rem; margin-top:1.1rem; }
    .aj-btn-cancel { padding:.75rem 1.25rem; border:1px solid var(--line); background:#fff; font-family:inherit;
        color:var(--muted); font-size:.85rem; font-weight:700; border-radius:.8rem; cursor:pointer;
        transition:background .15s ease, color .15s ease, border-color .15s ease; }
    .aj-btn-cancel:hover { background:var(--surface); color:var(--ink); border-color:#cbd5e1; }
    .aj-btn-submit { flex:1; min-height:2.9rem; padding:.75rem 1.25rem; border:0; font-family:inherit;
        background:linear-gradient(135deg, var(--primary-dark), var(--primary));
        color:#fff; font-size:.9rem; font-weight:800; border-radius:.8rem;
        display:inline-flex; align-items:center; justify-content:center; gap:.45rem; cursor:pointer;
        transition:filter .15s ease, box-shadow .15s ease; box-shadow:0 10px 22px -8px rgba(23,107,135,.5); }
    .aj-btn-submit:hover:not(:disabled) { filter:brightness(1.08); }
    .aj-btn-submit:disabled { background:#cbd5e1; color:#64748b; box-shadow:none; cursor:not-allowed; }

    @media (max-width: 575.98px) {
        .fp-form-overlay { padding:0; align-items:stretch; }
        .aj-modal { border-radius:0; border:0; max-width:none; min-height:100%; margin:0; }
        .aj-head { border-radius:0; padding:1.1rem 1.1rem .95rem; }
        .aj-fac-bar { padding:.8rem 1.1rem; }
        .aj-block { padding:1rem 1.1rem 0; }
        .aj-form { padding:1rem 1.1rem 1.25rem; }
        .aj-input { font-size:1rem; } /* cegah zoom otomatis iOS saat fokus */
        .aj-total-val { font-size:1.2rem; }
        .aj-foot { flex-direction:column-reverse; }
    }
</style>

<script>
(function () {
    const overlay = document.getElementById('fpFormOverlay');
    const form = document.querySelector('.aj-form');
    if (!overlay || !form) return;

    const satuan = form.dataset.satuan; // Jam | Hari | Bulan
    const sehariSaja = form.dataset.sehariSaja === '1';
    const durasiMin = parseInt(form.dataset.durasiMin, 10) || 1;
    const tarifTotal = parseFloat(form.dataset.tarifTotal) || 0;

    const elMulai = document.getElementById('ajTanggalMulai');
    const elSelesai = document.getElementById('ajTanggalSelesai');
    const elJamMulai = form.querySelector('[name="jam_mulai"]');
    const elJamSelesai = form.querySelector('[name="jam_selesai"]');
    const elJumlah = document.getElementById('ajJumlahPengguna');
    const btnSubmit = document.getElementById('ajSubmitBtn');

    const durasiSub = document.getElementById('ajDurasiSub');
    const durasiVal = document.getElementById('ajDurasiVal');
    const totalVal = document.getElementById('ajTotalVal');
    const totalSub = document.getElementById('ajTotalSub');
    const alertBox = document.getElementById('ajJadwalAlert');
    const alertList = document.getElementById('ajJadwalAlertList');

    // ─── Util tanggal: murni aritmetika Y-m-d (tanpa objek Date lokal) supaya tidak
    //     bergeser sehari karena zona waktu. Perbandingan string ISO = perbandingan tanggal. ───
    const pad = (n) => String(n).padStart(2, '0');
    const utc = (s) => { const [y, m, d] = s.split('-').map(Number); return Date.UTC(y, m - 1, d); };
    const selisihHari = (a, b) => Math.round((utc(b) - utc(a)) / 86400000);
    const hariKe = (s) => new Date(utc(s)).getUTCDay(); // 0 = Minggu, 6 = Sabtu
    const akhirPekan = (s) => [0, 6].includes(hariKe(s));
    const tambahBulan = (s, n) => {
        const [y, m, d] = s.split('-').map(Number);
        const total = (m - 1) + n;
        const ny = y + Math.floor(total / 12);
        const nm = ((total % 12) + 12) % 12;
        const maks = new Date(Date.UTC(ny, nm + 1, 0)).getUTCDate();
        return ny + '-' + pad(nm + 1) + '-' + pad(Math.min(d, maks));
    };
    // Sama persis dengan CartService::hitungBulan() di server.
    const hitungBulan = (a, b) => {
        if (!a || !b || b <= a) return { penuh: 0, sisa: 0, ditagih: 0 };
        let penuh = 0;
        while (penuh < 1200 && tambahBulan(a, penuh + 1) <= b) penuh++;
        const sisa = selisihHari(tambahBulan(a, penuh), b);
        return { penuh, sisa, ditagih: penuh + (sisa > 0 ? 1 : 0) };
    };
    const tglPanjang = (s) => new Date(utc(s)).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' });
    const rupiah = (n) => 'Rp ' + Math.round(n || 0).toLocaleString('id-ID');

    // ─── Pesan kesalahan di bawah kolom (menggantikan pesan server kolom yang sama) ───
    const setErr = (input, pesan) => {
        const field = input?.closest('.aj-field');
        if (!field) return;
        field.querySelectorAll('.aj-field-err, .catatan-salah').forEach((el) => el.remove());
        input.classList.toggle('is-invalid', !!pesan);
        if (!pesan) return;
        const div = document.createElement('div');
        div.className = 'aj-field-err';
        div.dataset.klien = '1';
        div.textContent = pesan;
        (input.closest('.aj-input-suffix, [data-jampicker]') || input).insertAdjacentElement('afterend', div);
    };

    // ─── Buka/tutup modal ───
    const buka = () => { overlay.classList.add('show'); document.body.style.overflow = 'hidden'; };
    const tutup = () => { overlay.classList.remove('show'); document.body.style.overflow = ''; };
    window.ajBukaModal = buka;
    document.getElementById('fpFormClose')?.addEventListener('click', tutup);
    document.getElementById('fpFormCloseBottom')?.addEventListener('click', tutup);
    overlay.addEventListener('mousedown', (e) => { overlay.dataset.turun = e.target === overlay ? '1' : ''; });
    overlay.addEventListener('click', (e) => { if (e.target === overlay && overlay.dataset.turun === '1') tutup(); });
    document.addEventListener('keydown', (e) => {
        // Escape menutup lapisan teratas dulu: pop-up, lalu panel pemilih jam, baru form ini.
        if (e.key === 'Escape' && overlay.classList.contains('show') && !document.querySelector('.swal2-container, .jampicker.buka')) tutup();
    });

    // ─── Rincian durasi & total ───
    const setTotal = (durasi, teksDurasi, teksSub) => {
        totalVal.textContent = rupiah(tarifTotal * durasi);
        durasiVal.textContent = durasi > 0 ? teksDurasi : '–';
        durasiSub.textContent = durasi > 0 ? teksSub : 'Pilih jadwal untuk menghitung durasi';
        totalSub.textContent = durasi > 0 ? rupiah(tarifTotal) + ' × ' + teksDurasi : 'Tarif × durasi sewa';
    };

    const hitung = () => {
        if (satuan === 'Jam') {
            const m = parseInt((elJamMulai?.value || '').split(':')[0], 10);
            const s = parseInt((elJamSelesai?.value || '').split(':')[0], 10);
            const durasi = (isFinite(m) && isFinite(s) && s > m) ? s - m : 0;
            setTotal(durasi, durasi + ' jam', durasi ? elJamMulai.value.replace(':', '.') + '–' + elJamSelesai.value.replace(':', '.') + ' WIB' : '');
        } else if (satuan === 'Hari') {
            const a = elMulai?.value;
            const b = sehariSaja ? a : elSelesai?.value;
            const durasi = (a && b && b >= a) ? selisihHari(a, b) + 1 : 0;
            setTotal(durasi, durasi + ' hari', durasi ? (a === b ? tglPanjang(a) : tglPanjang(a) + ' – ' + tglPanjang(b)) : '');
        } else {
            const a = elMulai?.value, b = elSelesai?.value;
            const bln = hitungBulan(a, b);
            const sub = bln.ditagih
                ? tglPanjang(a) + ' – ' + tglPanjang(b) + (bln.sisa > 0 ? ' (' + bln.penuh + ' bulan ' + bln.sisa + ' hari, dihitung ' + bln.ditagih + ' bulan)' : '')
                : '';
            setTotal(bln.ditagih, bln.ditagih + ' bulan', sub);
            form.querySelectorAll('.aj-durasi-chip').forEach((chip) => {
                chip.classList.toggle('active', !!a && !!b && tambahBulan(a, parseInt(chip.dataset.bulan, 10)) === b);
            });
        }
    };

    // ─── Validasi tanggal di sisi pemesan (server tetap memvalidasi ulang) ───
    const pesanAkhirPekan = 'Gedung tidak beroperasi pada hari Sabtu dan Minggu. Silakan pilih hari kerja (Senin–Jumat).';
    const validasiTanggal = () => {
        let sah = true;
        if (elMulai) {
            let pesan = '';
            if (elMulai.value && akhirPekan(elMulai.value)) pesan = pesanAkhirPekan;
            setErr(elMulai, pesan);
            if (pesan) sah = false;
        }
        if (elSelesai && elMulai) {
            let pesan = '';
            const a = elMulai.value, b = elSelesai.value;
            if (a && b) {
                if (satuan === 'Bulan') {
                    const palingCepat = tambahBulan(a, durasiMin);
                    if (b < palingCepat) pesan = 'Masa sewa bulanan minimal ' + durasiMin + ' bulan. Tanggal berakhir paling cepat ' + tglPanjang(palingCepat) + '.';
                } else if (b < a) {
                    pesan = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
                } else {
                    for (let t = a, i = 0; t <= b && i < 400; i++) {
                        if (akhirPekan(t)) { pesan = 'Rentang tanggal mencakup hari Sabtu/Minggu. Sewa harian hanya berlaku pada hari kerja (Senin–Jumat).'; break; }
                        const n = new Date(utc(t) + 86400000);
                        t = n.getUTCFullYear() + '-' + pad(n.getUTCMonth() + 1) + '-' + pad(n.getUTCDate());
                    }
                }
            }
            setErr(elSelesai, pesan);
            if (pesan) sah = false;
        }
        return sah;
    };

    // ─── Jumlah pengguna vs kapasitas ───
    const validasiJumlah = () => {
        if (!elJumlah || elJumlah.value === '') { return true; }
        const n = Number(elJumlah.value);
        let pesan = '';
        if (!Number.isInteger(n) || n < 1) pesan = elJumlah.dataset.pesanMin;
        else if (n > Number(elJumlah.max)) pesan = elJumlah.dataset.pesanMaks;
        setErr(elJumlah, pesan);
        return !pesan;
    };
    elJumlah?.addEventListener('input', validasiJumlah);

    // ─── Pengecekan jadwal ke server (jadwal tersimpan di database + keranjang) ───
    const labelSubmit = btnSubmit.innerHTML;
    let bentrok = !alertBox.hidden;
    let cekCtrl = null, cekTimer = null;
    const tabs = document.querySelectorAll('#ajJenisTabs .aj-jenis-tab');
    const jenisHint = document.getElementById('ajJenisHint');

    // Pesan bentrok kiriman server (setelah simpan gagal) dipertahankan sampai pemesan
    // mengubah jadwal — pengecekan awal dengan jadwal yang belum lengkap tidak menghapusnya.
    let pesanServer = !alertBox.hidden;
    const tampilkanHasil = (data) => {
        const pesan = Array.isArray(data.pesan) ? data.pesan : [];
        if (pesanServer && !data.lengkap && data.tersedia !== false) { bentrok = false; btnSubmit.disabled = false; return; }
        bentrok = data.tersedia === false;
        alertList.innerHTML = '';
        pesan.forEach((p) => { const li = document.createElement('li'); li.textContent = p; alertList.appendChild(li); });
        alertBox.hidden = !bentrok;
        btnSubmit.disabled = bentrok;

        if (data.kondisi && tabs.length) {
            let alasan = '';
            tabs.forEach((tab) => {
                const bisa = data.kondisi.bisa?.[tab.dataset.satuan] !== false;
                tab.classList.toggle('is-disabled', !bisa);
                bisa ? tab.removeAttribute('aria-disabled') : tab.setAttribute('aria-disabled', 'true');
                if (!bisa) alasan = 'Fasilitas ' + data.kondisi.label.toLowerCase() + ' pada ' + tglPanjang(data.kondisi.tanggal) + ', sehingga jenis sewa yang dicoret tidak dapat dipesan pada tanggal tersebut.';
            });
            jenisHint.textContent = alasan;
            jenisHint.hidden = !alasan;
        }
    };

    const cekJadwal = () => {
        clearTimeout(cekTimer);
        cekTimer = setTimeout(() => {
            const a = elMulai?.value;
            if (!a) { tampilkanHasil({ tersedia: true, pesan: [], lengkap: false, kondisi: { bisa: {} } }); return; }


            const p = new URLSearchParams({ satuan, tanggal_mulai: a });
            let lengkap = false;
            if (satuan === 'Jam') {
                if (elJamMulai.value && elJamSelesai.value && elJamSelesai.value > elJamMulai.value) {
                    p.set('jam_mulai', elJamMulai.value); p.set('jam_selesai', elJamSelesai.value); lengkap = true;
                }
            } else {
                const b = sehariSaja ? a : elSelesai?.value;
                if (b && b >= a) { p.set('tanggal_selesai', b); lengkap = true; }
            }
            if (form.dataset.antrian) p.set('antrian', form.dataset.antrian);
            if (form.dataset.editIndex !== '') p.set('edit_index', form.dataset.editIndex);

            // Tab jenis sewa membawa tanggal yang sedang dipilih supaya tetap terisi setelah berpindah.
            tabs.forEach((tab) => { const u = new URL(tab.href); u.searchParams.set('tanggal_mulai', a); tab.href = u.toString(); });

            cekCtrl?.abort();
            cekCtrl = new AbortController();
            fetch(form.dataset.cekUrl + '?' + p.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, signal: cekCtrl.signal })
                .then((r) => r.ok ? r.json() : Promise.reject(r.status))
                .then((data) => tampilkanHasil({ ...data, lengkap: lengkap && validasiTanggal() }))
                .catch((err) => { if (err?.name !== 'AbortError') { bentrok = false; btnSubmit.disabled = false; } });
        }, 250);
    };

    const perbarui = () => { pesanServer = false; hitung(); validasiTanggal(); cekJadwal(); };

    // Jam mulai/selesai disimpan di input tersembunyi (tidak ikut validasi bawaan browser).
    const validasiJam = () => {
        if (satuan !== 'Jam') return true;
        let sah = true;
        [[elJamMulai, 'Jam mulai wajib dipilih.'], [elJamSelesai, 'Jam selesai wajib dipilih.']].forEach(([el, pesan]) => {
            setErr(el, el.value ? '' : pesan);
            if (!el.value) sah = false;
        });
        if (sah && elJamSelesai.value <= elJamMulai.value) { setErr(elJamSelesai, 'Jam selesai harus setelah jam mulai.'); sah = false; }
        return sah;
    };

    // Tab jenis sewa yang tidak dapat dipesan: tidak berpindah halaman.
    tabs.forEach((tab) => tab.addEventListener('click', (e) => {
        if (tab.classList.contains('is-disabled')) { e.preventDefault(); jenisHint.hidden = false; jenisHint.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); }
    }));

    // ─── Bulanan: tanggal berakhir otomatis = tanggal mulai + lama sewa (minimal durasi minimum),
    //     tetap dapat diubah manual atau lewat chip lama sewa. ───
    if (satuan === 'Bulan' && elMulai && elSelesai) {
        let lamaSewa = Math.max(durasiMin, hitungBulan(elMulai.value, elSelesai.value).ditagih);
        elMulai.addEventListener('change', () => {
            if (!elMulai.value) return;
            elSelesai.value = tambahBulan(elMulai.value, lamaSewa);
        });
        elSelesai.addEventListener('change', () => {
            const bln = hitungBulan(elMulai.value, elSelesai.value);
            if (bln.ditagih >= durasiMin) lamaSewa = bln.ditagih;
        });
        form.querySelectorAll('.aj-durasi-chip').forEach((chip) => chip.addEventListener('click', () => {
            lamaSewa = parseInt(chip.dataset.bulan, 10);
            if (!elMulai.value) { setErr(elMulai, 'Pilih tanggal mulai terlebih dahulu.'); elMulai.focus(); return; }
            elSelesai.value = tambahBulan(elMulai.value, lamaSewa);
            perbarui();
        }));
        if (elMulai.value && !elSelesai.value) elSelesai.value = tambahBulan(elMulai.value, lamaSewa);
    }
    if (satuan === 'Hari' && elMulai && elSelesai) {
        elMulai.addEventListener('change', () => {
            if (elMulai.value) elSelesai.min = elMulai.value;
            if (elMulai.value && (!elSelesai.value || elSelesai.value < elMulai.value)) elSelesai.value = elMulai.value;
        });
    }

    [elMulai, elSelesai].forEach((el) => el?.addEventListener('change', perbarui));
    [elJamMulai, elJamSelesai].forEach((el) => el?.addEventListener('change', () => { if (el.value) setErr(el, ''); perbarui(); }));


    // ─── Submit: validasi klien → kunci tombol (cegah kirim ganda) ───
    form.addEventListener('submit', (e) => {
        if (form.dataset.submitting === '1') { e.preventDefault(); return; }
        const sah = validasiJumlah() & validasiTanggal() & validasiJam();
        if (!sah || bentrok) {
            e.preventDefault();
            const target = form.querySelector('.aj-field-err[data-klien]') || alertBox;
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (bentrok) { alertBox.classList.remove('goyang'); void alertBox.offsetWidth; alertBox.classList.add('goyang'); }
            return;
        }
        form.dataset.submitting = '1';
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span>Menyimpan…</span>';
    });
    // Kembali lewat tombol Back browser (bfcache): buka lagi kunci tombol.
    window.addEventListener('pageshow', (e) => {
        if (!e.persisted) return;
        delete form.dataset.submitting;
        btnSubmit.disabled = bentrok;
        btnSubmit.innerHTML = labelSubmit;

    });

    // ─── Unggah dokumen (bulanan): berkas menumpuk (tidak saling menggantikan), bisa seret-lepas,
    //     tiap berkas bisa dihapus satu per satu. ───
    (function () {
        const drop = document.getElementById('ajDokDrop');
        const input = document.getElementById('ajDokInput');
        const list = document.getElementById('ajDokList');
        const emptyHint = document.getElementById('ajDokEmptyHint');
        if (!drop || !input || !list) return;

        let files = [];
        const IZIN = ['pdf', 'jpg', 'jpeg', 'png'];
        const updateEmptyHint = () => { emptyHint.hidden = list.children.length > 0; };
        const syncInput = () => { const dt = new DataTransfer(); files.forEach((f) => dt.items.add(f)); input.files = dt.files; };

        const buatBaris = (file) => {
            const row = document.createElement('div');
            row.className = 'aj-dok-item';
            row.innerHTML = '<i class="bi bi-file-earmark-arrow-up-fill"></i><span class="aj-dok-nama"></span>'
                + '<button type="button" class="aj-dok-remove" title="Batalkan berkas ini" aria-label="Batalkan berkas"><i class="bi bi-x-lg"></i></button>';
            row.querySelector('.aj-dok-nama').textContent = file.name;
            row.querySelector('.aj-dok-remove').addEventListener('click', () => {
                files = files.filter((f) => f !== file);
                syncInput(); row.remove(); updateEmptyHint();
            });
            return row;
        };

        const tambah = (fileList) => {
            const ditolak = [];
            [...fileList].forEach((file) => {
                const ext = file.name.split('.').pop().toLowerCase();
                if (!IZIN.includes(ext)) { ditolak.push(file.name + ' (format tidak didukung)'); return; }
                if (file.size > 5 * 1024 * 1024) { ditolak.push(file.name + ' (melebihi 5 MB)'); return; }
                files.push(file);
                list.appendChild(buatBaris(file));
            });
            syncInput(); updateEmptyHint();
            setErr(input, ditolak.length ? 'Berkas tidak ditambahkan: ' + ditolak.join(', ') + '.' : '');
        };

        drop.addEventListener('click', () => input.click());
        drop.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); } });
        input.addEventListener('change', () => { const baru = [...input.files]; tambah(baru); });
        ['dragenter', 'dragover'].forEach((ev) => drop.addEventListener(ev, (e) => { e.preventDefault(); drop.classList.add('dragover'); }));
        ['dragleave', 'drop'].forEach((ev) => drop.addEventListener(ev, (e) => { e.preventDefault(); drop.classList.remove('dragover'); }));
        drop.addEventListener('drop', (e) => { if (e.dataTransfer?.files?.length) tambah(e.dataTransfer.files); });

        list.querySelectorAll('.aj-dok-item[data-existing="1"] .aj-dok-remove').forEach((btn) => {
            btn.addEventListener('click', () => { btn.closest('.aj-dok-item').remove(); updateEmptyHint(); });
        });
    })();

    // ─── Keadaan awal ───
    hitung();
    if (elMulai?.value) { validasiTanggal(); cekJadwal(); }
    // Jadwal yang baru dipesan pemesan lain langsung diperiksa ulang (lihat partials/pantau-status).
    document.addEventListener('realtime:berubah', () => { if (elMulai?.value) cekJadwal(); });
    btnSubmit.disabled = false; // pesan bentrok dari server tetap tampil; pemesan boleh langsung mencoba jadwal lain
})();
</script>
