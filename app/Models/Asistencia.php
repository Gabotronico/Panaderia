<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $fillable = [
        'empleado_id', 'fecha', 'estado', 'hora_entrada', 'hora_salida',
        'minutos_tardanza', 'observaciones', 'user_id',
    ];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
    ];

    /** Estados que cuentan como día trabajado (total o parcial). */
    public const ESTADOS_TRABAJADOS = ['presente', 'tardanza', 'medio_dia'];

    public const ETIQUETAS = [
        'presente' => 'Presente',
        'ausente' => 'Ausente',
        'tardanza' => 'Tardanza',
        'medio_dia' => 'Medio día',
        'feriado' => 'Feriado',
        'licencia' => 'Licencia',
    ];

    public const COLORES = [
        'presente' => 'success',
        'ausente' => 'danger',
        'tardanza' => 'warning',
        'medio_dia' => 'info',
        'feriado' => 'secondary',
        'licencia' => 'primary',
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
        $minutos = $this->minutosTrabajados();

        return $minutos === null ? null : round($minutos / 60, 2);
    }

    /** Minutos entre la entrada y la salida marcadas; null si falta alguna. */
    public function minutosTrabajados(): ?int
    {
        if (! $this->hora_entrada || ! $this->hora_salida) {
            return null;
        }

        $entrada = Carbon::parse($this->hora_entrada);
        $salida = Carbon::parse($this->hora_salida);

        // Turno que cruza medianoche (ej. panadería: entra 22:00, sale 06:00)
        if ($salida->lessThan($entrada)) {
            $salida->addDay();
        }

        return (int) $entrada->diffInMinutes($salida);
    }

    /**
     * Qué parte del día se le paga al empleado (0 a 1).
     *
     * Con entrada y salida marcadas manda el reloj: si cumplió su jornada se
     * paga el día completo; si trabajó menos, solo las horas trabajadas.
     * La tolerancia de entrada no se descuenta, igual que no cuenta como
     * tardanza: con 10 minutos de tolerancia, llegar 5 tarde sigue siendo un
     * día completo y llegar 20 tarde descuenta 10. Perdona solo el atraso al
     * llegar, no el irse antes. Lo trabajado de más no suma: las horas extra
     * las carga a mano el administrador.
     *
     * Sin alguno de los dos marcajes no hay cómo medir lo trabajado y se
     * paga por estado: presente o tardanza, el día; medio día, la mitad.
     */
    public function fraccionDiaPagada(Empleado $empleado): float
    {
        if (! \in_array($this->estado, self::ESTADOS_TRABAJADOS, true)) {
            return 0.0;
        }

        $trabajados = $this->minutosTrabajados();

        if ($trabajados === null) {
            return $this->estado === 'medio_dia' ? 0.5 : 1.0;
        }

        $jornada = $empleado->jornada_minutos;
        $perdonados = min($empleado->tolerancia_minutos, $empleado->minutosDeAtraso($this->hora_entrada));
        $faltante = max(0, $jornada - $trabajados - $perdonados);

        return round(($jornada - $faltante) / $jornada, 4);
    }

    /** Diferencia contra la jornada estándar: positiva = de más, negativa = de menos. */
    public function getDiferenciaJornadaAttribute(): ?float
    {
        $horas = $this->horas_trabajadas;

        return $horas === null ? null : round($horas - config('nomina.horas_jornada'), 2);
    }

    /** Verdadero si el atraso se calculó a partir del horario del empleado. */
    public function getCalculadoDesdeHorarioAttribute(): bool
    {
        return (bool) $this->empleado?->tiene_horario;
    }

    /** El domingo no se contabiliza para planilla. */
    public function getEsDomingoAttribute(): bool
    {
        return $this->fecha && $this->fecha->dayOfWeek === Carbon::SUNDAY;
    }

    /**
     * Solo días laborables (excluye domingos).
     *
     * La expresión depende del motor: DAYOFWEEK() es de MySQL y en SQLite no
     * existe, así que la planilla fallaba al generarse tras el cambio de base.
     */
    public function scopeLaborables($query)
    {
        $noEsDomingo = match ($query->getConnection()->getDriverName()) {
            'sqlite' => "strftime('%w', fecha) != '0'",
            'pgsql' => 'EXTRACT(DOW FROM fecha) != 0',
            'sqlsrv' => "DATENAME(WEEKDAY, fecha) != 'Sunday'",
            default => 'DAYOFWEEK(fecha) != 1',
        };

        return $query->whereRaw($noEsDomingo);
    }

    /**
     * Asistencias dentro de un rango de fechas, ambos extremos incluidos.
     *
     * Los límites se normalizan a fecha sin hora porque llegan casteados a
     * Carbon ('2026-07-27 00:00:00') mientras que la columna guarda solo el día
     * ('2026-07-27'). SQLite compara esos valores como texto y la cadena más
     * corta queda antes, así que el día de inicio caía fuera del rango: la
     * planilla perdía en silencio el primer día de cada período.
     */
    public function scopeEnPeriodo($query, $inicio, $fin)
    {
        return $query->whereBetween('fecha', [
            Carbon::parse($inicio)->toDateString(),
            Carbon::parse($fin)->toDateString(),
        ]);
    }
}
