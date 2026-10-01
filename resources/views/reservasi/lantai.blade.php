@extends('layouts.customer')
@section('title', 'Pilih Lantai')

@section('content')
    <nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('reservasi.index') }}">Fasilitas</a></li>
        <li class="breadcrumb-item"><a href="{{ route('reservasi.index', ['kategori' => $kategori]) }}">{{ $kategori }}</a></li>
        <li class="breadcrumb-item active">Denah per Lantai</li>
    </ol></nav>

    <div class="row g-3" data-reveal>
        @forelse ($lantai as $l)
            <div class="col-6 col-md-3">
                <a href="{{ route('reservasi.denah', ['kategori' => $kategori, 'lantai' => $l->id_lantai, 'jenis' => $jenis?->id_jenis_sewa]) }}"
                   class="xcard hover text-center p-3 p-sm-4 h-100" style="border-bottom:3px solid var(--primary)">
                    <div class="mx-auto mb-2" style="width:3.6rem;height:3.6rem;border-radius:1.1rem;display:grid;place-items:center;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;font-weight:800;font-size:1.3rem;font-family:'Plus Jakarta Sans',sans-serif;box-shadow:0 10px 20px rgba(14,107,125,.28)">{{ $l->nomor_lantai }}</div>
                    <div class="fw-bold">Lantai {{ $l->nomor_lantai }}</div>
                    <div class="small" style="color:var(--primary)">Lihat denah <i class="bi bi-arrow-right"></i></div>
                </a>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-warning">Tidak ada lantai dengan fasilitas yang sesuai.</div></div>
        @endforelse
    </div>
@endsection
