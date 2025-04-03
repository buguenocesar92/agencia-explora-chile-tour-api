<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourTemplate extends Model
{
    protected $fillable = ['name', 'destination', 'description'];

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }
}
