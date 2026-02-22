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
    protected $casts = [
        'dias' => 'array',
    ];
    
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
