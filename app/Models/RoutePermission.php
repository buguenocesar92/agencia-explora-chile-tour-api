<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;

class RoutePermission extends Model
{
    use HasFactory;

    protected $fillable = ['route_name', 'permission_name', 'permission_id'];

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
