@extends('layouts.customer')
@section('title', 'Pilih Lantai')

@section('content')
    <nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('reservasi.index') }}">Fasilitas</a></li>
        <li class="breadcrumb-item"><a href="{{ route('reservasi.index', ['kategori' => $kategori]) }}">{{ $kategori }}</a></li>
        <li class="breadcrumb-item active">Denah per Lantai</li>
    </ol></nav>

    <div class="page-head mb-4 p-4 p-md-5 rounded-4 text-white" style="background:linear-gradient(115deg,var(--primary-dark),var(--primary) 60%,var(--teal) 130%)" data-reveal>
        <p class="eyebrow-sm mb-1" style="color:#cdece6">{{ $kategori }} @if($jenis) · Per {{ $jenis->satuan->value }} @endif</p>
        <h1 class="h3 mb-1">Pilih Lantai</h1>
        <p class="mb-0" style="opacity:.85">Lantai berikut memiliki fasilitas yang cocok dengan pilihan Anda.</p>
    </div>

    <div class="row g-3" data-reveal>
        @forelse ($lantai as $l)
            <div class="col-6 col-md-3">
                <a href="{{ route('reservasi.denah', ['kategori' => $kategori, 'lantai' => $l->id_lantai, 'jenis' => $jenis?->id_jenis_sewa]) }}"
                   class="xcard hover text-center p-4 h-100" style="border-bottom:3px solid var(--primary)">
                    <div class="mx-auto mb-2" style="width:3.6rem;height:3.6rem;border-radius:1.1rem;display:grid;place-items:center;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;font-weight:800;font-size:1.3rem;font-family:'Plus Jakarta Sans',sans-serif;box-shadow:0 10px 20px rgba(14,107,125,.28)">{{ $l->nomor_lantai }}</div>
                    <div class="fw-bold">Lantai {{ $l->nomor_lantai }}</div>
                    <div class="small" style="color:var(--primary)">Lihat denah <i class="bi bi-arrow-right"></i></div>
                </a>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-warning">Tidak ada lantai dengan fasilitas yang cocok.</div></div>
        @endforelse
    </div>
@endsection
