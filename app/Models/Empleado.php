<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $fillable = [
        'nombre', 'apellido', 'ci', 'telefono', 'cargo_id',
        'salario_base', 'tipo_pago',
        'fecha_ingreso', 'hora_entrada', 'hora_salida', 'minutos_tolerancia',
        'activo', 'observaciones',
    ];

    protected $casts = [
        'salario_base'  => 'decimal:2',
        'fecha_ingreso' => 'date',
        'activo'        => 'boolean',
    ];

    public function cargo()
    {
        return $this->belongsTo(Cargo::class);
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class);
    }

    public function adelantos()
    {
        return $this->hasMany(Adelanto::class);
    }

    public function planillaDetalles()
    {
        return $this->hasMany(PlanillaEmpleado::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }

    /**
     * Divisor para convertir el salario base a valor por día.
     * Mensual → días laborables del mes; Semanal → días laborables de la semana.
     */
    public function getDivisorDiasAttribute(): int
    {
        return $this->tipo_pago === 'mensual'
            ? config('nomina.dias_mes')
            : config('nomina.dias_semana');
    }

    /** Valor por día sin redondear — base para los demás equivalentes. */
    protected function valorDiaExacto(): float
    {
        return (float) $this->salario_base / $this->divisor_dias;
    }

    public function getValorDiaAttribute(): float
    {
        return round($this->valorDiaExacto(), 2);
    }

    public function getTarifaHoraAttribute(): float
    {
        return round($this->valorDiaExacto() / config('nomina.horas_jornada'), 2);
    }

    /** Equivalente semanal del salario, sin importar el tipo de pago. */
    public function getValorSemanaAttribute(): float
    {
        return round($this->valorDiaExacto() * config('nomina.dias_semana'), 2);
    }

    /** Equivalente mensual del salario, sin importar el tipo de pago. */
    public function getValorMesAttribute(): float
    {
        return round($this->valorDiaExacto() * config('nomina.dias_mes'), 2);
    }

    /** Antigüedad en años cumplidos desde la fecha de ingreso. */
    public function getAntiguedadAniosAttribute(): int
    {
        return $this->fecha_ingreso
            ? (int) $this->fecha_ingreso->diffInYears(now(), absolute: true)
            : 0;
    }

    /** Texto legible de antigüedad, ej. "2 años, 3 meses". */
    public function getAntiguedadTextoAttribute(): string
    {
        if (!$this->fecha_ingreso) {
            return '—';
        }

        $anios = $this->antiguedad_anios;
        $meses = (int) $this->fecha_ingreso->copy()->addYears($anios)
                        ->diffInMonths(now(), absolute: true);

        $partes = [];
        if ($anios > 0) $partes[] = $anios . ' año' . ($anios > 1 ? 's' : '');
        if ($meses > 0) $partes[] = $meses . ' mes' . ($meses > 1 ? 'es' : '');

        return $partes ? implode(', ', $partes) : 'Menos de un mes';
    }

    /** Saldo de adelantos aún no descontados en ninguna planilla. */
    public function getAdelantosPendientesAttribute(): float
    {
        return (float) $this->adelantos()->whereNull('planilla_id')->sum('monto');
    }

    /**
     * Cuánto más se le puede adelantar sin pasarse de su sueldo del ciclo.
     * Evita que la deuda supere lo que la próxima planilla puede descontar.
     */
    public function getCapacidadAdelantoAttribute(): float
    {
        return round(max(0, (float) $this->salario_base - $this->adelantos_pendientes), 2);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // ── Horario de trabajo ──────────────────────────────────────────

    /** Solo con entrada y salida definidas se puede calcular algo. */
    public function getTieneHorarioAttribute(): bool
    {
        return $this->hora_entrada !== null && $this->hora_salida !== null;
    }

    public function getHorarioTextoAttribute(): string
    {
        if (!$this->tiene_horario) {
            return 'Sin horario definido';
        }

        return $this->formatoHora($this->hora_entrada) . ' – ' . $this->formatoHora($this->hora_salida);
    }

    /** Tolerancia propia del empleado; si no tiene, la general del sistema. */
    public function getToleranciaMinutosAttribute(): int
    {
        return (int) ($this->minutos_tolerancia ?? config('nomina.tolerancia_tardanza'));
    }

    /** Duración de la jornada según su horario; sin horario, la estándar. */
    public function getJornadaHorasAttribute(): float
    {
        if (!$this->tiene_horario) {
            return (float) config('nomina.horas_jornada');
        }

        return round($this->minutosEntreHoras($this->hora_entrada, $this->hora_salida) / 60, 2);
    }

    /**
     * Minutos de atraso respecto a la hora de entrada programada.
     *
     * Descuenta la tolerancia y nunca devuelve negativo: llegar temprano no
     * genera crédito, solo evita el atraso.
     */
    public function calcularTardanza(?string $horaMarcada): int
    {
        if (!$this->tiene_horario || !$horaMarcada) {
            return 0;
        }

        $desfase = $this->desfaseMinutos($this->hora_entrada, $horaMarcada);

        return max(0, $desfase - $this->tolerancia_minutos);
    }

    /**
     * Minutos de $marcada respecto a $programada, normalizados a ±12 h.
     *
     * La normalización resuelve los turnos que cruzan medianoche: si el
     * horario termina a las 06:00 y el marcaje dice 05:50, la resta cruda da
     * −10 min (salió antes), no +1430 (se quedó casi un día).
     */
    private function desfaseMinutos(string $programada, string $marcada): int
    {
        $diff = $this->minutoDelDia($marcada) - $this->minutoDelDia($programada);

        if ($diff > 720) {
            $diff -= 1440;
        } elseif ($diff < -720) {
            $diff += 1440;
        }

        return $diff;
    }

    /** Duración de un tramo horario, contemplando el cruce de medianoche. */
    private function minutosEntreHoras(string $desde, string $hasta): int
    {
        $minutos = $this->minutoDelDia($hasta) - $this->minutoDelDia($desde);

        return $minutos <= 0 ? $minutos + 1440 : $minutos;
    }

    private function minutoDelDia(string $hora): int
    {
        $t = Carbon::parse($hora);

        return $t->hour * 60 + $t->minute;
    }

    private function formatoHora(string $hora): string
    {
        return Carbon::parse($hora)->format('H:i');
    }
}
