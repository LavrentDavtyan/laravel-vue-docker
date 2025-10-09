<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShareTopic extends Model
{
    protected $fillable = ['owner_user_id', 'title', 'currency', 'invite_token', 'status'];

    public function owner(): BelongsTo {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function members(): HasMany {
        return $this->hasMany(ShareTopicMember::class, 'topic_id');
    }

    public function expenses(): HasMany {
        return $this->hasMany(ShareExpense::class, 'topic_id');
    }

    public function settlements(): HasMany {
        return $this->hasMany(ShareSettlement::class, 'topic_id');
    }

    public function scopeOpen($q)  { return $q->where('status', 'open'); }
    public function scopeClosed($q){ return $q->where('status', 'closed'); }
}
