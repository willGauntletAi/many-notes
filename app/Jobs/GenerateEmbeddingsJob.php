<?php

namespace App\Jobs;

use App\Models\Vault;
use App\Models\JobStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateEmbeddingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The vault instance.
     *
     * @var \App\Models\Vault
     */
    protected $vault;

    /**
     * The job ID for tracking.
     *
     * @var string
     */
    public $jobStatusId;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600; // 1 hour timeout

    /**
     * Indicate if the job should be marked as failed on timeout.
     *
     * @var bool
     */
    public $failOnTimeout = true;

    /**
     * Create a new job instance.
     */
    public function __construct(Vault $vault)
    {
        $this->vault = $vault;
        $this->onQueue('embeddings'); // Use a dedicated queue for embedding jobs
        
        // Create a unique ID for this job
        $this->jobStatusId = (string) Str::uuid();
        
        // Create or update job status record
        JobStatus::updateOrCreate(
            ['vault_id' => $vault->id, 'type' => JobStatus::TYPE_EMBEDDING],
            [
                'job_id' => $this->jobStatusId,
                'status' => JobStatus::STATUS_PENDING,
                'message' => 'Job queued, waiting to start...',
                'progress' => 0
            ]
        );
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Update status to processing
            $this->updateStatus(JobStatus::STATUS_PROCESSING, 'Starting embedding generation...', 10);
            
            Log::info('Starting embedding generation job for vault: ' . $this->vault->id);
            
            // Run the Artisan command to generate embeddings
            $this->updateStatus(JobStatus::STATUS_PROCESSING, 'Running embedding generation command...', 20);
            
            $exitCode = Artisan::call('vault:generate-embeddings', [
                'vault_id' => $this->vault->id
            ]);
            
            if ($exitCode === 0) {
                $this->updateStatus(
                    JobStatus::STATUS_COMPLETED, 
                    'Embedding generation completed successfully!', 
                    100
                );
                
                Log::info('Embedding generation completed successfully for vault: ' . $this->vault->id);
            } else {
                $this->updateStatus(
                    JobStatus::STATUS_FAILED, 
                    'Embedding generation failed with exit code: ' . $exitCode, 
                    100
                );
                
                Log::error('Embedding generation failed for vault: ' . $this->vault->id . ' with exit code: ' . $exitCode);
                throw new \Exception('Embedding generation command failed with exit code: ' . $exitCode);
            }
        } catch (\Exception $e) {
            $this->updateStatus(
                JobStatus::STATUS_FAILED, 
                'Error during embedding generation: ' . $e->getMessage(), 
                100
            );
            
            Log::error('Error during embedding generation: ' . $e->getMessage());
            $this->fail($e);
            throw $e; // Re-throw to mark job as failed
        }
    }
    
    /**
     * Update job status in the database
     */
    protected function updateStatus(string $status, string $message, int $progress): void
    {
        JobStatus::where('job_id', $this->jobStatusId)
            ->update([
                'status' => $status,
                'message' => $message,
                'progress' => $progress,
                'updated_at' => now()
            ]);
    }
}
