<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobStatus extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'job_id',
        'type',
        'vault_id',
        'status',
        'message',
        'progress',
    ];
    
    /**
     * Get the vault that owns the job status.
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }
    
    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    
    /**
     * Job type constants
     */
    const TYPE_EMBEDDING = 'embedding';
}
