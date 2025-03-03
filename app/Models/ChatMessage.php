<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'vault_chat_id',
        'role',
        'content',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'role' => 'string',
    ];

    /**
     * Get the chat that owns the message.
     *
     * @return BelongsTo<VaultChat, ChatMessage>
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(VaultChat::class, 'vault_chat_id');
    }
}
