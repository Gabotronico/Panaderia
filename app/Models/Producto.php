<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'categoria_id',
        'nombre',
        'descripcion',
        'precio_venta',
        'costo_unitario',
        'stock',
        'stock_minimo',
        'imagen',
        'activo',
    ];

    protected $casts = [
        'precio_venta' => 'decimal:2',
        // Cuatro decimales: el costo unitario de una pieza barata cae por
        // debajo del centavo y redondear acá arrastra error al multiplicar
        // por cientos de unidades vendidas.
        'costo_unitario' => 'decimal:4',
        'activo' => 'boolean',
    ];

    // Relación: Un producto pertenece a una categoría
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    // Relación: Un producto tiene muchos detalles de venta
    public function detalleVentas()
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function insumos()
    {
        return $this->belongsToMany(Insumo::class, 'producto_insumo')
                    ->withPivot('cantidad_necesaria')
                    ->withTimestamps();
    }

    public function almacenes()
    {
        return $this->belongsToMany(Almacen::class, 'almacen_producto')
                    ->withPivot('stock')
                    ->withTimestamps();
    }
}