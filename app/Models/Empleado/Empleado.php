<?php

namespace App\Models\Empleado;

use App\Models\Departamento\Departamento;
use App\Models\Empresa\Empresa;
use App\Models\Horario\horario;
use App\Models\Permiso\Permiso;
use App\Models\Puesto\Puesto;
use App\Models\Sucursales\Sucursal;
use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $fillable = [
        'cod_trabajador',
        'correo',
        'direccion',
        'edad',
        'documento',
        'nombres',
        'apellidos',
        'id_puesto',
        'id_depto',
        'id_sucursal',
        'id_empresa',
        'login',
        'estado',
        'creado_por_usuario',
    ];

    public function puesto()
    {
        return $this->belongsTo(Puesto::class, 'id_puesto');
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'id_depto');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'id_sucursal');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function horarios()
    {
        return $this->belongsToMany(
            horario::class,
            'horarios_trabajadores',
            'id_empleado',
            'id_horario'
        );
    }

    public function permisos()
    {
        return $this->hasMany(Permiso::class, 'id_empleado');
    }
}
