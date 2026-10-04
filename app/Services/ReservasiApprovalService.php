<?php

namespace App\Services;

use App\Enums\SatuanSewa;
use App\Enums\StatusReservasi;
use App\Enums\StatusVerifikasi;
use App\Models\Reservasi;

/**
 * Checklist validasi sebelum admin boleh menyetujui satu Reservasi (Stage 3).
 * Dipakai bersama oleh halaman detail (tampilan checklist) dan aksi Setujui (backend).
 */
class ReservasiApprovalService
{
    /**
     * Jenis sewa yang boleh untuk tiap kategori. Kategori tak terdaftar → semua boleh.
     * (Nilai string literal — PHP 8.1 tak mengizinkan Enum->value di ekspresi konstan.)
     */
    private const SATUAN_KATEGORI = [
        'Convention Hall'  => ['Hari'],
        'Working Space'    => ['Jam', 'Hari', 'Bulan'],
        'Co-Working Space' => ['Jam', 'Hari', 'Bulan'],
    ];

    public function __construct(private readonly AvailabilityService $availability)
    {
    }

    /**
     * Item checklist menyesuaikan jenis sewa — syarat durasi & dokumen Bulan cuma relevan
     * (dan cuma ditampilkan) untuk reservasi Bulan, supaya reservasi Jam/Hari tidak melihat
     * baris "tidak berlaku" yang percuma.
     *
     * @return array<int, array{label: string, passed: bool, note: string}>
     */
    public function checklist(Reservasi $reservasi): array
    {
        $reservasi->loadMissing('tarifSewa.fasilitas', 'tarifSewa.jenisSewa', 'dokumenPersyaratan');
        $fasilitas = $reservasi->tarifSewa->fasilitas;
        $jenis = $reservasi->tarifSewa->jenisSewa;
        $satuan = $jenis->satuan;

        $checklist = [
            $this->cekBentrok($reservasi, $fasilitas, $satuan),
            $this->cekJenisKategori($fasilitas->kategori_fasilitas, $satuan),
        ];

        if ($satuan === SatuanSewa::Bulan) {
            $checklist[] = $this->cekBulan($reservasi, $jenis);
        }

        $checklist[] = $this->cekKapasitas($reservasi, $fasilitas);

        return $checklist;
    }

    public function passes(Reservasi $reservasi): bool
    {
        foreach ($this->checklist($reservasi) as $item) {
            if (! $item['passed']) {
                return false;
            }
        }

        return true;
    }

    private function cekBentrok(Reservasi $reservasi, $fasilitas, SatuanSewa $satuan): array
    {
        $slot = [
            'tanggal_mulai'   => $reservasi->tanggal_mulai->toDateString(),
            'tanggal_selesai' => $reservasi->tanggal_selesai->toDateString(),
            'jam_mulai'       => $reservasi->jam_mulai,
            'jam_selesai'     => $reservasi->jam_selesai,
        ];

        // Pembanding hanya reservasi yang SUDAH DISETUJUI. Dua pengajuan Menunggu untuk jadwal
        // yang sama tidak boleh saling mengunci: yang pertama tetap bisa disetujui, dan begitu
        // disetujui, pengajuan lainnya otomatis gagal pada butir ini.
        $bentrok = $this->availability->hasReservationConflict(
            $fasilitas->id_fasilitas, $slot, $reservasi->id_reservasi, [StatusReservasi::Disetujui->value],
        );

        $lingkup = match ($satuan) {
            SatuanSewa::Jam => 'jam',
            SatuanSewa::Hari => 'tanggal',
            SatuanSewa::Bulan => 'periode',
        };

        return [
            'label'  => 'Tidak bentrok jadwal',
            'passed' => ! $bentrok,
            'note'   => $bentrok
                ? "Sudah ada reservasi lain yang disetujui pada {$lingkup} yang sama."
                : "Tidak ada reservasi lain yang disetujui pada {$lingkup} yang sama.",
        ];
    }

    private function cekJenisKategori(string $kategori, SatuanSewa $satuan): array
    {
        $allowed = self::SATUAN_KATEGORI[$kategori] ?? [SatuanSewa::Jam->value, SatuanSewa::Hari->value, SatuanSewa::Bulan->value];
        $ok = in_array($satuan->value, $allowed, true);

        return [
            'label'  => 'Jenis sewa sesuai kategori',
            'passed' => $ok,
            'note'   => $ok ? "Sewa {$satuan->value} diperbolehkan untuk {$kategori}." : "Kategori {$kategori} tidak menerima sewa {$satuan->value}.",
        ];
    }

    /** Hanya dipanggil untuk reservasi Bulan — lihat gating di checklist(). */
    private function cekBulan(Reservasi $reservasi, $jenis): array
    {
        $min = (int) $jenis->durasi_minimum;
        $durasiOk = $reservasi->durasi >= $min;

        $dokumen = $reservasi->dokumenPersyaratan;
        $adaDokumen = $dokumen->isNotEmpty();
        $semuaValid = $adaDokumen && $dokumen->every(fn ($d) => $d->status_verifikasi === StatusVerifikasi::Valid);

        $passed = $durasiOk && $semuaValid;
        $note = match (true) {
            ! $durasiOk   => "Durasi {$reservasi->durasi} bulan < minimum {$min} bulan.",
            ! $adaDokumen => 'Belum ada dokumen persyaratan.',
            ! $semuaValid => 'Masih ada dokumen yang belum berstatus Valid.',
            default       => "Durasi ≥ {$min} bulan dan semua dokumen Valid.",
        };

        return ['label' => 'Durasi & dokumen sewa Bulan', 'passed' => $passed, 'note' => $note];
    }

    private function cekKapasitas(Reservasi $reservasi, $fasilitas): array
    {
        $ok = $reservasi->jumlah_pengguna <= $fasilitas->kapasitas;

        return [
            'label'  => 'Kapasitas ruangan',
            'passed' => $ok,
            'note'   => "{$reservasi->jumlah_pengguna} dari kapasitas {$fasilitas->kapasitas} orang.",
        ];
    }
}
