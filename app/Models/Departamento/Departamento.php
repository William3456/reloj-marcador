<?php

namespace App\Models\Departamento;

use App\Models\Sucursales\Sucursal;
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

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
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

        // Encargado: solo departamentos de su sucursal
        if ($user->rol->id == 2 && $user->empleado) {
            return $query->where('sucursal_id', $user->empleado->id_sucursal);
        }

        return $query;
    }
}
