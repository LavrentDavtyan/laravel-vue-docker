<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShareSettlement extends Model
{
    protected $fillable = [
        'topic_id', 'from_member_id', 'to_member_id', 'amount_decimal', 'note', 'settled_at',
    ];

    protected $casts = [
        'amount_decimal' => 'decimal:2',
        'settled_at' => 'datetime',
    ];

    public function topic(): BelongsTo {
        return $this->belongsTo(ShareTopic::class, 'topic_id');
    }

    public function fromMember(): BelongsTo {
        return $this->belongsTo(ShareTopicMember::class, 'from_member_id');
    }

    public function toMember(): BelongsTo {
        return $this->belongsTo(ShareTopicMember::class, 'to_member_id');
    }
}
