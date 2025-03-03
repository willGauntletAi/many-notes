<?php

namespace App\Console\Commands;

use App\Services\PostgresVecService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearEmbeddings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vault:clear-embeddings {vault_id? : The ID of the vault to clear embeddings for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear vector embeddings for vault nodes';

    /**
     * Execute the console command.
     */
    public function handle(PostgresVecService $vecService)
    {
        $vaultId = $this->argument('vault_id');
        
        if ($this->confirm('Are you sure you want to clear embeddings' . ($vaultId ? " for vault ID {$vaultId}" : 'for all vaults') . '?')) {
            try {
                if ($vaultId) {
                    // Clear embeddings for a specific vault
                    DB::table('embeddings')
                        ->where('type', 'node')
                        ->where('note_id', function ($query) use ($vaultId) {
                            $query->select('id')
                                ->from('vault_nodes')
                                ->where('vault_id', $vaultId);
                        })
                        ->delete();
                    
                    $this->info("Embeddings cleared for vault ID {$vaultId}");
                } else {
                    // Clear all embeddings
                    DB::table('embeddings')->truncate();
                    $this->info('All embeddings cleared');
                }
                
                return 0;
            } catch (\Exception $e) {
                $this->error('Error clearing embeddings: ' . $e->getMessage());
                return 1;
            }
        }
        
        $this->info('Operation cancelled');
        return 0;
    }
}
