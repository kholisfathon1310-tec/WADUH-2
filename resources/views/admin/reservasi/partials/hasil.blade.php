<div class="d-flex align-items-center justify-content-between mb-3">
    <p class="text-muted small mb-0"><i class="bi bi-collection me-1"></i>{{ $grup->count() }} reservasi · {{ $grup->flatten()->count() }} ruangan</p>
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
            <table class="table mb-0 align-middle">
                <thead><tr>
                    <th>Ruangan</th><th>Jenis Sewa</th><th>Periode</th><th class="text-center">Pengguna</th><th class="text-end">Total Biaya</th><th class="text-center">Status</th>
                </tr></thead>
                <tbody>
                @foreach ($baris as $r)
                    <tr>
                        <td>
                            <span class="cell-main">{{ $r->tarifSewa->fasilitas->nama_fasilitas }}</span>
                            <span class="cell-sub d-block">{{ $r->tarifSewa->fasilitas->kategori_fasilitas }} · Lantai {{ $r->tarifSewa->fasilitas->lantai->nomor_lantai }}</span>
                        </td>
                        <td><span class="badge text-bg-light border">Per {{ $r->tarifSewa->jenisSewa->satuan->value }}</span></td>
                        <td class="small text-nowrap">
                            @if ($r->jam_mulai)
                                <i class="bi bi-calendar3 me-1 text-muted"></i>{{ $r->tanggal_mulai->translatedFormat('d M Y') }}
                                <span class="text-muted">·</span> {{ \Illuminate\Support\Str::substr($r->jam_mulai,0,5) }}–{{ \Illuminate\Support\Str::substr($r->jam_selesai,0,5) }} WIB
                            @elseif ($r->tanggal_selesai->ne($r->tanggal_mulai))
                                <i class="bi bi-calendar3 me-1 text-muted"></i>{{ $r->tanggal_mulai->translatedFormat('d M Y') }} – {{ $r->tanggal_selesai->translatedFormat('d M Y') }}
                            @else
                                <i class="bi bi-calendar3 me-1 text-muted"></i>{{ $r->tanggal_mulai->translatedFormat('d M Y') }}
                            @endif
                        </td>
                        <td class="text-center small text-nowrap">{{ $r->jumlah_pengguna }} orang</td>
                        <td class="text-end fw-semibold text-nowrap">Rp {{ number_format($r->total_biaya, 0, ',', '.') }}</td>
                        <td class="text-center"><span class="chip {{ strtolower($r->status_reservasi->value) }}">{{ $r->status_reservasi->value }}</span></td>
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
