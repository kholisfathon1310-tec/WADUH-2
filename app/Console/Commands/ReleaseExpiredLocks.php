<?php

namespace App\Console\Commands;

use App\Services\StatusOtomatisService;
use Illuminate\Console\Command;

class ReleaseExpiredLocks extends Command
{
    /**
     * @var string
     */
    protected $signature = 'reservasi:release-expired-locks';

    /**
     * @var string
     */
    protected $description = 'Ubah lock_status Reservasi yang masih temporary_hold dan sudah lewat lock_expires_at menjadi released';

    public function handle(StatusOtomatisService $layanan): int
    {
        $affected = $layanan->lepasLockKedaluwarsa();

        $this->info("Selesai. {$affected} reservasi temporary_hold yang expired diubah menjadi 'released'.");

        return self::SUCCESS;
    }
}
