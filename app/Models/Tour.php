<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Esta clase es un alias para TourTemplate para mantener compatibilidad
 * con los tests existentes. Se recomienda usar TourTemplate directamente.
 *
 * @deprecated Use TourTemplate instead
 */
class Tour extends Model
{
    protected $table = 'tour_templates';

    protected $fillable = [
        'name',
        'destination',
        'description',
        'price',
        'capacity'
    ];

    public function trips()
    {
        return $this->hasMany(Trip::class, 'tour_template_id');
    }
}
