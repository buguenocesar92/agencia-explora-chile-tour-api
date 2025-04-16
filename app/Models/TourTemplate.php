<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TourTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'destination', 'description'];

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }
}
