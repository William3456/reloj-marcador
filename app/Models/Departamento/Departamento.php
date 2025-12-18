<?php

namespace App\Models\Departamento;

use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    protected $table = 'departamentos';

    protected $fillable = [
        'cod_depto',
        'nombre_depto',
        'estado',
        'sucursal_id',
    ];
}
