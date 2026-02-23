<?php

namespace App\Models\Marcacion;

use App\Models\Empleado\Empleado;
use App\Models\Horario\horario;
use App\Models\Horario\HorarioHistorico;
use App\Models\Permiso\Permiso;
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
        'ubi_foto_full',
        'id_permiso_aplicado',
        'fuera_horario',
        'id_marcacion_entrada',
        'id_horario',
        'id_horario_historico_empleado',
        'id_horario_historico_sucursal',
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

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado');
    }

    public function permisos()
    {
        return $this->belongsToMany(
            Permiso::class,          // 1. El modelo final que queremos obtener
            'permisos_marcaciones',  // 2. El nombre de tu nueva tabla pivote
            'id_marcacion',          // 3. La columna que representa a la marcación en la pivote
            'id_permiso'             // 4. La columna que representa al permiso en la pivote
        )->withTimestamps();         // Agregamos esto porque tu tabla tiene created_at y updated_at
    }

    public function salida()
    {
        return $this->hasOne(self::class, 'id_marcacion_entrada');
    }

    public function scopeVisiblePara($query, $user)
    {
        if ($user->rol->id == 1) {
            return $query; // admin ve todo
        }

        if ($user->rol->id == 2) {

            return $query->where('id_sucursal', $user->empleado->id_sucursal);
        }

        return $query;
    }

    public function horario()
    {
        return $this->belongsTo(horario::class, 'id_horario');
    }

    public function horarioHistorico()
    {
        // Esta relación conecta la marcación con la "foto" del horario en ese momento
        return $this->belongsTo(HorarioHistorico::class, 'id_horario_historico_empleado');
    }
}
