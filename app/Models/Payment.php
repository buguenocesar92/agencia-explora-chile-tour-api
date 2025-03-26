<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'amount',
        'payment_date',
        'transaction_id',
        'receipt'
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
