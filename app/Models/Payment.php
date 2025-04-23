<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;

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
            ? url('storage_files/' . $this->receipt)
            : null;
    }
}
