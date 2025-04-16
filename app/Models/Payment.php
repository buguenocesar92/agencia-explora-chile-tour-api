<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'receipt'
    ];

    // Agrega el accesor para que se incluya en el JSON
    protected $appends = ['receipt_url'];

    public function getReceiptUrlAttribute()
    {
        return $this->receipt
            ? Storage::disk('s3')->url($this->receipt)
            : null;
    }
}
