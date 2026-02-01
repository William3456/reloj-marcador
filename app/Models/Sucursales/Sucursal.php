<?php

namespace App\Models\Sucursales;

use App\Models\Empleado\Empleado;
use App\Models\Empresa\Empresa;
use App\Models\Horario\horario;
use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    protected $table = 'sucursales';

    protected $fillable = [
        'nombre',
        'direccion',
        'correo_encargado',
        'id_empresa',
        'cant_empleados',
        'rango_marcacion_mts',
        'dias_laborales',
        'estado',
        'latitud',
        'longitud',
        'telefono',
        'margen_error_gps_mts',
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
    public function horarios()
    {
        return $this->belongsToMany(
            horario::class,           // Modelo destino
            'horarios_sucursales',    // Tabla pivote (intermedia)
            'id_sucursal',            // Clave foránea en la pivote para este modelo
            'id_horario'              // Clave foránea en la pivote para el otro modelo
        );
    }
}
