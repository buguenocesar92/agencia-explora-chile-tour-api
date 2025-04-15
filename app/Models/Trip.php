<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'tour_template_id',
        'departure_date',
        'return_date',
/*         'price',
        'capacity' */
    ];

    public function tourTemplate()
    {
        return $this->belongsTo(TourTemplate::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
