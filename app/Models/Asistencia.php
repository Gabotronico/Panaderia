<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $fillable = [
        'empleado_id', 'fecha', 'estado', 'hora_entrada', 'hora_salida',
        'minutos_tardanza', 'horas_extra', 'observaciones', 'user_id',
    ];

    protected $casts = [
        'fecha'       => 'date',
        'horas_extra' => 'decimal:2',
    ];

    /** Estados que cuentan como día trabajado (total o parcial). */
    public const ESTADOS_TRABAJADOS = ['presente', 'tardanza', 'medio_dia'];

    public const ETIQUETAS = [
        'presente'  => 'Presente',
        'ausente'   => 'Ausente',
        'tardanza'  => 'Tardanza',
        'medio_dia' => 'Medio día',
        'feriado'   => 'Feriado',
        'licencia'  => 'Licencia',
    ];

    public const COLORES = [
        'presente'  => 'success',
        'ausente'   => 'danger',
        'tardanza'  => 'warning',
        'medio_dia' => 'info',
        'feriado'   => 'secondary',
        'licencia'  => 'primary',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getEtiquetaEstadoAttribute(): string
    {
        return self::ETIQUETAS[$this->estado] ?? $this->estado;
    }

    public function getColorEstadoAttribute(): string
    {
        return self::COLORES[$this->estado] ?? 'secondary';
    }

    /**
     * Horas efectivamente trabajadas según marcaje de entrada y salida.
     * Devuelve null si falta alguno de los dos registros.
     */
    public function getHorasTrabajadasAttribute(): ?float
    {
        if (!$this->hora_entrada || !$this->hora_salida) {
            return null;
        }

        $entrada = Carbon::parse($this->hora_entrada);
        $salida  = Carbon::parse($this->hora_salida);

        // Turno que cruza medianoche (ej. panadería: entra 22:00, sale 06:00)
        if ($salida->lessThan($entrada)) {
            $salida->addDay();
        }

        return round($entrada->diffInMinutes($salida) / 60, 2);
    }

    /** Diferencia contra la jornada estándar: positiva = de más, negativa = de menos. */
    public function getDiferenciaJornadaAttribute(): ?float
    {
        $horas = $this->horas_trabajadas;

        return $horas === null ? null : round($horas - config('nomina.horas_jornada'), 2);
    }

    /** El domingo no se contabiliza para planilla. */
    public function getEsDomingoAttribute(): bool
    {
        return $this->fecha && $this->fecha->dayOfWeek === Carbon::SUNDAY;
    }

    /** Solo días laborables (excluye domingos). */
    public function scopeLaborables($query)
    {
        return $query->whereRaw('DAYOFWEEK(fecha) != 1');
    }

    public function scopeEnPeriodo($query, $inicio, $fin)
    {
        return $query->whereBetween('fecha', [$inicio, $fin]);
    }
}
