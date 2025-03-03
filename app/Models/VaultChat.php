<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VaultChat extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'vault_id',
        'name',
    ];

    /**
     * Get the vault that owns the chat.
     *
     * @return BelongsTo<Vault, VaultChat>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the messages for the chat.
     *
     * @return HasMany<ChatMessage, VaultChat>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }
}
