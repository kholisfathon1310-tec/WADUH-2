<style>
    /* Tabel laporan — ringkas, satu garis pemisah horizontal, angka rata kanan & sejajar. */
    .lp-card { overflow:hidden; }
    .lp-head { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem 1.5rem; padding:1.15rem 1.35rem; border-bottom:1px solid var(--line); }
    .lp-head h2 { font-size:1rem; font-weight:800; margin:0; color:var(--ink); }
    .lp-head .sub { font-size:.8rem; color:var(--muted); margin-top:.15rem; }
    .lp-ringkas { display:flex; gap:.6rem; flex-wrap:wrap; }
    .lp-ringkas .item { padding:.45rem .9rem; border:1px solid var(--line); border-radius:.75rem; background:#fbfdfe; line-height:1.25; }
    .lp-ringkas .item small { display:block; font-size:.68rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--muted); }
    .lp-ringkas .item b { font-size:.95rem; color:var(--ink); font-variant-numeric:tabular-nums; }
    .lp-ringkas .item.utama { background:var(--primary-soft); border-color:transparent; }
    .lp-ringkas .item.utama b { color:var(--primary-dark, var(--primary)); }

    .lp-tabel { min-width:900px; }
    .lp-tabel thead th { padding:.75rem 1rem; font-size:.7rem; }
    .lp-tabel td { padding:.8rem 1rem; font-size:.86rem; line-height:1.4; }
    .lp-tabel tbody tr:nth-child(even) td { background:#fff; }
    .lp-tabel tbody tr:hover td { background:#f6fafb; }
    .lp-tabel .no { width:3.25rem; color:var(--muted); text-align:center; font-variant-numeric:tabular-nums; }
    .lp-tabel .angka { text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
    .lp-tabel .utama { font-weight:600; color:var(--ink); white-space:nowrap; }
    .lp-tabel .kecil { display:block; font-size:.76rem; color:var(--muted); white-space:nowrap; }
    .lp-kode { display:inline-block; padding:.2rem .5rem; border-radius:.45rem; background:#f1f5f9; color:var(--ink); font-weight:700; font-size:.8rem; letter-spacing:.04em; white-space:nowrap; }
    .lp-ket { display:inline-flex; align-items:center; gap:.35rem; padding:.2rem .6rem; border-radius:2rem; background:#e2f7ef; color:#0d7a55; font-size:.74rem; font-weight:700; white-space:nowrap; }
    .lp-ket::before { content:''; width:.4rem; height:.4rem; border-radius:50%; background:currentColor; }
    .lp-tabel tfoot td { padding:.95rem 1rem; background:#f8fafc; border-top:2px solid var(--line); border-bottom:0; font-weight:800; color:var(--ink); }
    .lp-tabel tfoot .label { text-align:right; font-size:.74rem; letter-spacing:.07em; text-transform:uppercase; color:var(--muted); }
    .lp-tabel tfoot .angka { font-size:.98rem; color:var(--primary-dark, var(--primary)); }
    .lp-kosong { padding:3rem 1rem !important; text-align:center; color:var(--muted); }
    .lp-kosong i { display:block; font-size:1.8rem; color:#cbd5e1; margin-bottom:.4rem; }

    .lp-foot { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.75rem; padding:.85rem 1.35rem; border-top:1px solid var(--line); background:#fbfdfe; }
    .lp-foot .info { font-size:.82rem; color:var(--muted); }
    .lp-foot .aksi { display:flex; gap:.5rem; flex-wrap:wrap; margin-left:auto; }
    @media (max-width: 575.98px) {
        .lp-head, .lp-foot { padding:1rem; }
        .lp-ringkas { width:100%; }
        .lp-ringkas .item { flex:1; }
        .lp-foot .aksi, .lp-foot .aksi .btn { width:100%; }
        /* Total sudah tampil di ringkasan atas; di tabel yang digeser baris ini terlihat kosong. */
        .lp-tabel tfoot { display:none; }
    }
</style>

<div class="xcard lp-card" data-reveal>
    <div class="lp-head">
        <div>
            <h2>Laporan Data Reservasi Bulan {{ $judulBulan }}</h2>
            <div class="sub">Reservasi berstatus Disetujui dan Selesai</div>
        </div>
        <div class="lp-ringkas">
            <div class="item"><small>Jumlah Data</small><b>{{ $rows->count() }}</b></div>
            <div class="item utama"><small>Total Pendapatan</small><b>Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</b></div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table lp-tabel mb-0 align-middle">
            <thead>
                <tr>
                    <th class="text-center">No.</th>
                    <th>Kode</th>
                    <th>Nama Pemesan</th>
                    <th>Uraian</th>
                    <th class="text-end">Volume</th>
                    <th>Periode Sewa</th>
                    <th class="text-center">Keterangan</th>
                    <th class="text-end">Total Biaya</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($rows as $row)
                <tr @if($loop->iteration > 10) class="baris-extra d-none" @endif>
                    <td class="no">{{ $row['no'] }}</td>
                    <td><span class="lp-kode">{{ $row['kode_reservasi'] }}</span></td>
                    <td class="utama">{{ $row['nama'] ?: '-' }}</td>
                    <td>
                        <span class="utama">{{ $row['fasilitas'] }}</span>
                        <span class="kecil">Lantai {{ $row['lantai'] }} · {{ $row['jenis_sewa'] }}</span>
                    </td>
                    <td class="angka">{{ number_format($row['volume'], 2, ',', '.') }} m²</td>
                    <td>
                        <span class="text-nowrap">{{ $row['tanggal'] }}</span>
                        @if ($row['waktu'])<span class="kecil">{{ $row['waktu'] }}</span>@endif
                    </td>
                    <td class="text-center"><span class="lp-ket">{{ $row['keterangan'] }}</span></td>
                    <td class="angka utama">Rp {{ number_format($row['total_harga'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="lp-kosong"><i class="bi bi-inbox"></i>Belum ada reservasi yang disetujui pada {{ $judulBulan }}.</td></tr>
            @endforelse
            </tbody>
            @if ($rows->isNotEmpty())
                <tfoot>
                    <tr>
                        <td colspan="7" class="label">Total Pendapatan</td>
                        <td class="angka">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    <div class="lp-foot">
        <span class="info" id="infoBaris">
            @if ($rows->count() > 10) Menampilkan 10 dari {{ $rows->count() }} data @else Menampilkan {{ $rows->count() }} data @endif
        </span>
        <div class="aksi">
            @if ($rows->count() > 10)
                <button type="button" class="btn btn-brand-outline btn-sm" id="btnSemua" onclick="toggleSemuaBaris()">
                    <i class="bi bi-chevron-double-down me-1"></i>Tampilkan Semua
                </button>
            @endif
            <a href="{{ route('admin.laporan.pdf', request()->query()) }}" class="btn btn-brand btn-sm"
               data-confirm="Laporan PDF akan diunduh."
               data-confirm-title="Unduh laporan ini?" data-icon="warning" data-confirm-text="Ya, unduh">
                <i class="bi bi-file-earmark-pdf me-1"></i>Unduh PDF
            </a>
        </div>
    </div>
</div>
