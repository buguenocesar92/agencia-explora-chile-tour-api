<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Payment extends Model
{
    protected $fillable = [
        'receipt'
    ];

    // Agrega el accesor para que se incluya en el JSON
    protected $appends = ['receipt_url'];

    public function getReceiptUrlAttribute()
    {
        return $this->receipt
            ? asset('storage/' . $this->receipt)
            : null;
    }
}
