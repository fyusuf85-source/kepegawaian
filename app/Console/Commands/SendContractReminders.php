<?php

namespace App\Console\Commands;

use App\Services\ContractService;
use Illuminate\Console\Command;

class SendContractReminders extends Command
{
    protected $signature = 'contracts:remind {--date= : Tanggal acuan (Y-m-d), default hari ini}';

    protected $description = 'Kirim pengingat kontrak H-30/H-14/H-7 dan perbarui status kontrak';

    public function handle(ContractService $contracts): int
    {
        $at = $this->option('date')
            ? \Illuminate\Support\Carbon::parse($this->option('date'), 'Asia/Jakarta')
            : now('Asia/Jakarta');

        $stats = $contracts->sendReminders($at);

        $this->info("Pengingat terkirim: {$stats['reminded']}; kontrak akan berakhir: {$stats['expiring']}; kontrak berakhir: {$stats['ended']}.");

        return self::SUCCESS;
    }
}
