<?php

namespace App\Models\Turnos;

use Illuminate\Database\Eloquent\Model;

class Turnos extends Model
{
    protected $table = 'turnos';

    protected $fillable = [
        'nombre_turno',
        'hora_ini',
        'hora_fin',
    ];
}
