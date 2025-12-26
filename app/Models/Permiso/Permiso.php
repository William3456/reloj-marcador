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

    public function scopeVisiblePara($query, $user = null)
    {
        if (! $user) {
            return $query;
        }

        // Admin ve todo
        if ($user->rol->id == 1) {
            return $query;
        }

        // Encargado: solo permisos de empleados de su sucursal
        if ($user->rol->id == 2 && $user->empleado) {
            return $query->whereHas('empleado', function ($q) use ($user) {
                $q->where('id_sucursal', $user->empleado->id_sucursal);
            });
        }

        return $query;
    }
}
