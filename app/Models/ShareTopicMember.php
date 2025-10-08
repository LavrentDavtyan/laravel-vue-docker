<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShareTopicMember extends Model
{
   protected $fillable = ['topic_id', 'user_id', 'display_name', 'role', 'joined_at'];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    public function topic(): BelongsTo {
        return $this->belongsTo(ShareTopic::class, 'topic_id');
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function expensesPaid(): HasMany {
        return $this->hasMany(ShareExpense::class, 'payer_member_id');
    }

    public function splits(): HasMany {
        return $this->hasMany(ShareExpenseSplit::class, 'member_id');
    }

    public function scopeOwner($q) { return $q->where('role', 'owner'); }
}
