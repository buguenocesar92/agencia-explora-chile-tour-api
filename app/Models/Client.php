<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name',
        'rut',
        'date_of_birth',
        'nationality',
        'email',
        'phone'
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
