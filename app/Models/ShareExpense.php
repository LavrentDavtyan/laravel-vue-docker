<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShareExpense extends Model
{
    use HasFactory;

    /**
     * Allow mass-assignment for all columns we write,
     * including legacy columns present in your table.
     */
    protected $fillable = [
        'topic_id',
        'payer_user_id',
        'description',
        'amount',
        'currency',

        // legacy / existing columns in your DB
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

    public function splits()
    {
        return $this->hasMany(ShareExpenseSplit::class, 'expense_id');
    }

    public function topic()
    {
        return $this->belongsTo(ShareTopic::class, 'topic_id');
    }
}
