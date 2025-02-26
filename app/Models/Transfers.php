<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transfers extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'receiver_id',
        'transfer_amount',
        'account_from',
        'account_to',
        'transfer_status',
        'transfer_timestamp'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
