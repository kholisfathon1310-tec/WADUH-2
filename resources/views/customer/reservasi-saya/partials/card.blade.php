{{--
    Satu kartu = satu ruangan (Reservasi). Variabel: $r (Reservasi).
--}}
@php
    $fasilitas = $r->tarifSewa->fasilitas ?? null;
    $statusVal = $r->status_reservasi->value;
    $statusLabel = $chipLabel[$statusVal] ?? $statusVal;
@endphp
<article class="xcard hover rs-card"
         data-rs-status="{{ $statusVal }}"
         data-rs-search="{{ \Illuminate\Support\Str::lower(($fasilitas->nama_fasilitas ?? '').' '.$r->kode_reservasi.' '.$r->kode_transaksi) }}">
    <div class="thumb">
        <img src="{{ $fasilitas?->fotoUrls()[0] ?? asset('images/lt1.png') }}" alt="{{ $fasilitas->nama_fasilitas ?? '' }}" style="width:100%;height:100%;object-fit:cover;">
    </div>

    <div class="info">
        <div class="nm-row">
            <h3 class="nm">{{ $fasilitas->nama_fasilitas ?? '—' }}</h3>
            <span class="chip {{ $chipClass[$statusVal] ?? '' }}">{{ $statusLabel }}</span>
        </div>
        <div class="meta">
            <span class="code"><i class="bi bi-upc"></i> #{{ $r->kode_reservasi }}</span>
            <span class="sep">•</span>
            <span><i class="bi bi-calendar3"></i> {{ $r->tanggal_mulai->translatedFormat('d M Y') }}</span>
            <span class="sep">•</span>
            <span><i class="bi bi-building"></i> Lantai {{ $fasilitas->lantai->nomor_lantai ?? '—' }} @if($fasilitas?->kategori_fasilitas) - {{ $fasilitas->kategori_fasilitas }} @endif</span>
            @if ($r->jam_mulai && $r->jam_selesai)
                <span class="sep">•</span>
                <span><i class="bi bi-clock"></i> {{ substr($r->jam_mulai,0,5) }} - {{ substr($r->jam_selesai,0,5) }} WIB</span>
            @endif
        </div>
        @if ($r->keperluan)
            <div class="desc">{{ \Illuminate\Support\Str::limit($r->keperluan, 90) }}</div>
        @endif
    </div>

    <div class="side">
        <div>
            <span class="lbl d-block">Total Biaya</span>
            <span class="price">Rp {{ number_format($r->total_biaya, 0, ',', '.') }}</span>
        </div>
        <div class="actions">
            @if ($statusVal === 'Selesai')
                <a href="{{ route('reservasi.index') }}" class="btn btn-sm btn-brand">
                    <i class="bi bi-arrow-clockwise me-1"></i>Pesan Lagi
                </a>
            @endif
            <a href="{{ route('customer.reservasi-saya.show', $r->kode_reservasi) }}" class="btn btn-sm btn-detail
                {{ $statusVal === 'Menunggu' ? 'btn-brand' : 'btn-brand-outline' }}">
                Lihat Detail <i class="bi bi-chevron-right"></i>
            </a>
        </div>
    </div>
</article>
