<?php

namespace App\Models; // Ajusta si lo guardaste en otra carpeta como App\Models\Marcacion

use Illuminate\Database\Eloquent\Relations\Pivot;

class PermisoMarcacion extends Pivot
{
    // 1. Le indicamos explícitamente el nombre de la tabla
    protected $table = 'permisos_marcaciones';

}