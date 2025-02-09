<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\ProcessVaultLinks;
use App\Models\Vault;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class UpgradeLinksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upgrade:links';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process links from existing vaults';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        /** @var object{total: int} $upgrade */
        $upgrade = DB::table('upgrades')->first();

        if ($upgrade->total >= 1) {
            return;
        }

        $vaults = Vault::all();
        foreach ($vaults as $vault) {
            new ProcessVaultLinks()->handle($vault);
        }

        DB::table('upgrades')->update([
            'total' => 1,
        ]);
    }
}
