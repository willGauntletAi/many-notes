<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class UpgradeInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upgrade:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prepare database for upgrades';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (DB::table('upgrades')->count()) {
            return;
        }

        DB::table('upgrades')->insert([
            'total' => 0,
        ]);
    }
}
