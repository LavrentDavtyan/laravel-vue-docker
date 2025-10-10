<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShareExpense extends Model
{
    use HasFactory;

    /**
     * Columns we write via mass-assignment.
     */
    protected $fillable = [
        'topic_id',
        'payer_user_id',
        'description',
        'amount',
        'currency',

        // legacy / existing columns (kept for compatibility)
        'date',
        'notes',
        'amount_decimal',
    ];

    /**
     * Helpful casting so you get proper types back.
     */
    protected $casts = [
        'amount'         => 'decimal:2',
        'amount_decimal' => 'decimal:2',
        'date'           => 'date',
    ];

    /**
     * Keep legacy column out of serialized responses.
     */
    protected $hidden = [
        'amount_decimal',
    ];

    /**
     * Relationships
     */
    public function splits(): HasMany
    {
        return $this->hasMany(ShareExpenseSplit::class, 'expense_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(ShareTopic::class, 'topic_id');
    }

    /**
     * The user who paid this expense.
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_user_id');
    }
}
