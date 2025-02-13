<?php

declare(strict_types=1);

namespace App\Console\Commands\Upgrade;

use App\Actions\GetPathFromVaultNode;
use App\Models\Vault;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class SyncDatabaseNotesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upgrade:sync-database-notes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync database notes to disk';

    /**
     * Execute the console command.
     */
    public function handle(GetPathFromVaultNode $getPathFromVaultNode): int
    {
        try {
            $vaults = Vault::all();

            foreach ($vaults as $vault) {
                $nodes = $vault->nodes()
                    ->where('is_file', true)
                    ->where('extension', 'md')
                    ->get();

                foreach ($nodes as $node) {
                    $path = $getPathFromVaultNode->handle($node);
                    Storage::disk('local')->put($path, $node->content ?? '');
                }
            }
        } catch (Throwable) {
            $this->error('Something went wrong syncing database notes to disk');

            return self::FAILURE;
        }

        $this->info('All notes were successfully synced to disk');

        return self::SUCCESS;
    }
}
