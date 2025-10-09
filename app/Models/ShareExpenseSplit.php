<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShareExpenseSplit extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_id',
        'member_id',
        'share_amount',
    ];

    public function expense() {
        return $this->belongsTo(ShareExpense::class, 'expense_id');
    }

    public function member() {
        return $this->belongsTo(ShareTopicMember::class, 'member_id');
    }
}
