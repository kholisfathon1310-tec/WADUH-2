{{--
    Partial internal untuk <x-denah> — satu kartu ruangan (dipakai berulang di grid_main,
    rows_after, dan rows supaya markupnya tidak tertulis 3x). Menerima $r dari $renderRoom().
--}}
@if ($r['clickableThis'])
    <a href="{{ $r['href'] }}" class="{{ implode(' ', $r['classes']) }}"
       style="flex-grow: {{ $r['flexGrow'] }};"
       data-kode="{{ $r['kode'] }}" data-id="{{ $r['f']->id_fasilitas }}" data-label="{{ $r['label'] }}"
       data-nama="{{ $r['f']->nama_fasilitas }}" data-status="{{ $r['status'] }}" data-status-label="{{ $r['statusLabel'] }}"
       data-luas="{{ $r['luas'] }}" data-kapasitas="{{ $r['f']->kapasitas }}" data-harga="{{ $r['harga'] }}"
       data-hint="{{ $r['hint'] }}">
        <span class="d-kode">{{ $r['label'] }}</span>
        @if ($r['luas'])
            <span class="d-luas">{{ number_format($r['luas'], 2, ',', '.') }} m²</span>
        @endif
        <span class="d-status {{ $r['status'] }}">{{ strtoupper($r['statusSingkat']) }}</span>
    </a>
@else
    <div class="{{ implode(' ', $r['classes']) }}" style="flex-grow: {{ $r['flexGrow'] }};"
         @if ($r['tipe'] === 'ruangan' && $r['kode'])
             data-kode="{{ $r['kode'] }}" data-label="{{ $r['label'] }}"
             data-nama="{{ $r['f']?->nama_fasilitas ?? $r['kode'] }}" data-status="{{ $r['status'] }}" data-status-label="{{ $r['statusLabel'] }}"
             data-luas="{{ $r['luas'] }}" data-kapasitas="{{ $r['f']?->kapasitas }}" data-harga="{{ $r['harga'] }}"
             data-hint="{{ $r['hint'] }}"
         @endif>
        @if ($r['tipe'] === 'ruangan')
            <span class="d-kode">{{ $r['label'] }}</span>
            @if ($r['luas'])
                <span class="d-luas">{{ number_format($r['luas'], 2, ',', '.') }} m²</span>
            @endif
            <span class="d-status {{ $r['status'] }}">{{ strtoupper($r['statusSingkat']) }}</span>
        @else

            <span class="d-label">{{ $r['label'] }}</span>
        @endif
    </div>
@endif
