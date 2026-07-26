<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorteCaja extends Model
{
    use HasFactory;

    protected $table = 'cortes_caja';

    protected $fillable = [
        'user_id',
        'fecha_corte',
        'hora_apertura',
        'hora_cierre',
        'monto_inicial',
        'total_ventas',
        'total_efectivo',
        'monto_final',
        'diferencia',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_corte' => 'date',
        'monto_inicial' => 'decimal:2',
        'total_ventas' => 'decimal:2',
        'total_efectivo' => 'decimal:2',
        'monto_final' => 'decimal:2',
        'diferencia' => 'decimal:2',
    ];

    // Relación: Un corte pertenece a un usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}