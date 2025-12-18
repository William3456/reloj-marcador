<?php

namespace App\Models\Puesto;

use Illuminate\Database\Eloquent\Model;

class Puesto extends Model
{
    protected $table = 'puestos_trabajos';

    protected $fillable = [
        'cod_puesto',
        'desc_puesto',
        'estado',
        'sucursal_id',
    ];
}
