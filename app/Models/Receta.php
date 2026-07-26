<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receta extends Model
{
    protected $fillable = ['nombre', 'rendimiento', 'descripcion', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function insumos()
    {
        return $this->belongsToMany(Insumo::class, 'receta_insumo')
            ->withPivot('cantidad_necesaria')
            ->withTimestamps();
    }
}
