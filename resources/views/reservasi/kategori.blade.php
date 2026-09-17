@extends('layouts.customer')
@section('title', 'Fasilitas')

@section('content')
    @php
        $fotoLantai = [
            '1'  => 'images/lt1(home).png',
            '2'  => 'images/lt2(home).png',
            '3A' => 'images/lt3a(home).png',
            '3B' => 'images/lt3b(home).png',
            '5'  => 'images/lt5.png',
        ];
    @endphp
    <style>
        .lt-card { overflow:hidden; display:flex; flex-direction:column; height:100%; }
        .lt-card .head { position:relative; height:150px; background-size:cover; background-position:center; }
        .lt-card .head::after { content:''; position:absolute; inset:0; background:linear-gradient(180deg, rgba(9,23,36,.05), rgba(9,23,36,.55)); }
        .lt-card .badge-lt { position:absolute; left:.9rem; bottom:.75rem; z-index:1; color:#fff; font-weight:800; }
        .lt-card .badge-lt small { display:block; font-size:.65rem; font-weight:700; opacity:.85; text-transform:uppercase; letter-spacing:.06em; }
        .lt-card .badge-lt b { font-size:1.25rem; }
        .lt-card .body { padding:1.1rem 1.25rem 1.25rem; display:flex; flex-direction:column; flex:1; }
        .lt-card .desc { color:var(--muted); font-size:.85rem; margin-bottom:.75rem; }
        .lt-card .pills-row { display:flex; flex-wrap:wrap; gap:.4rem; margin-bottom:1rem; }
        .lt-card .pill { font-size:.7rem; font-weight:700; padding:.3rem .65rem; border-radius:9999px; display:inline-flex; align-items:center; gap:.3rem; }
        .lt-card .pill-unit { background:var(--surface-2); color:var(--muted); border:1px solid var(--line); }
        .lt-card .pill-tersedia { background:#e6f6f0; color:#176b87; }
        .lt-card .pill-habis { background:#fdeaea; color:#be123c; }
        .lt-card .btn-lantai { margin-top:auto; display:inline-flex; align-items:center; justify-content:center; gap:.5rem; padding:.7rem 1rem; border-radius:.85rem; background:var(--primary); color:#fff; font-weight:700; text-decoration:none; transition:background .15s ease; }
        .lt-card .btn-lantai:hover { background:var(--primary-dark); color:#fff; }
    </style>

    <div class="page-head mb-4 p-4 p-md-5 rounded-4 text-white" style="background:linear-gradient(115deg,var(--primary-dark),var(--primary) 60%,var(--teal) 130%)" data-reveal>
        <p class="eyebrow-sm mb-1" style="color:#cdece6">Fasilitas</p>
        <h1 class="h3 mb-1">Pilih Lantai</h1>
        <p class="mb-0" style="opacity:.85">Lima lantai, tiga jenis ruang — pilih lantai untuk melihat denah &amp; detail tiap ruangannya.</p>
    </div>

    <div class="row g-4" data-reveal>
        @foreach ($daftarLantai as $l)
            @php
                $meta = \App\Support\KategoriMeta::get($l['kategori']);
                $foto = asset($fotoLantai[$l['nomor']] ?? 'images/lt1.png');
                $habis = (int) $l['tersedia'] === 0;
            @endphp
            <div class="col-sm-6 col-lg-4">
                <div class="xcard lt-card">
                    <div class="head" style="background-image:url('{{ $foto }}')">
                        <span class="badge-lt">
                            <small>Lantai</small>
                            <b>{{ $l['nomor'] }}</b>
                        </span>
                    </div>
                    <div class="body">
                        <h2 class="h6 mb-1"><i class="bi {{ $meta['ikon'] }} me-1"></i>{{ $l['kategori'] }}</h2>
                        <p class="desc">{{ $meta['desk'] }}</p>
                        <div class="pills-row">
                            <span class="pill pill-unit"><i class="bi bi-door-open"></i> {{ $l['total'] }} unit</span>
                            <span class="pill {{ $habis ? 'pill-habis' : 'pill-tersedia' }}">
                                <i class="bi {{ $habis ? 'bi-slash-circle' : 'bi-check-circle' }}"></i>
                                {{ $l['tersedia'] }} tersedia
                            </span>
                            @if ($l['kapasitas_maks'])
                                <span class="pill pill-unit"><i class="bi bi-people"></i> hingga {{ $l['kapasitas_maks'] }} orang</span>
                            @endif
                        </div>
                        <a href="{{ route('reservasi.denah', ['kategori' => $l['kategori'], 'lantai' => $l['id']]) }}" class="btn-lantai">
                            <span>Lihat Detail Lantai {{ $l['nomor'] }}</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
