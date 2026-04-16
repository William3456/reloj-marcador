<?php

namespace App\Models\Empleado;

use Illuminate\Database\Eloquent\Model;

class HomeOffice extends Model
{
    protected $table = 'trabajo_remoto_empleado';
    
    protected $fillable = [
        'id_empleado',
        'dias',
        'estado',
        'fecha_inicio', 
        'fecha_fin',    
        'es_actual',    
    ];

    
    protected $casts = [
        'dias' => 'array',
        'es_actual' => 'boolean',
    ];
}
