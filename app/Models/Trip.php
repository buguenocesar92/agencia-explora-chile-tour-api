<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $fillable = [
        'destination',
        'departure_date',
        'return_date',
        'service_type'
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
