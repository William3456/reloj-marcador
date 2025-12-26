<?php

namespace App\Models\Sucursales;

use App\Models\Empleado\Empleado;
use App\Models\Empresa\Empresa;
use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    protected $table = 'sucursales';

    protected $fillable = [
        'nombre',
        'direccion',
        'correo_encargado',
        'id_empresa',
        'id_horario',
        'cant_empleados',
        'rango_marcacion_mts',
        'dias_laborales',
        'estado',
        'latitud',
        'longitud',
    ];

    protected $casts = [
        'dias_laborales' => 'array',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function empleados()
    {
        return $this->hasMany(Empleado::class, 'id_sucursal');
    }

    public function scopeVisiblePara($query, $user)
    {
        if ($user->rol->id == 1) {
            return $query; // admin ve todo
        }

        if ($user->rol->id == 2) {

            return $query->where('id', $user->empleado->id_sucursal);
        }

        return $query;
    }
}
