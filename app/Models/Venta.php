<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use HasFactory;

    protected $table = 'ventas';

    protected $fillable = [
        'user_id',
        'almacen_id',
        'numero_venta',
        'subtotal',
        'descuento',
        'total',
        'tipo_pago',
        'monto_recibido',
        'cambio',
        'estado',
        'es_directa',
        'observaciones',
    ];

    protected $casts = [
        'subtotal'       => 'decimal:2',
        'descuento'      => 'decimal:2',
        'total'          => 'decimal:2',
        'monto_recibido' => 'decimal:2',
        'cambio'         => 'decimal:2',
        'es_directa'     => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class);
    }
}