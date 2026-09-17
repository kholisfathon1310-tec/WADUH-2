@php
    $jumlahKosong = $status->filter(fn ($s) => $s === 'hijau')->count();
    $jumlahTerisi = $status->filter(fn ($s) => $s !== 'hijau')->count();
@endphp

<div data-reveal>
    <x-denah.floor-header
        :nomor-lantai="$lantai->nomor_lantai"
        :kategori="$kategori"
        :satuan="$jenis?->satuan->value"
        :total="$fasilitas->count()"
        :kosong="$jumlahKosong"
        :terisi="$jumlahTerisi"
    />
</div>

{{-- Denah interaktif SVG — koordinat presisi dari config/denah.php --}}
@php
    $statusByKode = $fasilitas->mapWithKeys(fn ($f) => [$f->kode_fasilitas => $status[$f->id_fasilitas] ?? 'hijau'])->all();
    $tplDetail = route('reservasi.fasilitas.show', array_merge(['fasilitas' => '__ID__', 'jenis' => $jenis?->id_jenis_sewa], $slot));
@endphp
<x-denah :lantai="$lantai->nomor_lantai" :status-per-fasilitas="$statusByKode" :clickable="true" :link-template="$tplDetail" :jenis="$jenis?->id_jenis_sewa" :kategori="$kategori"/>

<p class="text-muted small mt-2 mb-0"><i class="bi bi-info-circle me-1"></i>Klik ruangan <strong class="text-success">hijau/kuning</strong> untuk memilih (bisa lebih dari satu), lalu tekan <strong>Lihat Detail</strong>. Ruangan <strong>merah</strong> penuh atau sedang tidak disewakan.</p>
