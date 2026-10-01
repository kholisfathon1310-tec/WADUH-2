@extends('layouts.reservasi')
@section('title', 'Jelajah Fasilitas')

@section('content')
    <div class="page-head mb-4 p-4 p-md-5 rounded-4 text-white" style="background:linear-gradient(115deg,#0d2a3a,#145f7c 55%,#168b88)" data-reveal>
        <p class="eyebrow-sm mb-1" style="color:#a9e6dd">Jelajah Fasilitas</p>
        <h1 class="h3 mb-1">Pilih Jenis Fasilitas</h1>
        <p class="mb-0" style="opacity:.85">Lihat denah dan detail setiap ruangan di gedung BITC.</p>
    </div>

    @if ($kategori->isEmpty())
        <div class="alert alert-warning">Belum ada fasilitas yang tersedia.</div>
    @else
        <div class="row g-3" data-reveal>
            @foreach ($kategori as $kat)
                @php $meta = \App\Support\KategoriMeta::get($kat); @endphp
                <div class="col-md-4">
                    <a href="{{ route('fasilitas.lantai', ['kategori' => $kat]) }}" class="xcard hover h-100 overflow-hidden d-flex flex-column">
                        <div class="position-relative">
                            <img src="{{ asset($meta['gambar']) }}" alt="{{ $kat }}" style="width:100%; height:170px; object-fit:cover">
                            @if ($meta['lokasi'])
                                <span class="avail position-absolute top-0 end-0 m-2" style="background:rgba(255,255,255,.92); color:{{ $meta['warna'] }}"><i class="bi bi-layers"></i> {{ $meta['lokasi'] }}</span>
                            @endif
                        </div>
                        <div class="p-3 p-sm-4 d-flex flex-column flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="icon-tile" style="width:2.4rem; height:2.4rem; font-size:1rem; background:{{ $meta['warna'] }}1a; color:{{ $meta['warna'] }}"><i class="bi {{ $meta['ikon'] }}"></i></span>
                                <h2 class="h5 mb-0">{{ $kat }}</h2>
                            </div>
                            <p class="text-muted small mb-2">{{ $meta['desk'] }}</p>
                            <ul class="list-unstyled small text-muted mb-3">
                                @foreach (array_slice($meta['dapat'], 0, 3) as $d)
                                    <li><i class="bi bi-check-circle-fill me-1" style="color:{{ $meta['warna'] }}"></i>{{ $d }}</li>
                                @endforeach
                                @if (count($meta['dapat']) > 3)
                                    <li class="ms-4 fst-italic">dan {{ count($meta['dapat']) - 3 }} fasilitas lainnya</li>
                                @endif
                            </ul>
                            <div class="fw-bold mt-auto" style="color:{{ $meta['warna'] }}">Lihat {{ $kat }} <i class="bi bi-arrow-right"></i></div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif
@endsection
