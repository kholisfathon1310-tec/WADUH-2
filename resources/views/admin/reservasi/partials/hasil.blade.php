<div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-3">
    <p class="text-muted small mb-0 text-nowrap"><i class="bi bi-collection me-1"></i>{{ $ringkasan['reservasi'] }} reservasi · {{ $ringkasan['ruangan'] }} ruangan</p>
    @if ($grup->total() > 0)
        <p class="text-muted small mb-0 text-nowrap">Data {{ $grup->firstItem() }}–{{ $grup->lastItem() }} dari {{ $grup->total() }}</p>
    @endif
</div>

@forelse ($grup as $kodeTransaksi => $baris)
    @php $first = $baris->first(); @endphp
    <div class="xcard mb-4 overflow-hidden" data-reveal>
        {{-- Header pemesanan: satu tombol Detail untuk seluruh ruangan --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 px-3 px-md-4 py-3" style="background:var(--surface); border-bottom:1px solid var(--line)">
            <div class="d-flex align-items-center gap-3" style="min-width:0; flex:1 1 14rem;">
                <span class="initial-chip">{{ strtoupper(substr($first->pemesan->nama_lengkap, 0, 1)) }}</span>
                <div style="min-width:0;">
                    <span class="fw-bold" style="color:var(--primary)">{{ $kodeTransaksi ?: '(tanpa kode)' }}</span>
                    <span class="cell-sub d-block mt-1" style="overflow-wrap:anywhere;"><i class="bi bi-person me-1"></i>{{ $first->pemesan->nama_lengkap }} <span class="mx-1 text-muted">·</span> <i class="bi bi-clock me-1"></i>{{ $first->created_at->translatedFormat('d M Y, H:i') }}</span>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3">
                <div class="text-end me-auto me-md-0">
                    <span class="badge text-bg-light border">{{ $baris->count() }} ruangan</span>
                    <span class="fw-bold ms-1" style="color:var(--primary)">Rp {{ number_format($baris->sum('total_biaya'), 0, ',', '.') }}</span>
                </div>
                <a href="{{ route('admin.reservasi.show', $first->kode_reservasi) }}" class="btn btn-sm btn-brand"><i class="bi bi-eye me-1"></i>Detail</a>
                <form method="POST" action="{{ route('admin.reservasi.hapus', $first->kode_reservasi) }}" class="d-inline-flex"
                      data-confirm="Seluruh data reservasi {{ $kodeTransaksi }} ({{ $baris->count() }} ruangan, dokumen, riwayat, dan faktur) akan dihapus permanen dan tidak dapat dikembalikan."
                      data-confirm-title="Hapus reservasi ini?" data-icon="warning"
                      data-confirm-text="Ya, hapus" data-confirm-color="#e11d48">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" data-tip="Hapus reservasi" aria-label="Hapus reservasi"><i class="bi bi-trash3"></i></button>
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle rv-tabel">
                <thead><tr>
                    <th>Ruangan</th><th>Jenis Sewa</th><th>Periode</th><th class="text-center">Pengguna</th><th class="text-end">Total Biaya</th><th class="text-center">Status</th>
                </tr></thead>
                <tbody>
                @foreach ($baris as $r)
                    <tr>
                        <td class="rv-ruang">
                            <span class="cell-main text-nowrap">{{ $r->tarifSewa->fasilitas->nama_fasilitas }}</span>
                            <span class="cell-sub d-block text-nowrap">{{ $r->tarifSewa->fasilitas->kategori_fasilitas }} · Lt {{ $r->tarifSewa->fasilitas->lantai->nomor_lantai }}</span>
                        </td>
                        <td class="rv-jenis"><span class="badge text-bg-light border">Per {{ $r->tarifSewa->jenisSewa->satuan->value }}</span></td>
                        <td class="rv-periode small text-nowrap">
                            @if ($r->jam_mulai)
                                <i class="bi bi-calendar3 me-1 text-muted"></i>{{ $r->tanggal_mulai->translatedFormat('d M Y') }}
                                <span class="text-muted">·</span> {{ \Illuminate\Support\Str::substr($r->jam_mulai,0,5) }}–{{ \Illuminate\Support\Str::substr($r->jam_selesai,0,5) }}
                            @elseif ($r->tanggal_selesai->ne($r->tanggal_mulai))
                                {{-- Rentang dipadatkan: 22–23 Okt 2026, 28 Okt – 3 Nov 2026, atau lintas tahun lengkap. --}}
                                <i class="bi bi-calendar3 me-1 text-muted"></i>{{ $r->tanggal_mulai->translatedFormat($r->tanggal_mulai->year !== $r->tanggal_selesai->year ? 'd M Y' : ($r->tanggal_mulai->month !== $r->tanggal_selesai->month ? 'd M' : 'd')) }}{{ $r->tanggal_mulai->isSameMonth($r->tanggal_selesai) ? '–' : ' – ' }}{{ $r->tanggal_selesai->translatedFormat('d M Y') }}
                            @else
                                <i class="bi bi-calendar3 me-1 text-muted"></i>{{ $r->tanggal_mulai->translatedFormat('d M Y') }}
                            @endif
                        </td>
                        <td class="rv-peng text-center small text-nowrap">{{ $r->jumlah_pengguna }} orang</td>
                        <td class="rv-total text-end fw-semibold text-nowrap">Rp {{ number_format($r->total_biaya, 0, ',', '.') }}</td>
                        <td class="rv-status text-center"><span class="chip {{ strtolower($r->status_reservasi->value) }}">{{ $r->status_reservasi->value }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="xcard p-5 text-center">
        <div class="text-muted"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Tidak ada reservasi yang sesuai dengan filter.</div>
    </div>
@endforelse

@if ($grup->lastPage() > 1)
    @php
        $hal = $grup->currentPage(); $akhir = $grup->lastPage();
        $nomor = collect([1, $akhir, $hal - 1, $hal, $hal + 1])->filter(fn ($n) => $n >= 1 && $n <= $akhir)->unique()->sort()->values();
    @endphp
    <nav class="rv-pagination" aria-label="Halaman Data Reservasi" data-pagination>
        <a href="{{ $grup->previousPageUrl() ?? '#' }}" class="rv-page {{ $grup->onFirstPage() ? 'disabled' : '' }}" aria-label="Halaman sebelumnya" @if ($grup->onFirstPage()) aria-disabled="true" tabindex="-1" @endif><i class="bi bi-chevron-left"></i></a>
        @foreach ($nomor as $i => $n)
            @if ($i > 0 && $n - $nomor[$i - 1] > 1)<span class="rv-page-gap">…</span>@endif
            <a href="{{ $grup->url($n) }}" class="rv-page {{ $n === $hal ? 'active' : '' }}" @if ($n === $hal) aria-current="page" @endif>{{ $n }}</a>
        @endforeach
        <a href="{{ $grup->nextPageUrl() ?? '#' }}" class="rv-page {{ $grup->hasMorePages() ? '' : 'disabled' }}" aria-label="Halaman berikutnya" @unless ($grup->hasMorePages()) aria-disabled="true" tabindex="-1" @endunless><i class="bi bi-chevron-right"></i></a>
    </nav>
@endif
