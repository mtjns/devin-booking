<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessedTransaction extends Model
{
    protected $fillable = [
        'transaction_id',
        'booking_id',
        'variable_symbol',
        'amount',
        'status',
        'notes',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
