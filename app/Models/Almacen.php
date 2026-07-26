<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Almacen extends Model
{
    protected $table = 'almacenes';

    protected $fillable = ['nombre', 'descripcion', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function cajeros()
    {
        return $this->hasMany(User::class);
    }

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'almacen_producto')
            ->withPivot('stock')
            ->withTimestamps();
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    // Stock de un producto específico en este almacén
    public function stockProducto(int $productoId): int
    {
        $registro = $this->productos()->where('producto_id', $productoId)->first();
        return $registro ? (int) $registro->pivot->stock : 0;
    }
}
