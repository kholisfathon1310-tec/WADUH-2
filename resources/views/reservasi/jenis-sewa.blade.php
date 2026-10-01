@extends('layouts.reservasi')
@section('title', 'Pilih Jenis Sewa')

@section('stepper')
    @include('reservasi.partials.stepper', ['step' => 2])
@endsection

@php
    $ikon = ['Jam' => 'bi-clock', 'Hari' => 'bi-calendar-day', 'Bulan' => 'bi-calendar-month'];
    $desk = [
        'Jam'   => 'Sesuai untuk rapat dan kegiatan singkat. Biaya dihitung per jam pemakaian.',
        'Hari'  => 'Untuk kegiatan satu hari atau lebih. Satu hari dihitung 8 jam pemakaian (08.00–16.00).',
        'Bulan' => 'Sewa jangka panjang untuk kantor atau kegiatan usaha.',
    ];
@endphp

@section('content')
    <nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('reservasi.index') }}">Kategori</a></li>
        <li class="breadcrumb-item active">{{ $kategori }}</li>
    </ol></nav>

    <div class="row g-3" data-reveal>
        @foreach ($jenis as $j)
            <div class="col-md-4">
                <a href="{{ route('reservasi.lantai', ['kategori' => $kategori, 'jenis' => $j->id_jenis_sewa]) }}" class="xcard hover h-100 p-4">
                    <div class="icon-tile mb-3"><i class="bi {{ $ikon[$j->satuan->value] ?? 'bi-clock-history' }}"></i></div>
                    <h2 class="h5 mb-1">Per {{ $j->satuan->value }}</h2>
                    <p class="text-muted small mb-2">{{ $desk[$j->satuan->value] ?? '' }}</p>
                    <span class="badge text-bg-light border mb-3">Durasi minimal {{ $j->durasi_minimum }} {{ strtolower($j->satuan->value) }}</span><br>
                    <span class="fw-bold" style="color:var(--primary)">Pilih <i class="bi bi-arrow-right"></i></span>
                </a>
            </div>
        @endforeach
    </div>
@endsection
