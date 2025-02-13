<?php

declare(strict_types=1);

namespace App\Console\Commands\Upgrade;

use App\Actions\ProcessVaultLinks;
use App\Models\Vault;
use Illuminate\Console\Command;
use Throwable;

final class ProcessLinksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upgrade:process-links';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process links from existing vaults';

    /**
     * Execute the console command.
     */
    public function handle(ProcessVaultLinks $processVaultLinks): int
    {
        try {
            $vaults = Vault::all();

            foreach ($vaults as $vault) {
                $processVaultLinks->handle($vault);
            }
        } catch (Throwable) {
            $this->error('Something went wrong processing links from existing vaults');

            return self::FAILURE;
        }

        $this->info('All links were successfully processed');

        return self::SUCCESS;
    }
}
