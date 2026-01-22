<?php

namespace App\Models\Marcacion;

use App\Models\Sucursales\Sucursal;
use Illuminate\Database\Eloquent\Model;

class MarcacionEmpleado extends Model
{
    protected $table = 'marcaciones_empleados';

    protected $fillable = [
        'id_empleado',
        'id_sucursal',
        'latitud',
        'longitud',
        'distancia_real_mts',
        'ubicacion',
        'tipo_marcacion',
        'ubi_foto',
        'id_permiso_aplicado',
        'fuera_horario',
        'id_marcacion_entrada',
    ];

    public function sucursal()
    {
        return $this->belongsTo(
            Sucursal::class,
            'id_sucursal',
            'id'
        );
    }
    public function entrada()
    {
        return $this->belongsTo(self::class, 'id_marcacion_entrada');
    }

    public function salida()
    {
        return $this->hasOne(self::class, 'id_marcacion_entrada');
    }
}
