<?php

namespace App\Models\Horario;

use App\Models\Empleado\Empleado;
use App\Models\Sucursales\Sucursal;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class horario extends Model
{
    protected $table = 'horarios';

    protected $fillable = [
        'hora_ini',
        'hora_fin',
        'permitido_marcacion',
        'estado',
        'tolerancia',
        'requiere_salida',
        'turno_txt',
        'turno',
        'sucursal_creacion',
        'dias',
    ];

    protected $casts = [
        'dias' => 'array',
    ];

    public function getHoraIniAttribute($value)
    {
        return Carbon::createFromFormat('H:i:s', $value)->format('H:i');
    }

    public function getHoraFinAttribute($value)
    {
        return Carbon::createFromFormat('H:i:s', $value)->format('H:i');
    }

    protected function tipoHorario(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->permitido_marcacion == 1 ? 'Sucursal' : 'Trabajador'
        );
    }

    protected function requiereSalidaTxt(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->requiere_salida == 1 ? 'Sí' : 'No'
        );
    }

    protected function toleranciaTxt(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->tolerancia == 0) {
                    return 'N/A';
                }

                // plural o singular
                return $this->tolerancia == 1
                    ? '1 min.'
                    : "{$this->tolerancia} mins.";
            }
        );
    }

    protected $appends = ['horas_laborales'];

    protected function horasLaborales(): Attribute
    {
        return Attribute::make(
            get: function () {

                // Convertir hora_ini y hora_fin a minutos
                [$h1, $m1] = explode(':', $this->hora_ini);
                [$h2, $m2] = explode(':', $this->hora_fin);

                $ini = $h1 * 60 + $m1;
                $fin = $h2 * 60 + $m2;

                // Si cruza medianoche
                $diff = $fin - $ini;
                if ($diff < 0) {
                    $diff += 1440; // sumar 24h
                }

                $horas = floor($diff / 60);
                $min = $diff % 60;

                return sprintf('%02dh %02dm', $horas, $min);
            }
        );
    }

    public function empleados()
    {
        return $this->belongsToMany(
            Empleado::class,
            'horarios_trabajadores',
            'id_horario',
            'id_empleado'
        );
    }

    public function scopeVisiblePara($query, $user)
    {
        if ($user->rol->id == 1) {
            return $query; // admin ve todo
        }

        if ($user->rol->id == 2) {

            return $query->where(function ($q) use ($user) {

                // Horarios creados en su sucursal
                $q->where('sucursal_creacion', $user->empleado->id_sucursal)

                  // O horarios asignados a su sucursal
                    ->orWhereHas('sucursales', function ($q2) use ($user) {
                        $q2->where('sucursales.id', $user->empleado->id_sucursal);
                    });

            });
        }

        return $query;
    }

    public function sucursales()
    {
        return $this->belongsToMany(
            Sucursal::class,          // Modelo destino
            'horarios_sucursales',   // Tabla pivote
            'id_horario',            // Clave foránea de este modelo (Horario) en la pivote
            'id_sucursal'            // Clave foránea del modelo destino (Sucursal) en la pivote
        );
    }
    public function historicos()
    {
        return $this->hasMany(
            HorarioHistorico::class,
            'id_horario',
            'id'
        );
    }
    /**
     * Codifica el valor dado a JSON.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function asJson($value)
    {
        // El flag JSON_UNESCAPED_UNICODE evita que PHP convierta la 'é' en '\u00e9'
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
