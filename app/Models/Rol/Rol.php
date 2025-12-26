<?php

namespace App\Models\Rol;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'roles';
    protected $fillable = [
        'rol_name',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'id_rol');
    }
}
