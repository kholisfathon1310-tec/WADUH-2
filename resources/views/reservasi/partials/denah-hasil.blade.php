@php
    $satuan = $jenis?->satuan->value;
    $jumlahTersedia = $status->filter(fn ($s) => $s === 'hijau')->count();
    $jumlahSebagian = $status->filter(fn ($s) => $s === 'kuning')->count();
    $jumlahTerisi = $status->filter(fn ($s) => $s === 'merah')->count();
@endphp

<div data-reveal>
    <x-denah.floor-header
        :nomor-lantai="$lantai->nomor_lantai"
        :kategori="$kategori"
        :satuan="$satuan"
        :total="$fasilitas->count()"
        :kosong="$jumlahTersedia"
        :sebagian="$jumlahSebagian"
        :terisi="$jumlahTerisi"
    />
</div>

{{-- Denah interaktif — status tiap ruangan dihitung dari jadwal reservasi tersimpan
     (AvailabilityService::statusFasilitas) untuk tanggal pada filter di atas. --}}
@php
    $statusByKode = $fasilitas->mapWithKeys(fn ($f) => [$f->kode_fasilitas => $status[$f->id_fasilitas] ?? 'hijau'])->all();
    $paramDetail = ['fasilitas' => '__ID__', 'jenis' => $jenis?->id_jenis_sewa, 'tanggal_mulai' => $slot['tanggal_mulai']];
    if ($satuan !== 'Jam' && $slot['tanggal_selesai'] !== $slot['tanggal_mulai']) {
        $paramDetail['tanggal_selesai'] = $slot['tanggal_selesai'];
    }
    $tplDetail = route('reservasi.fasilitas.show', $paramDetail);
@endphp
<x-denah :lantai="$lantai->nomor_lantai" :status-per-fasilitas="$statusByKode" :clickable="true" :link-template="$tplDetail" :jenis="$jenis?->id_jenis_sewa" :satuan="$satuan" :kategori="$kategori" :hide-header="true"/>

<p class="text-muted small mt-2 mb-0"><i class="bi bi-info-circle me-1"></i>Pilih satu atau beberapa ruangan yang dapat dipesan, lalu tekan <strong>Lihat Detail</strong> untuk mengisi jadwal.</p>

