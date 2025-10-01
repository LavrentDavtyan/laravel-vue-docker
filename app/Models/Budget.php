<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $fillable = [
        'user_id', 'category', 'month', 'amount_decimal', 'currency'
    ];

    protected $casts = [
        'month' => 'date:Y-m-d',
        'amount_decimal' => 'decimal:2',
    ];
}
