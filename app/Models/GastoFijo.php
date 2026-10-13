<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class GastoFijo extends Model
{
    protected $table = 'gastos_fijos';

    protected $fillable = [
        'nombre', 'categoria', 'monto_estimado', 'frecuencia',
        'dia_vencimiento', 'dia_semana', 'mes_inicio', 'proveedor', 'observaciones',
        'activo', 'user_id',
    ];

    protected $casts = [
        'monto_estimado'  => 'decimal:2',
        'activo'          => 'boolean',
        'dia_vencimiento' => 'integer',
        'dia_semana'      => 'integer',
        'mes_inicio'      => 'integer',
    ];

    public const CATEGORIAS = [
        'alquiler'      => 'Alquiler',
        'servicios'     => 'Servicios',
        'personal'      => 'Personal y beneficios',
        'mantenimiento' => 'Mantenimiento',
        'impuestos'     => 'Impuestos',
        'otro'          => 'Otro',
    ];

    public const FRECUENCIAS = [
        'semanal'    => 'Semanal',
        'mensual'    => 'Mensual',
        'bimestral'  => 'Bimestral',
        'trimestral' => 'Trimestral',
        'semestral'  => 'Semestral',
        'anual'      => 'Anual',
    ];

    /** Días de la semana como los numera Carbon (isoWeekday). */
    public const DIAS_SEMANA = [
        1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves',
        5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo',
    ];

    public function pagos()
    {
        return $this->hasMany(GastoPago::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function esSemanal(): bool
    {
        return $this->frecuencia === 'semanal';
    }

    public function seVenceEnMes(int $year, int $month): bool
    {
        $diferencia = ($month - $this->mes_inicio + 12) % 12;

        return match ($this->frecuencia) {
            'semanal', 'mensual' => true,
            'bimestral'  => $diferencia % 2 === 0,
            'trimestral' => $diferencia % 3 === 0,
            'semestral'  => $diferencia % 6 === 0,
            'anual'      => $month === $this->mes_inicio,
            default      => false,
        };
    }

    /**
     * Fechas en que vence este gasto dentro de un mes: una por semana si es
     * semanal (todos los sábados, por ejemplo: cuatro o cinco según el mes),
     * una sola en los demás casos, o ninguna si ese mes no le toca.
     *
     * @return list<Carbon>
     */
    public function vencimientosEnMes(int $year, int $month): array
    {
        if (! $this->seVenceEnMes($year, $month)) {
            return [];
        }

        $inicio = Carbon::createFromDate($year, $month, 1)->startOfDay();

        if (! $this->esSemanal()) {
            // El día de vencimiento no puede exceder el último día del mes.
            return [$inicio->copy()->day(min($this->dia_vencimiento, $inicio->daysInMonth))];
        }

        $diaSemana = $this->dia_semana ?: 6;
        $fecha = $inicio->copy();
        while ($fecha->isoWeekday() !== $diaSemana) {
            $fecha->addDay();
        }

        $fechas = [];
        for (; $fecha->month === $month; $fecha->addWeek()) {
            $fechas[] = $fecha->copy();
        }

        return $fechas;
    }

    /** Lo que se espera pagar por este gasto en un mes. */
    public function estimadoEnMes(int $year, int $month): float
    {
        return round((float) $this->monto_estimado * \count($this->vencimientosEnMes($year, $month)), 2);
    }

    /** "Sábados", "Día 5": cuándo vence, en palabras. */
    public function getVencimientoTextoAttribute(): string
    {
        if ($this->esSemanal()) {
            $dia = self::DIAS_SEMANA[$this->dia_semana ?: 6];

            return 'Cada '.mb_strtolower($dia);
        }

        return 'Día '.$this->dia_vencimiento;
    }

    public static function etiquetaCategoria(string $cat): string
    {
        return self::CATEGORIAS[$cat] ?? 'Otro';
    }

    public static function etiquetaFrecuencia(string $frec): string
    {
        return self::FRECUENCIAS[$frec] ?? $frec;
    }
}
