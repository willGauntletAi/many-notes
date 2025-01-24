<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\VaultObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy([VaultObserver::class])]
final class Vault extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'templates_node_id',
        'name',
    ];

    /**
     * Get the associated user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the nodes for the vault.
     */
    public function nodes(): HasMany
    {
        return $this->hasMany(VaultNode::class);
    }

    /**
     * Get the associated templates node.
     */
    public function templatesNode(): HasOne
    {
        return $this->hasOne(VaultNode::class, 'id', 'templates_node_id');
    }
}
