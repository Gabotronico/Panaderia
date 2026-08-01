<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompraInsumo extends Model
{
    protected $table = 'compras_insumo';

    protected $fillable = [
        'insumo_id',
        'user_id',
        'cantidad',
        'precio_unitario',
        'total',
        'fecha',
        'observaciones',
    ];

    protected $casts = [
        'cantidad'        => 'decimal:5',
        'precio_unitario' => 'decimal:5',
        'total'          => 'decimal:2',
        'fecha'          => 'date:Y-m-d',
    ];

    public function insumo()
    {
        return $this->belongsTo(Insumo::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
