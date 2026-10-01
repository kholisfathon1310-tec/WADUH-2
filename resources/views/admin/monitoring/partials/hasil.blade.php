@php
    $statusByKode = $fasilitas->mapWithKeys(fn ($f) => [$f->kode_fasilitas => $status[$f->id_fasilitas] ?? 'merah'])->all();
    $tplDetail = route('admin.monitoring.detail', array_merge(['fasilitas' => '__ID__'], $slot));
@endphp
@if ($lantai)
    {{-- hide-header: judul lantai + jumlah ruang sudah ditampilkan di kartu hero di atas,
         header internal denah di sini cuma mengulang info yang sama. --}}
    <x-denah :lantai="$lantai->nomor_lantai" :status-per-fasilitas="$statusByKode" :clickable="false" :link-template="$tplDetail" :hide-header="true"/>
    <p class="text-muted small mt-2 mb-0"><i class="bi bi-info-circle me-1"></i>Pilih ruangan pada denah untuk melihat detail fasilitas, jadwal reservasi, dan data pemesan. Ruangan berwarna merah berarti terisi atau tidak aktif.</p>
@else
    <div class="alert alert-warning">Data lantai tidak ditemukan.</div>
@endif
