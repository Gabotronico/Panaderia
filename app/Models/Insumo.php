<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insumo extends Model
{
    use HasFactory;

    protected $table = 'insumos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'unidad_medida',
        'cantidad_stock',
        'stock_minimo',
        'costo_unitario',
        'activo',
    ];

    protected $casts = [
        'cantidad_stock' => 'decimal:2',
        'stock_minimo' => 'decimal:2',
        'costo_unitario' => 'decimal:5',
        'activo' => 'boolean',
    ];

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_insumo')
                    ->withPivot('cantidad_necesaria')
                    ->withTimestamps();
    }

    public function compras()
    {
        return $this->hasMany(CompraInsumo::class);
    }

    public function mermas()
    {
        return $this->hasMany(MermaInsumo::class);
    }
}