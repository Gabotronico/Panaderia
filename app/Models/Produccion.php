<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Una corrida de producción: los insumos que se consumieron y los productos
 * que salieron para la venta.
 */
class Produccion extends Model
{
    protected $table = 'producciones';

    protected $fillable = ['fecha', 'observaciones', 'user_id'];

    protected $casts = ['fecha' => 'date:Y-m-d'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Insumos consumidos. El costo unitario del pivot es el que regía al
     * producir, no el actual del insumo.
     */
    public function insumos()
    {
        return $this->belongsToMany(Insumo::class, 'produccion_insumo')
            ->withPivot('cantidad', 'costo_unitario')
            ->withTimestamps();
    }

    /** Productos obtenidos, con las unidades que entraron al stock. */
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'produccion_producto')
            ->withPivot('cantidad')
            ->withTimestamps();
    }

    /** Lo que costaron los insumos de esta corrida. */
    public function getCostoTotalAttribute(): float
    {
        return round(
            $this->insumos->sum(fn ($i) => (float) $i->pivot->cantidad * (float) $i->pivot->costo_unitario),
            2
        );
    }

    /** Unidades totales producidas, sumando todos los productos. */
    public function getUnidadesProducidasAttribute(): int
    {
        return (int) $this->productos->sum(fn ($p) => (int) $p->pivot->cantidad);
    }

    /**
     * Costo por unidad producida. Sirve para saber si el precio de venta
     * cubre lo que cuesta hacer el producto.
     */
    public function getCostoPorUnidadAttribute(): float
    {
        $unidades = $this->unidades_producidas;

        return $unidades > 0 ? round($this->costo_total / $unidades, 2) : 0.0;
    }

    public function scopeEnPeriodo($query, $inicio, $fin)
    {
        return $query->whereBetween('fecha', [$inicio, $fin]);
    }
}
