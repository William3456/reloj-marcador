<?php

namespace App\Models\Horario;

use App\Models\Marcacion\MarcacionEmpleado;
use Illuminate\Database\Eloquent\Model;

class HorarioHistorico extends Model
{
    protected $table = 'horario_historico';

    protected $fillable = [
        'id_horario',
        'hora_entrada',
        'hora_salida',
        'tolerancia',
        'vigente_desde',
        'vigente_hasta',
        'dias',
        'tipo_horario',
    ];
    /* =========================
     * RELACIONES
     * ========================= */

    public function horario()
    {
        return $this->belongsTo(horario::class, 'id_horario');
    }

    public function marcaciones()
    {
        return $this->hasMany(MarcacionEmpleado::class, 'id_horario_historico');
    }

    /* =========================
     * SCOPES
     * ========================= */
    // Histórico vigente
    public function scopeVigente($query)
    {
        return $query->whereNull('vigente_hasta');
    }

    // Buscar versión idéntica al horario actual
    public function scopeMismoHorario($query, $horario)
    {
        $query
            ->where('id_horario', $horario->id)
            ->where('tipo_horario', $horario->permitido_marcacion)
            ->where('hora_entrada', $horario->hora_ini)
            ->where('hora_salida', $horario->hora_fin)
            ->where('tolerancia', $horario->tolerancia);
        
        foreach ($horario->dias as $dia) {
            $query->whereJsonContains('dias', $dia);
        }
        
        $query->whereRaw(
            'JSON_LENGTH(dias) = ?',
            [count($horario->dias)]
        );

        return $query;
    }
}
