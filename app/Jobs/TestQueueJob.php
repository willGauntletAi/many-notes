<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TestQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        // Use a specific queue for this test
        $this->onQueue('test-queue');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Wait 5 seconds to simulate work
        sleep(5);
        
        // Log a message
        Log::info('Test queue job processed successfully at ' . now()->toDateTimeString());
    }
}
