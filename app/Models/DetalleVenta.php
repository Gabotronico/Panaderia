<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleVenta extends Model
{
    use HasFactory;

    protected $table = 'detalle_ventas';

    protected $fillable = [
        'venta_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'costo_unitario',
        'subtotal',
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
        // Costo congelado al vender: se copia del producto en el momento de
        // la venta y no se recalcula nunca. Si mañana sube la harina, esta
        // venta sigue mostrando lo que realmente costó.
        'costo_unitario' => 'decimal:4',
        'subtotal' => 'decimal:2',
    ];

    /** Lo que costó lo vendido en esta línea. */
    public function getCostoTotalAttribute(): float
    {
        return round((float) $this->cantidad * (float) $this->costo_unitario, 2);
    }

    // Relación: Un detalle pertenece a una venta
    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    // Relación: Un detalle pertenece a un producto
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}