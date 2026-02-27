<?php

namespace App\Models\HorarioEmpleado;

use App\Models\Empleado\Empleado;
use App\Models\Horario\horario;
use App\Models\Horario\HorarioHistorico;
use Illuminate\Database\Eloquent\Model;

class HorarioEmpleado extends Model
{
    protected $table = 'horarios_trabajadores';

    protected $fillable = [
        'id_empleado',
        'id_horario',
        'id_horario_historico',
        'fecha_inicio',
        'fecha_fin',
        'es_actual'
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado');
    }

    public function horario()
    {
        return $this->belongsTo(horario::class, 'id_horario');
    }
    public function historico()
    {
        return $this->belongsTo(HorarioHistorico::class, 'id_horario_historico');
    }
}
