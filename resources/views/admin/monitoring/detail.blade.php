@extends('admin.layouts.app')
@section('title', 'Detail Fasilitas')

@section('actions')
    <a href="{{ route('admin.monitoring', ['lantai' => $fasilitas->id_lantai]) }}" class="btn btn-brand-outline btn-sm d-none d-md-inline-flex align-items-center"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
@endsection

@section('content')
    @php
        $foto = $fasilitas->fotoUrls()[0] ?? null;
        $disetujui = $reservasiAktif->filter(fn ($r) => $r->status_reservasi === \App\Enums\StatusReservasi::Disetujui)->count();
        $menunggu = $reservasiAktif->filter(fn ($r) => $r->status_reservasi === \App\Enums\StatusReservasi::Menunggu)->count();
    @endphp

    <style>
        .df-hero { position:relative; height:210px; border-radius:1.35rem 1.35rem 0 0; overflow:hidden; background:linear-gradient(135deg,var(--primary),var(--primary-dark)); }
        .df-hero img { width:100%; height:100%; object-fit:cover; cursor:zoom-in; transition:transform .25s ease; }
        .df-hero img:hover { transform:scale(1.03); }
        .df-hero::after { content:''; position:absolute; inset:0; background:linear-gradient(180deg, rgba(15,36,59,0) 40%, rgba(15,36,59,.82) 100%); pointer-events:none; }
        .df-hero-noimg { display:flex; align-items:center; justify-content:center; }
        .df-hero-noimg i { font-size:2.6rem; color:rgba(255,255,255,.5); }
        .df-hero-caption { position:absolute; left:1.3rem; right:1.3rem; bottom:1rem; z-index:2; display:flex; justify-content:space-between; align-items:flex-end; gap:.75rem; pointer-events:none; }
        .df-hero-caption h2 { color:#fff; font-size:1.15rem; font-weight:800; margin:0; line-height:1.25; }
        .df-hero-caption .kode { color:rgba(255,255,255,.75); font-size:.74rem; font-weight:600; letter-spacing:.04em; }
        .df-hero-caption .badge { pointer-events:none; }
        .df-hero-zoom { position:absolute; top:.85rem; right:.85rem; z-index:2; width:2.1rem; height:2.1rem; display:grid; place-items:center; border-radius:.6rem; background:rgba(15,36,59,.45); color:#fff; font-size:.85rem; pointer-events:none; }

        .df-row { display:flex; align-items:center; gap:.75rem; padding:.65rem 0; border-bottom:1px solid var(--line); }
        .df-row:last-child { border-bottom:none; }
        .df-row .df-ic { display:grid; place-items:center; width:2.05rem; height:2.05rem; border-radius:.65rem; background:var(--surface); color:var(--primary); font-size:.85rem; flex:none; }
        .df-row .df-label { color:var(--muted); font-size:.78rem; font-weight:600; }
        .df-row .df-value { margin-left:auto; font-weight:700; color:var(--ink); font-size:.88rem; text-align:right; }

        .df-tarif { display:flex; justify-content:space-between; align-items:center; padding:.6rem .85rem; border-radius:.85rem; background:var(--surface); }
        .df-tarif + .df-tarif { margin-top:.5rem; }
        .df-tarif .satuan { font-size:.76rem; color:var(--muted); font-weight:600; }
        .df-tarif .harga { font-weight:800; color:var(--primary-dark); font-size:.92rem; }

        .df-table thead th { position:sticky; top:0; background:#fbfdfe; color:var(--muted); font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; padding:.85rem 1rem; border-bottom:1px solid var(--line); white-space:nowrap; }
        .df-table tbody td { padding:.8rem 1rem; border-bottom:1px solid var(--line); vertical-align:middle; }
        .df-table tbody tr:last-child td { border-bottom:none; }
        .df-table tbody tr.df-clickrow { cursor:pointer; }
        .df-table tbody tr.df-clickrow:hover { background:var(--surface); }

        .df-pill { display:inline-flex; align-items:center; font-size:.7rem; font-weight:700; padding:.28rem .7rem; border-radius:2rem; }
        .df-pill.success { background:#e2f7ef; color:#0d8a5f; }
        .df-pill.warning { background:#fff4d6; color:#9a6b00; }
        .df-pill.secondary { background:#eef1f4; color:var(--muted); }

        .df-empty { text-align:center; padding:3.2rem 1rem; color:var(--muted); }
        .df-empty i { font-size:1.8rem; opacity:.4; display:block; margin-bottom:.6rem; }

        .df-keperluan { display:block; max-width:12rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        @media (max-width: 575.98px) {
            .df-hero { height:170px; }
            .df-hero-caption { flex-wrap:wrap; left:1rem; right:1rem; }
            .df-table thead th, .df-table tbody td { padding:.65rem .75rem; }
            .df-lightbox { padding:1rem; }
        }

        .df-lightbox { position:fixed; inset:0; z-index:1080; background:rgba(10,20,35,.9); display:none; align-items:center; justify-content:center; padding:2rem; cursor:zoom-out; }
        .df-lightbox.show { display:flex; }
        .df-lightbox img { max-width:100%; max-height:100%; border-radius:.85rem; box-shadow:0 20px 50px rgba(0,0,0,.4); }
        .df-lightbox-close { position:absolute; top:1.2rem; right:1.4rem; width:2.4rem; height:2.4rem; display:grid; place-items:center; border-radius:50%; background:rgba(255,255,255,.12); color:#fff; font-size:1.1rem; }
    </style>

    <div class="row g-3">
        {{-- ============ KOLOM KIRI — Info Fasilitas ============ --}}
        <div class="col-lg-5" data-reveal>
            <div class="xcard h-100 overflow-hidden">
                <div class="df-hero {{ ! $foto ? 'df-hero-noimg' : '' }}">
                    @if ($foto)
                        <img src="{{ $foto }}" alt="{{ $fasilitas->nama_fasilitas }}" data-lightbox-trigger>
                        <span class="df-hero-zoom"><i class="bi bi-arrows-fullscreen"></i></span>
                    @else
                        <i class="bi bi-image"></i>
                    @endif
                    <div class="df-hero-caption">
                        <div>
                            <span class="kode">{{ $fasilitas->kode_fasilitas }} · {{ $fasilitas->kategori_fasilitas }}</span>
                            <h2>{{ $fasilitas->nama_fasilitas }}</h2>
                        </div>
                        <span class="badge rounded-pill text-bg-{{ $fasilitas->status_aktif->value === 'Aktif' ? 'success' : 'secondary' }}">
                            {{ $fasilitas->status_aktif->value }}
                        </span>
                    </div>
                </div>

                <div class="p-3 p-md-4">
                    <div class="df-row">
                        <span class="df-ic"><i class="bi bi-building"></i></span>
                        <span class="df-label">Lantai</span>
                        <span class="df-value">{{ $fasilitas->lantai->nomor_lantai }}</span>
                    </div>
                    <div class="df-row">
                        <span class="df-ic"><i class="bi bi-people"></i></span>
                        <span class="df-label">Kapasitas</span>
                        <span class="df-value">{{ $fasilitas->kapasitas }} orang</span>
                    </div>
                    <div class="df-row">
                        <span class="df-ic"><i class="bi bi-rulers"></i></span>
                        <span class="df-label">Luas</span>
                        <span class="df-value">{{ $fasilitas->luas }} m²</span>
                    </div>
                </div>

                @if ($tarifAktif->isNotEmpty())
                    <div class="xhead border-top"><span><i class="bi bi-tag me-1"></i>Tarif Sewa Aktif</span></div>
                    <div class="p-3 p-md-4 pt-3">
                        @foreach ($tarifAktif as $t)
                            <div class="df-tarif">
                                <span class="satuan">Per {{ $t->jenisSewa->satuan->value }}</span>
                                <span class="harga">Rp {{ number_format($t->harga, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- ============ KOLOM KANAN — Reservasi Aktif ============ --}}
        <div class="col-lg-7" data-reveal>
            <div class="xcard h-100 overflow-hidden">
                <div class="xhead flex-wrap gap-2">
                    <div>
                        <span class="d-block"><i class="bi bi-calendar-week me-1"></i>Reservasi Aktif</span>
                        <span class="cell-sub">
                            {{ \Illuminate\Support\Carbon::parse($slot['tanggal_mulai'])->translatedFormat('d F Y') }}@if($slot['tanggal_selesai'] !== $slot['tanggal_mulai']) s.d. {{ \Illuminate\Support\Carbon::parse($slot['tanggal_selesai'])->translatedFormat('d F Y') }}@endif
                        </span>
                    </div>
                    <span class="d-flex flex-wrap gap-1">
                        @if ($disetujui) <span class="df-pill success"><i class="bi bi-check-circle me-1"></i>{{ $disetujui }} disetujui</span> @endif
                        @if ($menunggu) <span class="df-pill warning"><i class="bi bi-hourglass-split me-1"></i>{{ $menunggu }} menunggu</span> @endif
                        @if (! $disetujui && ! $menunggu) <span class="df-pill secondary">Tidak ada reservasi</span> @endif
                    </span>
                </div>

                <div class="table-responsive" style="max-height:520px; overflow-y:auto">
                    <table class="table df-table mb-0">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Pemesan</th>
                                <th>Periode</th>
                                <th>Pengguna</th>
                                <th>Keperluan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($reservasiAktif as $r)
                            <tr class="df-clickrow" data-href="{{ route('admin.reservasi.show', $r->kode_reservasi) }}" title="Lihat detail reservasi {{ $r->kode_reservasi }}">
                                <td class="fw-bold" style="color:var(--primary)">{{ $r->kode_reservasi }}</td>
                                <td class="small fw-semibold">{{ $r->pemesan->nama_lengkap }}</td>
                                <td class="small">
                                    <span class="text-nowrap">{{ $r->tanggal_mulai->translatedFormat('d M Y') }}</span>
                                    @if($r->jam_mulai)
                                        <span class="cell-sub d-block text-nowrap">{{ \Illuminate\Support\Str::substr($r->jam_mulai,0,5) }}–{{ \Illuminate\Support\Str::substr($r->jam_selesai,0,5) }} WIB</span>
                                    @elseif($r->tanggal_selesai->ne($r->tanggal_mulai))
                                        <span class="cell-sub d-block text-nowrap">s.d. {{ $r->tanggal_selesai->translatedFormat('d M Y') }}</span>
                                    @endif
                                </td>
                                <td class="small text-nowrap">{{ $r->jumlah_pengguna }} orang</td>
                                <td class="small"><span class="df-keperluan" title="{{ $r->keperluan }}">{{ $r->keperluan }}</span></td>
                                <td>
                                    <span class="df-pill {{ $r->status_reservasi->value === 'Disetujui' ? 'success' : 'warning' }}">{{ $r->status_reservasi->value }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="border-0">
                                    <div class="df-empty">
                                        <i class="bi bi-calendar-x"></i>
                                        Tidak ada reservasi aktif pada tanggal ini.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Lightbox foto fasilitas --}}
    @if ($foto)
        <div class="df-lightbox" data-lightbox>
            <span class="df-lightbox-close"><i class="bi bi-x-lg"></i></span>
            <img src="{{ $foto }}" alt="{{ $fasilitas->nama_fasilitas }}">
        </div>
    @endif

    <script>
        (function () {
            // Baris reservasi: seluruh baris bisa diklik menuju detail.
            document.querySelectorAll('.df-clickrow[data-href]').forEach((row) => {
                row.addEventListener('click', () => { window.location.href = row.dataset.href; });
            });

            // Lightbox foto fasilitas.
            const box = document.querySelector('[data-lightbox]');
            const trigger = document.querySelector('[data-lightbox-trigger]');
            if (box && trigger) {
                trigger.addEventListener('click', () => box.classList.add('show'));
                box.addEventListener('click', () => box.classList.remove('show'));
            }
        })();
    </script>
@endsection