<div class="xcard" data-reveal>
    <div class="xhead">
        <span><i class="bi bi-table me-1"></i>Laporan Data Reservasi Bulan {{ $judulBulan }}</span>
        @if ($rows->count() > 10)
            <span class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted small fw-normal" id="infoBaris">10 dari {{ $rows->count() }} data</span>
                <button type="button" class="btn btn-brand-outline btn-sm" id="btnSemua" onclick="toggleSemuaBaris()">
                    <i class="bi bi-chevron-double-down me-1"></i>Lihat Semua
                </button>
            </span>
        @else
            <span class="badge text-bg-light border">{{ $rows->count() }} data</span>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover mb-0 align-middle">
            <thead class="text-center">
                <tr>
                    <th>Nomor</th><th>Kode Reservasi</th><th class="text-start">Nama Pemesan</th>
                    <th class="text-start">Uraian</th><th>Volume (m²)</th><th>Periode Sewa</th>
                    <th>Keterangan</th><th>Total Harga (Rp)</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($rows as $row)
                <tr @if($loop->iteration > 10) class="baris-extra d-none" @endif>
                    <td class="text-center">{{ $row['no'] }}</td>
                    <td class="text-center">{{ $row['kode_reservasi'] }}</td>
                    <td>{{ $row['nama'] ?: '-' }}</td>
                    <td>{{ $row['uraian'] }}</td>
                    <td class="text-center">{{ number_format($row['volume'], 2, ',', '.') }}</td>
                    <td class="text-center">{{ $row['periode'] }}</td>
                    <td class="text-center"><span class="avail merah">{{ $row['keterangan'] }}</span></td>
                    <td class="text-end">Rp {{ number_format($row['total_harga'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted p-4">Belum ada reservasi disetujui pada {{ $judulBulan }}.</td></tr>
            @endforelse
            @if ($rows->isNotEmpty())
                <tr class="fw-bold">
                    <td colspan="7" class="text-end">TOTAL</td>
                    <td class="text-end">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</td>
                </tr>
            @endif
            </tbody>
        </table>
    </div>
</div>

{{-- Ekspor di bawah tabel, rata kanan --}}
<div class="d-flex justify-content-end mt-3">
    <a href="{{ route('admin.laporan.pdf', request()->query()) }}" class="btn btn-brand"
       data-confirm="Laporan PDF akan diunduh."
       data-confirm-title="Unduh laporan ini?" data-icon="warning" data-confirm-text="Ya, Unduh">
        <i class="bi bi-file-earmark-pdf me-1"></i>Unduh PDF
    </a>
</div>