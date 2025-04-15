<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'client_id',
        'trip_id',
        'payment_id',
        'date',
        'descripcion',
        'status'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
