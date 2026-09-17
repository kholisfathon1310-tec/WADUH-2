@php
    $steps = [1 => 'Pilih Fasilitas', 2 => 'Keranjang & Konfirmasi'];
@endphp
<div class="stepper">
    @foreach ($steps as $n => $label)
        <div class="step {{ $n < $step ? 'done' : ($n === $step ? 'now' : '') }}">
            <span class="dot">@if($n < $step)<i class="bi bi-check-lg"></i>@else{{ $n }}@endif</span>
            <span class="step-label">{{ $label }}</span>
            @unless ($loop->last)<span class="bar"></span>@endunless
        </div>
    @endforeach
</div>
