<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tour_template_id',
        'departure_date',
        'return_date',
/*         'price',
        'capacity' */
    ];

    public function tourTemplate()
    {
        return $this->belongsTo(TourTemplate::class)->withTrashed();
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
