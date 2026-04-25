<?php

namespace App\Models\Empleado;

use Illuminate\Database\Eloquent\Model;

class HomeOffice extends Model
{
    protected $table = 'trabajo_remoto_empleado';
    
    protected $fillable = [
        'id_empleado',
        'dias',
        'estado',
        'fecha_inicio', 
        'fecha_fin',    
        'es_actual',    
    ];

    
    protected $casts = [
        'dias' => 'array',
        'es_actual' => 'boolean',
    ];
    
    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
    public function getDiasAttribute($value)
    {
        // 1. Decodificamos el JSON que viene de la base de datos
        $diasArray = is_string($value) ? json_decode($value, true) : $value;

        if (!is_array($diasArray)) {
            return [];
        }

        // 2. Definir el peso de cada día de la semana
        $orden = [
            'lunes'     => 1,
            'martes'    => 2,
            'miércoles' => 3,
            'miercoles' => 3, 
            'jueves'    => 4,
            'viernes'   => 5,
            'sábado'    => 6,
            'sabado'    => 6,
            'domingo'   => 7
        ];

        // 3. Ordenamos el arreglo usando usort y los pesos definidos
        usort($diasArray, function ($a, $b) use ($orden) {
            $pesoA = $orden[mb_strtolower($a, 'UTF-8')] ?? 99;
            $pesoB = $orden[mb_strtolower($b, 'UTF-8')] ?? 99;
            
            return $pesoA <=> $pesoB; // Compara y ordena de menor a mayor
        });

        return $diasArray;
    }
}
