<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Gasto ocasional: no se repite mes a mes ni tiene vencimiento.
 * Transporte, una reparación puntual, una compra de urgencia.
 */
class GastoVariable extends Model
{
    protected $table = 'gastos_variables';

    protected $fillable = [
        'fecha', 'concepto', 'categoria', 'monto', 'proveedor', 'observaciones', 'user_id',
    ];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
        'monto' => 'decimal:2',
    ];

    public const CATEGORIAS = [
        'transporte'    => 'Transporte y fletes',
        'mantenimiento' => 'Mantenimiento y reparaciones',
        'insumos'       => 'Compras de urgencia',
        'limpieza'      => 'Limpieza e higiene',
        'empaques'      => 'Empaques y descartables',
        'otro'          => 'Otro',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getEtiquetaCategoriaAttribute(): string
    {
        return self::CATEGORIAS[$this->categoria] ?? ucfirst($this->categoria);
    }

    /**
     * Gastos dentro de un rango. Los límites van como 'Y-m-d' porque la
     * columna guarda el día sin hora: comparar contra un Carbon completo
     * dejaría fuera los registros de los bordes.
     */
    public function scopeEnPeriodo($query, $inicio, $fin)
    {
        return $query->whereBetween('fecha', [
            \Carbon\Carbon::parse($inicio)->toDateString(),
            \Carbon\Carbon::parse($fin)->toDateString(),
        ]);
    }
}
