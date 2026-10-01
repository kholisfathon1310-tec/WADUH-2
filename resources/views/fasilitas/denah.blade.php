@extends('layouts.reservasi')
@section('title', 'Denah Lantai '.$lantai->nomor_lantai)

@section('content')
    <nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('fasilitas.index') }}">Fasilitas</a></li>
        <li class="breadcrumb-item"><a href="{{ route('fasilitas.lantai', ['kategori' => $kategori]) }}">{{ $kategori }}</a></li>
        <li class="breadcrumb-item active">Lantai {{ $lantai->nomor_lantai }}</li>
    </ol></nav>

    @php
        $statusByKode = $fasilitas->mapWithKeys(fn ($f) => [$f->kode_fasilitas => $status[$f->id_fasilitas] ?? 'hijau'])->all();
        $tplDetail = route('fasilitas.detail', ['fasilitas' => '__ID__']);
    @endphp
    <div data-reveal>
        <x-denah :lantai="$lantai->nomor_lantai" :status-per-fasilitas="$statusByKode" :clickable="false" :link-template="$tplDetail" :kategori="$kategori"/>
    </div>

    <p class="text-muted small mt-2 mb-0"><i class="bi bi-info-circle me-1"></i>Pilih ruangan untuk melihat detail fasilitas: foto, harga sewa, kapasitas, dan fasilitas yang termasuk.</p>
@endsection
