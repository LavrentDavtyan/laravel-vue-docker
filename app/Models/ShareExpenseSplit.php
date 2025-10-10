<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShareExpenseSplit extends Model
{
    use HasFactory;

    /**
     * Columns allowed for mass assignment.
     */
    protected $fillable = [
        'expense_id',
        'member_id',
        'share_amount',
    ];

    /**
     * Ensure precise numeric handling for share amounts.
     */
    protected $casts = [
        'share_amount' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(ShareExpense::class, 'expense_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(ShareTopicMember::class, 'member_id');
    }
}
