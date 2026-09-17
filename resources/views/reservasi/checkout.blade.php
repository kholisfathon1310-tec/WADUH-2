@extends('layouts.customer')
@section('title', 'Checkout & Keranjang')

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
        .ck-fac-chip { display:inline-flex; align-items:center; gap:.35rem; font-size:.7rem; font-weight:600;
            color:#475569; background:#fff; padding:.32rem .65rem; border-radius:.55rem;
            border:1px solid var(--line); }
        .ck-link-btn { display:inline-flex; align-items:center; gap:.3rem; background:none; border:0;
            font-size:.72rem; font-weight:700; color:var(--muted); padding:0; cursor:pointer;
            transition:color .15s ease; margin-left:auto; }
        .ck-link-btn:hover { color:var(--primary-dark); }
        .ck-link-btn.danger { color:var(--rose); margin-left:0; }
        .ck-link-btn.danger:hover { color:#be123c; }
        .ck-link-btn i { font-size:.8em; }

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
        .ck-total .amount { font-size:1.75rem; font-weight:800; color:var(--primary-darker);
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

        /* DATA DIRI */
        .ck-personal-head { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.5rem;
            padding-bottom:.85rem; border-bottom:1px solid var(--line-soft); margin-bottom:1rem; }
        .ck-personal-head h3 { font-size:.95rem; font-weight:800; margin:0; color:var(--ink);
            display:flex; align-items:center; gap:.5rem; }
        .ck-personal-head h3 i { color:var(--primary); }
        .ck-personal-head .edit-link { font-size:.72rem; font-weight:700; color:var(--primary-dark);
            text-decoration:none; }
        .ck-personal-head .edit-link:hover { text-decoration:underline; }

        .ck-pd-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(11rem, 1fr));
            gap:1rem 1.5rem; }
        .ck-pd-item { display:flex; align-items:flex-start; gap:.7rem; }
        .ck-pd-item .ic { display:grid; place-items:center; width:2.35rem; height:2.35rem; border-radius:.7rem;
            background:var(--primary-soft); color:var(--primary-dark); font-size:.9rem; flex:none;
            border:1px solid var(--primary-softer); }
        .ck-pd-item small { display:block; color:var(--soft); font-size:.62rem; font-weight:800;
            letter-spacing:.08em; text-transform:uppercase; margin-bottom:.15rem; }
        .ck-pd-item .val { font-size:.82rem; font-weight:700; color:var(--ink); word-break:break-word; }

        .ck-dok-notice { background:linear-gradient(135deg, var(--amber-tint), #fff8e1);
            border:1px solid #fde68a; border-radius:1rem; padding:1rem 1.15rem;
            display:flex; align-items:flex-start; gap:.85rem; margin-bottom:1rem; }
        .ck-dok-notice .ic { display:grid; place-items:center; width:2.3rem; height:2.3rem;
            border-radius:.7rem; background:#fde68a; color:#78350f; font-size:1rem; flex:none; }
        .ck-dok-notice p { font-size:.75rem; color:#78350f; margin:0; line-height:1.55; }
        .ck-dok-notice b { color:#5a2b0d; }

        /* EMPTY STATE */
        .ck-empty { padding:4rem 2rem; text-align:center; }
        .ck-empty .ic-wrap { display:grid; place-items:center; width:5.5rem; height:5.5rem; margin:0 auto 1.5rem;
            border-radius:1.4rem; background:var(--primary-soft); color:var(--primary);
            font-size:2rem; border:1px solid var(--primary-softer); }
        .ck-empty h2 { font-size:1.35rem; font-weight:800; color:var(--ink); margin:0 0 .5rem; }
        .ck-empty p { font-size:.85rem; color:var(--muted); max-width:24rem; margin:0 auto 1.5rem; line-height:1.6; }
    </style>

    {{-- ── HEAD ─────────────────────────────────────── --}}
    <div class="page-head mb-4" data-reveal>
        <nav aria-label="breadcrumb" class="mb-2" style="font-size:.72rem;">
            <span style="color:var(--primary-dark); font-weight:700;">Fasilitas</span>
            <i class="bi bi-chevron-right mx-1" style="color:var(--soft); font-size:.65em;"></i>
            <span style="color:var(--primary-dark); font-weight:800; text-transform:uppercase; letter-spacing:.06em;">Konfirmasi Reservasi</span>
        </nav>
        <h1 class="h3 mb-1">Keranjang Reservasi</h1>
        <p class="text-muted mb-0 lead">Periksa kembali item reservasi Anda, lengkapi data kontak pemesan, lalu ajukan verifikasi berkas.</p>
    </div>

    @if (count($items) === 0)
        {{-- ── EMPTY STATE ────────────────────────────── --}}
        <div class="xcard ck-empty mx-auto" style="max-width:640px;" data-reveal>
            <div class="ic-wrap"><i class="bi bi-bag"></i></div>
            <h2>Keranjang Masih Kosong</h2>
            <p>Anda belum memilih fasilitas atau unit kerja untuk direservasi. Silakan telusuri katalog ruangan atau area kerja BITC yang tersedia.</p>
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
                                        {{ $item['tanggal_mulai'] }}@if($item['tanggal_selesai'] !== $item['tanggal_mulai']) → {{ $item['tanggal_selesai'] }}@endif
                                    </span>
                                </div>
                                <div class="cell">
                                    <small>Durasi</small>
                                    <span class="val">
                                        <i class="bi bi-clock"></i>
                                        @if ($item['jam_mulai'])
                                            {{ substr($item['jam_mulai'],0,5) }} - {{ substr($item['jam_selesai'],0,5) }}
                                        @else
                                            {{ $item['durasi'] }} {{ strtolower($item['satuan']) }}
                                        @endif
                                    </span>
                                </div>
                                <div class="cell">
                                    <small>Status</small>
                                    <span class="chip menunggu">Menunggu Verifikasi</span>
                                </div>
                            </div>

                            {{-- Fasilitas bawaan chip + tombol aksi (1 baris) --}}
                            <div class="ck-facilities-line">
                                @foreach ($item['fasilitas_bawaan'] ?? [] as $fasilitas)
                                    <span class="ck-fac-chip">{{ $fasilitas }}</span>
                                @endforeach
                                <a href="{{ route('reservasi.fasilitas.show', ['fasilitas' => $item['id_fasilitas'], 'jenis' => $item['id_jenis_sewa'], 'edit_index' => $index]) }}" class="ck-link-btn">
                                    <i class="bi bi-pencil-square"></i>Ubah
                                </a>
                                <form method="POST" action="{{ route('reservasi.keranjang.hapus', $index) }}"
                                      data-confirm="{{ $item['nama_fasilitas'] }} akan dikeluarkan dari keranjang."
                                      data-confirm-title="Hapus item ini?" data-icon="warning"
                                      data-confirm-text="Ya, hapus" data-confirm-color="#e11d48" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ck-link-btn danger">
                                        <i class="bi bi-trash3"></i>Hapus
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- Data Pemesan (compact) --}}
                <div class="xcard p-4 mt-3">
                    <div class="ck-personal-head">
                        <h3><i class="bi bi-person-vcard-fill"></i>Data Pemesan &amp; Kontak</h3>
                        <a href="{{ route('customer.akun.profil') }}" class="edit-link">
                            <i class="bi bi-pencil me-1"></i>Ubah di Profil
                        </a>
                    </div>
                    <div class="ck-pd-grid">
                        <div class="ck-pd-item">
                            <span class="ic"><i class="bi bi-person"></i></span>
                            <div><small>Nama</small><div class="val">{{ $pemesan->nama_lengkap }}</div></div>
                        </div>
                        <div class="ck-pd-item">
                            <span class="ic"><i class="bi bi-envelope"></i></span>
                            <div><small>Email</small><div class="val">{{ $pemesan->email }}</div></div>
                        </div>
                        <div class="ck-pd-item">
                            <span class="ic"><i class="bi bi-telephone"></i></span>
                            <div><small>Telepon</small><div class="val">{{ $pemesan->no_telepon }}</div></div>
                        </div>
                        <div class="ck-pd-item">
                            <span class="ic"><i class="bi bi-briefcase"></i></span>
                            <div><small>Pekerjaan</small><div class="val">{{ $pemesan->pekerjaan }}</div></div>
                        </div>
                    </div>
                </div>

                {{-- Dokumen persyaratan (bulanan only) --}}
                @if ($hasBulan)
                    @php $ruangBulan = collect($items)->where('satuan', 'Bulan')->pluck('nama_fasilitas'); @endphp
                    <form method="POST" action="{{ route('reservasi.checkout') }}" enctype="multipart/form-data" id="formCheckout"
                          data-confirm="Reservasi akan dikirim untuk diverifikasi admin. Pastikan data & jadwal sudah benar."
                          data-confirm-title="Kirim reservasi ini?" data-icon="question" data-confirm-text="Ya, kirim">
                        @csrf
                        <div class="xcard p-4 mt-3">
                            <div class="ck-personal-head">
                                <h3><i class="bi bi-paperclip"></i>Dokumen Persyaratan <span class="text-muted fw-normal ms-1" style="font-size:.75rem;">(Wajib untuk sewa bulanan)</span></h3>
                            </div>
                            <div class="ck-dok-notice">
                                <span class="ic"><i class="bi bi-info-circle-fill"></i></span>
                                <p><b>Company Profile / Legalitas / KTP Penanggung Jawab</b><br>
                                    PDF, JPG, atau PNG, maksimal 5 MB per file. Berlaku untuk
                                    {{ $ruangBulan->count() > 1 ? 'semua ruangan bulanan (' . $ruangBulan->implode(', ') . ')' : $ruangBulan->first() }},
                                    cukup diunggah sekali.
                                </p>
                            </div>
                            <div data-dok-group>
                                <div class="d-flex gap-2 align-items-center mb-2">
                                    <input type="file" name="dokumen[]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" multiple required>
                                </div>
                                <button type="button" class="btn btn-sm btn-brand-outline" onclick="tambahFile()">
                                    <i class="bi bi-plus-lg me-1"></i>Tambah file lain
                                </button>
                            </div>
                            <script>
                                function tambahFile() {
                                    const group = document.querySelector('[data-dok-group]');
                                    const baris = document.createElement('div');
                                    baris.className = 'd-flex gap-2 align-items-center mb-2';
                                    const input = document.createElement('input');
                                    input.type = 'file'; input.name = 'dokumen[]';
                                    input.className = 'form-control form-control-sm';
                                    input.accept = '.pdf,.jpg,.jpeg,.png'; input.multiple = true;
                                    const hapus = document.createElement('button');
                                    hapus.type = 'button';
                                    hapus.className = 'btn btn-sm btn-outline-danger flex-shrink-0';
                                    hapus.title = 'Batalkan file ini';
                                    hapus.innerHTML = '<i class="bi bi-x-lg"></i>';
                                    hapus.onclick = () => baris.remove();
                                    baris.append(input, hapus);
                                    group.insertBefore(baris, group.lastElementChild);
                                }
                            </script>
                        </div>
                    </form>
                @endif
            </div>

            {{-- ══════════════ KOLOM KANAN: RINGKASAN ══════════════ --}}
            <div class="col-lg-5" data-reveal>
                <div class="xcard ck-summary ck-summary-card">
                    <div class="ck-sum-head">
                        <h3>Ringkasan Biaya</h3>
                        <span class="rid">#BITC-RSV-{{ str_pad(count($items) * 100 + 42, 4, '0', STR_PAD_LEFT) }}</span>
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
                        <span class="duration-chip">{{ count($items) }} Ruangan</span>
                    </div>

                    <div class="ck-actions">
                        @if ($hasBulan)
                            <button type="submit" form="formCheckout" class="btn btn-brand ck-submit-btn">
                                <span>Ajukan Verifikasi Sekarang</span>
                                <i class="bi bi-arrow-right arrow"></i>
                            </button>
                        @else
                            <form method="POST" action="{{ route('reservasi.checkout') }}"
                                  data-confirm="Reservasi akan dikirim untuk diverifikasi admin."
                                  data-confirm-title="Kirim reservasi ini?" data-icon="question" data-confirm-text="Ya, kirim">
                                @csrf
                                <button type="submit" class="btn btn-brand ck-submit-btn w-100">
                                    <span>Ajukan Verifikasi Sekarang</span>
                                    <i class="bi bi-arrow-right arrow"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection