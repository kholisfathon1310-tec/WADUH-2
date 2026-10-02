<?php

namespace App\Console\Commands;

use App\Services\StatusOtomatisService;
use Illuminate\Console\Command;

class PerbaruiStatusReservasiOtomatis extends Command
{
    /**
     * @var string
     */
    protected $signature = 'reservasi:perbarui-status-otomatis';

    /**
     * @var string
     */
    protected $description = 'Ubah status Reservasi otomatis berdasarkan waktu: Disetujui -> Selesai setelah masa penggunaan berakhir, dan Menunggu (belum diproses admin) -> Kadaluwarsa setelah batas persetujuan terlewati (beda per jenis sewa, dievaluasi per ruangan/baris)';

    public function handle(StatusOtomatisService $layanan): int
    {
        $hasil = $layanan->jalankan();

        if ($hasil === null) {
            $this->warn('Proses pembaruan status lain sedang berjalan. Dilewati.');

            return self::SUCCESS;
        }

        $this->info("Selesai. {$hasil['selesai']} reservasi diubah menjadi 'Selesai', {$hasil['kadaluwarsa']} reservasi diubah menjadi 'Kadaluwarsa'.");

        return self::SUCCESS;
    }
}
