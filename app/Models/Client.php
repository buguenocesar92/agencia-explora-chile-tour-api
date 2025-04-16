<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

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
