@extends('admin.layouts.app')
@section('pantau_status', '1')
@section('title', 'Detail Reservasi')

@php
    $r = $reservasi;
    $adaDisetujui = $items->contains(fn ($it) => in_array($it->status_reservasi->value, ['Disetujui', 'Selesai'], true));
    $totalSemua = $items->sum('total_biaya');
    // Ringkasan status seluruh ruangan, mis. "2 Disetujui · 1 Menunggu".
    $ringkasStatus = $items->countBy(fn ($it) => $it->status_reservasi->value);
    $warnaRiwayat = ['Menunggu' => '#f59e0b', 'Disetujui' => '#059669', 'Ditolak' => '#e11d48', 'Dibatalkan' => '#94a3b8', 'Selesai' => '#3b82f6', 'Kadaluwarsa' => '#8b5cf6'];
@endphp

@section('actions')
    <a href="{{ route('admin.reservasi.index') }}" class="btn btn-brand-outline btn-sm d-none d-md-inline-flex align-items-center"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
@endsection

@section('content')
<style>
    /* ══════════ RINGKASAN (atas) ══════════ */
    .rd-hero { padding:1.35rem 1.5rem; }
    .rd-hero-top { display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-start; gap:1rem; }
    .rd-eyebrow { font-size:.68rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase; color:var(--soft); }
    .rd-kode { font-size:1.6rem; font-weight:800; color:var(--primary-dark); letter-spacing:.02em; line-height:1.2; margin:.15rem 0 .55rem; }
    .rd-status-row { display:flex; flex-wrap:wrap; gap:.4rem; }
    .rd-stats { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); margin-top:1.25rem; border:1px solid var(--line); border-radius:1rem; overflow:hidden; }
    .rd-stat { display:flex; align-items:center; gap:.75rem; padding:.95rem 1.1rem; min-width:0; }
    .rd-stat + .rd-stat { border-left:1px solid var(--line); }
    .rd-stat .ic { display:grid; place-items:center; width:2.4rem; height:2.4rem; flex:none; border-radius:.75rem; background:var(--primary-soft); color:var(--primary-dark); font-size:1rem; }
    .rd-stat small { display:block; font-size:.68rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--soft); }
    .rd-stat b { display:block; font-size:.92rem; color:var(--ink); overflow-wrap:anywhere; }
    .rd-stat.total b { color:var(--primary-dark); font-size:1rem; }
    @media (max-width: 1199.98px) {
        .rd-stats { grid-template-columns:repeat(2, minmax(0, 1fr)); }
        .rd-stat:nth-child(3) { border-left:0; }
        .rd-stat:nth-child(n+3) { border-top:1px solid var(--line); }
    }
    @media (max-width: 575.98px) {
        .rd-hero { padding:1.1rem; }
        .rd-kode { font-size:1.3rem; }
        .rd-hero-top form, .rd-hero-top form .btn { width:100%; }
        .rd-stats { grid-template-columns:minmax(0, 1fr); }
        .rd-stat + .rd-stat { border-left:0; border-top:1px solid var(--line); }
    }

    /* ══════════ KARTU & JUDUL BAGIAN ══════════ */
    .rd-card { padding:0; }
    .rd-card-head { display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:1rem 1.3rem; border-bottom:1px solid var(--line-soft); }
    .rd-card-title { display:flex; align-items:center; gap:.6rem; font-weight:800; font-size:.95rem; color:var(--ink); margin:0; }
    .rd-card-title i { display:grid; place-items:center; width:2rem; height:2rem; border-radius:.6rem; background:var(--primary-soft); color:var(--primary-dark); font-size:.9rem; }
    .rd-count { flex:none; white-space:nowrap; font-size:.72rem; font-weight:700; color:var(--muted); background:var(--surface); border:1px solid var(--line); padding:.2rem .6rem; border-radius:2rem; }
    .rd-card-body { padding:1.2rem 1.3rem; }

    /* ══════════ RUANGAN ══════════ */
    .rd-room { border:1px solid var(--line); border-radius:1rem; overflow:hidden; }
    .rd-room + .rd-room { margin-top:1rem; }
    .rd-room-head { display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:.6rem; padding:.95rem 1.15rem; background:var(--primary-tint); border-bottom:1px solid var(--line); }
    .rd-room-nama { font-weight:800; color:var(--ink); }
    .rd-room-sub { font-size:.78rem; color:var(--muted); }
    .rd-room-body { padding:1.1rem 1.15rem; display:flex; flex-direction:column; gap:1.1rem; }
    .rd-detail { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:.6rem; }
    .rd-detail > div { padding:.7rem .85rem; border-radius:.75rem; background:var(--surface); min-width:0; }
    .rd-detail .full { grid-column:1 / -1; }
    .rd-lbl { display:block; font-size:.66rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:var(--soft); margin-bottom:.2rem; }
    .rd-val { font-size:.88rem; font-weight:600; color:var(--ink); overflow-wrap:anywhere; }
    .rd-val .muted { color:var(--muted); font-weight:500; font-size:.8rem; }
    .rd-val.harga { color:var(--primary-dark); font-weight:800; }
    @media (max-width: 575.98px) { .rd-detail { grid-template-columns:minmax(0, 1fr); } }

    .rd-sub-title { display:flex; align-items:center; justify-content:space-between; gap:.5rem; font-size:.78rem; font-weight:800; color:var(--ink); margin-bottom:.55rem; }
    .rd-pills { display:flex; flex-wrap:wrap; gap:.35rem; }
    .rd-pills span { font-size:.74rem; font-weight:600; color:#334155; background:#fff; border:1px solid var(--line); padding:.25rem .6rem; border-radius:2rem; }

    .rd-ck-progress { font-size:.72rem; font-weight:700; padding:.15rem .55rem; border-radius:2rem; }
    .rd-ck-progress.ok { background:var(--emerald-soft); color:#047857; }
    .rd-ck-progress.belum { background:var(--rose-tint); color:#be123c; }
    .checklist-kelayakan { display:flex; flex-direction:column; border:1px solid var(--line); border-radius:.8rem; overflow:hidden; }
    .checklist-kelayakan .ck-item { display:flex; align-items:flex-start; gap:.65rem; padding:.65rem .85rem; }
    .checklist-kelayakan .ck-item + .ck-item { border-top:1px solid var(--line-soft); }
    .checklist-kelayakan .ck-item > i { flex:none; margin-top:.1rem; font-size:.95rem; }
    .checklist-kelayakan .ck-label { font-size:.84rem; font-weight:700; color:var(--ink); line-height:1.3; }
    .checklist-kelayakan .ck-note { font-size:.76rem; color:var(--muted); line-height:1.35; }

    /* ══════════ DOKUMEN ══════════ */
    .rd-doc { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.75rem; padding:.85rem 0; }
    .rd-doc + .rd-doc { border-top:1px solid var(--line-soft); }
    .rd-doc-info { display:flex; align-items:center; gap:.75rem; min-width:0; flex:1 1 16rem; }
    .rd-doc-ic { display:grid; place-items:center; width:2.5rem; height:2.5rem; flex:none; border-radius:.7rem; background:var(--rose-tint); color:var(--rose); font-size:1.1rem; }
    .rd-doc-nama { font-weight:700; font-size:.86rem; color:var(--ink); }
    .rd-doc-file { font-size:.76rem; color:var(--primary); text-decoration:none; overflow-wrap:anywhere; }
    .rd-doc-file:hover { text-decoration:underline; }
    .rd-doc-badge { font-size:.68rem; font-weight:800; padding:.2rem .55rem; border-radius:2rem; margin-left:.35rem; }
    .rd-doc-badge.Menunggu { background:var(--amber-tint); color:#a16207; }
    .rd-doc-badge.Valid { background:var(--emerald-soft); color:#047857; }
    .rd-doc-badge.Tidak { background:var(--rose-tint); color:#be123c; }
    .min-w-0 { min-width:0; }
    .rd-empty { display:flex; align-items:center; gap:.5rem; color:var(--muted); font-size:.85rem; margin:0; }

    /* ══════════ KOLOM KANAN ══════════ */
    @media (min-width: 992px) { .rd-side { position:sticky; top:6.5rem; } }
    .rd-aksi-note { font-size:.8rem; color:var(--muted); line-height:1.55; padding:.7rem .85rem; border-radius:.75rem; background:var(--surface); margin-bottom:.9rem; }
    .rd-aksi .btn { font-weight:700; padding:.65rem 1rem; border-radius:.8rem; }
    .rd-aksi-warn { display:flex; gap:.45rem; font-size:.78rem; color:#be123c; margin:.1rem 0 .75rem; }

    .rd-person { display:flex; align-items:center; gap:.85rem; padding-bottom:1rem; margin-bottom:.35rem; border-bottom:1px solid var(--line-soft); }
    .rd-avatar { display:grid; place-items:center; width:2.9rem; height:2.9rem; flex:none; border-radius:50%; background:var(--primary); color:#fff; font-weight:800; font-size:1.1rem; }
    .rd-person-nama { font-weight:800; color:var(--ink); line-height:1.25; }
    .rd-person-sub { font-size:.78rem; color:var(--muted); overflow-wrap:anywhere; }
    .rd-kontak { list-style:none; margin:0; padding:0; }
    .rd-kontak li { display:flex; gap:.7rem; padding:.6rem 0; font-size:.85rem; }
    .rd-kontak li + li { border-top:1px dashed var(--line-soft); }
    .rd-kontak i { color:var(--soft); width:1rem; flex:none; margin-top:.1rem; }
    .rd-kontak .k { display:block; font-size:.68rem; font-weight:700; color:var(--soft); text-transform:uppercase; letter-spacing:.06em; }
    .rd-kontak .v { color:var(--ink); font-weight:600; overflow-wrap:anywhere; }

    .timeline { position:relative; padding-left:1.5rem; }
    .timeline::before { content:''; position:absolute; left:.4rem; top:.45rem; bottom:.45rem; width:2px; background:var(--line); border-radius:2px; }
    .timeline .titem { position:relative; padding:0 0 1.1rem; }
    .timeline .titem:last-child { padding-bottom:0; }
    .timeline .titem::before { content:''; position:absolute; left:-1.5rem; top:.25rem; width:.85rem; height:.85rem; border-radius:50%; background:#fff; border:3px solid var(--dot, var(--primary)); }
    .timeline .t-status { font-size:.84rem; font-weight:700; color:var(--ink); }
    .timeline .t-meta { font-size:.75rem; color:var(--muted); margin-top:.1rem; }
    .timeline .t-ket { font-size:.8rem; color:#334155; margin-top:.4rem; padding:.5rem .7rem; border-radius:.6rem; background:var(--surface); border-left:3px solid var(--dot, var(--primary)); }
</style>

    {{-- ══════════ RINGKASAN RESERVASI ══════════ --}}
    <div class="xcard rd-hero mb-3" data-reveal>
        <div class="rd-hero-top">
            <div>
                <span class="rd-eyebrow">Kode Reservasi</span>
                <div class="rd-kode">{{ $r->kode_transaksi }}</div>
                <div class="rd-status-row">
                    @foreach ($ringkasStatus as $status => $jumlah)
                        <span class="chip {{ strtolower($status) }}">{{ $items->count() > 1 ? $jumlah.' ' : '' }}{{ $status }}</span>
                    @endforeach
                </div>
            </div>
            @if ($adaDisetujui)
                {{-- Idempotent: klik pertama menerbitkan, berikutnya mengunduh PDF yang sama (1 faktur utk semua ruangan). --}}
                <form method="POST" action="{{ route('admin.reservasi.faktur.cetak', $r->kode_reservasi) }}"
                      data-confirm="Faktur PDF untuk reservasi {{ $r->kode_transaksi }} akan diunduh."
                      data-confirm-title="Unduh faktur ini?" data-icon="warning" data-confirm-text="Ya, unduh">@csrf
                    <button class="btn btn-brand"><i class="bi bi-receipt me-1"></i>Unduh Faktur</button>
                </form>
            @endif
        </div>

        <div class="rd-stats">
            <div class="rd-stat"><span class="ic"><i class="bi bi-person"></i></span><div><small>Pemesan</small><b>{{ $r->pemesan->nama_lengkap }}</b></div></div>
            <div class="rd-stat"><span class="ic"><i class="bi bi-calendar-event"></i></span><div><small>Diajukan</small><b>{{ $r->created_at->translatedFormat('d M Y, H:i') }}</b></div></div>
            <div class="rd-stat"><span class="ic"><i class="bi bi-door-open"></i></span><div><small>Jumlah Ruangan</small><b>{{ $items->count() }}</b></div></div>
            <div class="rd-stat total"><span class="ic"><i class="bi bi-cash-stack"></i></span><div><small>Total Biaya</small><b>Rp {{ number_format($totalSemua, 0, ',', '.') }}</b></div></div>
        </div>
    </div>

    <div class="row g-3" data-reveal>
        {{-- ══════════ KIRI ══════════ --}}
        <div class="col-lg-8">
            {{-- Ruangan yang direservasi --}}
            <div class="xcard rd-card mb-3">
                <div class="rd-card-head">
                    <h2 class="rd-card-title"><i class="bi bi-door-open"></i>Ruangan Dipesan</h2>
                    <span class="rd-count">{{ $items->count() }} ruangan</span>
                </div>
                <div class="rd-card-body">
                    @foreach ($items as $it)
                        @php
                            $fas = $it->tarifSewa->fasilitas;
                            $satuan = $it->tarifSewa->jenisSewa->satuan->value;
                            $cl = $checklists[$it->id_reservasi];
                            $jumlahLolos = collect($cl)->where('passed', true)->count();
                        @endphp
                        <div class="rd-room">
                            <div class="rd-room-head">
                                <div>
                                    <div class="rd-room-nama">{{ $fas->nama_fasilitas }}</div>
                                    <div class="rd-room-sub">{{ $fas->kategori_fasilitas }} · Lantai {{ $fas->lantai->nomor_lantai }} · {{ $fas->kode_fasilitas }}</div>
                                </div>
                                <span class="chip {{ strtolower($it->status_reservasi->value) }}">{{ $it->status_reservasi->value }}</span>
                            </div>

                            <div class="rd-room-body">
                                <div class="rd-detail">
                                    <div>
                                        <span class="rd-lbl">Periode</span>
                                        <div class="rd-val">
                                            @if ($it->jam_mulai)
                                                {{ $it->tanggal_mulai->translatedFormat('d M Y') }}<br><span class="muted">{{ \Illuminate\Support\Str::substr($it->jam_mulai,0,5) }}–{{ \Illuminate\Support\Str::substr($it->jam_selesai,0,5) }} WIB</span>
                                            @else
                                                {{ $it->tanggal_mulai->translatedFormat('d M Y') }} – {{ $it->tanggal_selesai->translatedFormat('d M Y') }}
                                            @endif
                                        </div>
                                    </div>
                                    <div>
                                        <span class="rd-lbl">Durasi</span>
                                        <div class="rd-val">{{ $it->durasi }} {{ strtolower($satuan) }}@if($satuan === 'Hari') <span class="muted">(1 hari = 8 jam)</span>@endif</div>
                                    </div>
                                    <div>
                                        <span class="rd-lbl">Tarif</span>
                                        <div class="rd-val">Rp {{ number_format($it->harga_satuan, 0, ',', '.') }} <span class="muted">/ {{ strtolower($satuan) }}</span></div>
                                    </div>
                                    <div>
                                        <span class="rd-lbl">Total</span>
                                        <div class="rd-val harga">Rp {{ number_format($it->total_biaya, 0, ',', '.') }}</div>
                                    </div>
                                    <div>
                                        <span class="rd-lbl">Jumlah Pengguna</span>
                                        <div class="rd-val">{{ $it->jumlah_pengguna }} orang</div>
                                    </div>
                                    <div>
                                        <span class="rd-lbl">Keperluan</span>
                                        <div class="rd-val">{{ $it->keperluan }}</div>
                                    </div>
                                </div>

                                <div>
                                    <div class="rd-sub-title">Fasilitas Termasuk</div>
                                    <div class="rd-pills">
                                        @foreach (app(\App\Services\FasilitasBawaanService::class)->untuk($fas, $satuan) as $bawaan)
                                            <span>{{ $bawaan }}</span>
                                        @endforeach
                                    </div>
                                </div>

                                <div>
                                    <div class="rd-sub-title">
                                        Checklist Kelayakan
                                        <span class="rd-ck-progress {{ $jumlahLolos === count($cl) ? 'ok' : 'belum' }}">{{ $jumlahLolos }}/{{ count($cl) }} terpenuhi</span>
                                    </div>
                                    <div class="checklist-kelayakan">
                                        @foreach ($cl as $c)
                                            <div class="ck-item">
                                                <i class="bi bi-{{ $c['passed'] ? 'check-circle-fill text-success' : 'x-circle-fill text-danger' }}"></i>
                                                <div>
                                                    <div class="ck-label">{{ $c['label'] }}</div>
                                                    <div class="ck-note">{{ $c['note'] }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Dokumen persyaratan pemesanan — satu file yang sama disalin ke tiap ruangan
                 Bulan dalam transaksi ini (lihat ReservasiController::simpanDokumen), jadi
                 di sini dikelompokkan per nama file supaya tampil satu kartu saja per dokumen. --}}
            @php $semuaDokumen = $items->flatMap(fn ($it) => $it->dokumenPersyaratan)->groupBy('nama_file'); @endphp
            <div class="xcard rd-card">
                <div class="rd-card-head">
                    <h2 class="rd-card-title"><i class="bi bi-paperclip"></i>Dokumen Persyaratan</h2>
                    <span class="rd-count">{{ $semuaDokumen->count() }} file</span>
                </div>
                <div class="rd-card-body py-2">
                    @forelse ($semuaDokumen as $grup)
                        @php $dok = $grup->first(); $stDok = $dok->status_verifikasi->value; @endphp
                        <div class="rd-doc">
                            <div class="rd-doc-info">
                                <span class="rd-doc-ic"><i class="bi bi-file-earmark-text"></i></span>
                                <div class="min-w-0">
                                    <div class="rd-doc-nama">{{ $dok->jenis_dokumen }}<span class="rd-doc-badge {{ strtok($stDok, ' ') }}">{{ $stDok }}</span></div>
                                    <a href="{{ route('admin.reservasi.dokumen.lihat', $dok->id_dokumen) }}" target="_blank" rel="noopener" class="rd-doc-file">{{ $dok->nama_file }} <i class="bi bi-box-arrow-up-right"></i></a>
                                </div>
                            </div>
                            @if ($stDok === 'Menunggu')
                                {{-- Keputusan final: begitu Valid/Tidak Valid dipilih, tombol hilang dan tidak bisa diubah lagi. --}}
                                <div class="d-flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('admin.reservasi.dokumen.verifikasi', $dok->id_dokumen) }}"
                                          data-confirm="Dokumen {{ $dok->nama_file }} akan ditandai Valid. Keputusan ini tidak dapat diubah."
                                          data-confirm-title="Validasi dokumen ini?" data-icon="warning"
                                          data-confirm-text="Ya, validasi" data-confirm-color="#25b47e">@csrf
                                        <input type="hidden" name="status_verifikasi" value="Valid">
                                        <button class="btn btn-sm btn-outline-success" title="Tandai Valid"><i class="bi bi-check-lg"></i> Validasi</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.reservasi.dokumen.verifikasi', $dok->id_dokumen) }}"
                                          data-confirm="Dokumen {{ $dok->nama_file }} akan ditandai Tidak Valid. Keputusan ini tidak dapat diubah."
                                          data-confirm-title="Tolak dokumen ini?" data-icon="warning"
                                          data-confirm-text="Ya, tolak" data-confirm-color="#e11d48">@csrf
                                        <input type="hidden" name="status_verifikasi" value="Tidak Valid">
                                        <button class="btn btn-sm btn-outline-danger" title="Tandai Tidak Valid"><i class="bi bi-x-lg"></i> Tolak</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="rd-empty py-2"><i class="bi bi-inbox"></i>Tidak ada dokumen persyaratan pada reservasi ini.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ══════════ KANAN ══════════ --}}
        <div class="col-lg-4">
            <div class="rd-side">
                @if ($bolehTolak)
                    <div class="xcard rd-card mb-3 rd-aksi">
                        <div class="rd-card-head">
                            <h2 class="rd-card-title"><i class="bi bi-ui-checks"></i>Aksi Persetujuan</h2>
                        </div>
                        <div class="rd-card-body">
                            <p class="rd-aksi-note mb-3">Keputusan berlaku untuk <strong>seluruh ruangan berstatus Menunggu</strong> pada reservasi ini. Checklist kelayakan tersedia pada kartu setiap ruangan.</p>
                            <form method="POST" action="{{ route('admin.reservasi.setujui', $r->kode_reservasi) }}" class="d-grid mb-2"
                                  data-confirm="Seluruh ruangan berstatus Menunggu pada reservasi {{ $r->kode_transaksi }} akan disetujui."
                                  data-confirm-title="Setujui reservasi ini?" data-icon="warning"
                                  data-confirm-text="Ya, setujui" data-confirm-color="#25b47e" data-nav-replace>@csrf
                                <button class="btn btn-success" @disabled(! $bolehSetujui)><i class="bi bi-check2-circle me-1"></i>Setujui Semua</button>
                            </form>
                            @unless ($bolehSetujui)
                                <p class="rd-aksi-warn"><i class="bi bi-info-circle"></i>Tombol aktif setelah seluruh checklist kelayakan terpenuhi.</p>
                            @endunless

                            <button class="btn btn-outline-danger w-100" type="button" data-bs-toggle="collapse" data-bs-target="#formTolak"><i class="bi bi-x-circle me-1"></i>Tolak Reservasi</button>
                            <div class="collapse mt-3 @error('alasan') show @enderror" id="formTolak">
                                <form method="POST" action="{{ route('admin.reservasi.tolak', $r->kode_reservasi) }}"
                                      data-confirm="Seluruh ruangan berstatus Menunggu pada reservasi ini akan ditolak."
                                      data-confirm-title="Tolak reservasi ini?" data-icon="warning"
                                      data-confirm-text="Ya, tolak" data-confirm-color="#e11d48" data-nav-replace>@csrf
                                    <label class="form-label small fw-bold">Alasan Penolakan <span class="text-danger">*</span></label>
                                    <textarea name="alasan" data-label="Alasan penolakan" class="form-control mb-2 @error('alasan') is-invalid @enderror" rows="3" minlength="10" maxlength="1000" placeholder="Tuliskan alasan penolakan untuk pemesan" required>{{ old('alasan') }}</textarea>
                                    <button class="btn btn-danger btn-sm w-100">Kirim Penolakan</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Identitas pemesan --}}
                <div class="xcard rd-card mb-3">
                    <div class="rd-card-head">
                        <h2 class="rd-card-title"><i class="bi bi-person-vcard"></i>Identitas Pemesan</h2>
                    </div>
                    <div class="rd-card-body">
                        <div class="rd-person">
                            <span class="rd-avatar">{{ strtoupper(substr($r->pemesan->nama_lengkap, 0, 1)) }}</span>
                            <div class="min-w-0">
                                <div class="rd-person-nama">{{ $r->pemesan->nama_lengkap }}</div>
                                <div class="rd-person-sub">{{ $r->pemesan->email }}</div>
                            </div>
                        </div>
                        <ul class="rd-kontak">
                            <li><i class="bi bi-telephone"></i><div><span class="k">No. Telepon</span><span class="v">{{ $r->pemesan->no_telepon }}</span></div></li>
                            <li><i class="bi bi-person-badge"></i><div><span class="k">Usia</span><span class="v">{{ $r->pemesan->usia }} tahun</span></div></li>
                            <li><i class="bi bi-briefcase"></i><div><span class="k">Pekerjaan</span><span class="v">{{ $r->pemesan->pekerjaan }}</span></div></li>
                            <li><i class="bi bi-geo-alt"></i><div><span class="k">Alamat</span><span class="v">{{ $r->pemesan->alamat }}</span></div></li>
                        </ul>
                    </div>
                </div>

                {{-- Riwayat status --}}
                <div class="xcard rd-card mb-3">
                    <div class="rd-card-head">
                        <h2 class="rd-card-title"><i class="bi bi-clock-history"></i>Riwayat Status</h2>
                    </div>
                    <div class="rd-card-body">
                        <div class="{{ $riwayat->isNotEmpty() ? 'timeline' : '' }}">
                            @forelse ($riwayat as $riw)
                                @php
                                    $baru = $riw->status_baru->value;
                                    // Selesai & Kadaluwarsa hanya diubah otomatis oleh sistem (lihat StatusOtomatisService).
                                    $pelaku = in_array($baru, ['Selesai', 'Kadaluwarsa'], true)
                                        ? 'Sistem (otomatis)'
                                        : ($riw->id_admin ? ($riw->admin?->nama_admin ?? 'Admin') : 'Pemesan');
                                @endphp
                                <div class="titem" style="--dot: {{ $warnaRiwayat[$baru] ?? 'var(--primary)' }}">
                                    <div class="t-status">{{ $riw->status_sebelumnya->value }} <i class="bi bi-arrow-right mx-1 text-muted"></i> {{ $baru }}</div>
                                    <div class="t-meta">{{ $riw->tanggal_perubahan?->translatedFormat('d M Y, H:i') }} · {{ $pelaku }}</div>
                                    @if ($riw->keterangan)<div class="t-ket">{{ $riw->keterangan }}</div>@endif
                                </div>
                            @empty
                                <p class="rd-empty"><i class="bi bi-inbox"></i>Belum ada perubahan status.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
