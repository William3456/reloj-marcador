<?php

namespace App\Models\Permiso;

use App\Models\Empleado\Empleado;
use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    protected $table = 'permiso_trabajador';

    protected $fillable = [
        'id_empleado',
        'id_tipo_permiso',
        'motivo',
        'cantidad_mts',
        'dias_activa',
        'valor',
        'fecha_inicio',
        'fecha_fin',
        'estado',
    ];

        // Relación con empleado
    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado');
    }

    // Relación con tipo de permiso
    public function tipo()
    {
        return $this->belongsTo(TipoPermiso::class, 'id_tipo_permiso');
    }
}
