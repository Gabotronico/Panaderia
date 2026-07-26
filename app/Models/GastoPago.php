<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GastoPago extends Model
{
    protected $table = 'gastos_pagos';

    protected $fillable = [
        'gasto_fijo_id', 'periodo', 'fecha_vencimiento', 'monto_estimado',
        'monto_real', 'fecha_pago', 'estado', 'referencia', 'observaciones', 'user_id',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'fecha_pago'        => 'date',
        'monto_estimado'    => 'decimal:2',
        'monto_real'        => 'decimal:2',
    ];

    public function gastoFijo()
    {
        return $this->belongsTo(GastoFijo::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function estaVencido(): bool
    {
        return $this->estado === 'pendiente' &&
               $this->getRawOriginal('fecha_vencimiento') < now()->toDateString();
    }
}
