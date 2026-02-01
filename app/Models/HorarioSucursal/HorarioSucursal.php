<?php

namespace App\Models\HorarioSucursal;

use App\Models\Horario\horario;
use App\Models\Sucursales\Sucursal;
use Illuminate\Database\Eloquent\Model;

class HorarioSucursal extends Model
{
    protected $table = 'horarios_sucursales';

    protected $fillable = [
        'id_sucursal',
        'id_horario',
    ];
    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'id_sucursal');
    }

    public function horario()
    {
        return $this->belongsTo(horario::class, 'id_horario');
    }
}
