<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transactions extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'transaction_id',
        'user_id',
        'transaction_amount',
        'transaction_status',
        'transaction_type',
        'action_reason',
        'transaction_timestamp'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
