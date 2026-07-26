<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GastoFijo extends Model
{
    protected $table = 'gastos_fijos';

    protected $fillable = [
        'nombre', 'categoria', 'monto_estimado', 'frecuencia',
        'dia_vencimiento', 'mes_inicio', 'proveedor', 'observaciones',
        'activo', 'user_id',
    ];

    protected $casts = [
        'monto_estimado'  => 'decimal:2',
        'activo'          => 'boolean',
        'dia_vencimiento' => 'integer',
        'mes_inicio'      => 'integer',
    ];

    public function pagos()
    {
        return $this->hasMany(GastoPago::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seVenceEnMes(int $year, int $month): bool
    {
        $diferencia = ($month - $this->mes_inicio + 12) % 12;

        return match ($this->frecuencia) {
            'mensual'    => true,
            'bimestral'  => $diferencia % 2 === 0,
            'trimestral' => $diferencia % 3 === 0,
            'semestral'  => $diferencia % 6 === 0,
            'anual'      => $month === $this->mes_inicio,
            default      => false,
        };
    }

    public static function etiquetaCategoria(string $cat): string
    {
        return match ($cat) {
            'alquiler'     => 'Alquiler',
            'servicios'    => 'Servicios',
            'mantenimiento'=> 'Mantenimiento',
            'impuestos'    => 'Impuestos',
            default        => 'Otro',
        };
    }

    public static function etiquetaFrecuencia(string $frec): string
    {
        return match ($frec) {
            'mensual'    => 'Mensual',
            'bimestral'  => 'Bimestral',
            'trimestral' => 'Trimestral',
            'semestral'  => 'Semestral',
            'anual'      => 'Anual',
            default      => $frec,
        };
    }
}
