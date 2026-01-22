<?php

namespace App\Models\Permiso;

use Illuminate\Database\Eloquent\Model;

class TipoPermiso extends Model
{
    protected $table = 'tipos_permiso';

    protected $fillable = [
        'codigo',
        'nombre',
        'requiere_distancia',
        'requiere_fechas',
        'requiere_dias',
        'estado',
    ];
    public function permisos()
    {
        return $this->hasMany(
            Permiso::class,
            'id_tipo_permiso',
            'id'
        );
    }
}
