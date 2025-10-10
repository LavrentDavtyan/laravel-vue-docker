<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShareJoinRequest extends Model
{
    protected $fillable = [
        'topic_id',
        'requester_user_id',
        'status',
        'invite_token',
        'message',
        'decided_by_user_id',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(ShareTopic::class, 'topic_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }
}
