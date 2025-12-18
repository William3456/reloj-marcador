<?php

namespace App\Models\Horario;

use App\Models\Empleado\Empleado;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

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
    ];

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
}
