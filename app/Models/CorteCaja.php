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
        'ventas_efectivo',
        'ventas_qr',
        'total_efectivo',
        'total_qr',
        'monto_final',
        'diferencia',
        'diferencia_qr',
        'estado',
        'cerrado_por',
        'observaciones',
    ];

    protected $casts = [
        'fecha_corte' => 'date:Y-m-d',
        'monto_inicial' => 'decimal:2',
        'total_ventas' => 'decimal:2',
        'ventas_efectivo' => 'decimal:2',
        'ventas_qr' => 'decimal:2',
        'total_efectivo' => 'decimal:2',
        'total_qr' => 'decimal:2',
        'monto_final' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'diferencia_qr' => 'decimal:2',
    ];

    // Relación: Un corte pertenece a un usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Usuario que realizó el cierre — puede no ser el dueño del turno. */
    public function cerradoPor()
    {
        return $this->belongsTo(User::class, 'cerrado_por');
    }

    /** Verdadero si lo cerró alguien distinto al cajero del turno. */
    public function getCerradoPorTerceroAttribute(): bool
    {
        return $this->cerrado_por !== null && $this->cerrado_por !== $this->user_id;
    }

    /**
     * Efectivo que debería haber en el cajón: lo que se puso al abrir más lo
     * que se cobró en efectivo. Las ventas por QR quedan fuera a propósito —
     * ese dinero va a la cuenta, no a la caja.
     */
    public function getEfectivoEsperadoAttribute(): float
    {
        return (float) $this->monto_inicial + (float) $this->ventas_efectivo;
    }

    /** Sobrante (+) o faltante (−) de efectivo respecto a lo esperado. */
    public function getDiferenciaEfectivoAttribute(): float
    {
        return round((float) $this->total_efectivo - $this->efectivo_esperado, 2);
    }

    /** Sobrante (+) o faltante (−) de QR respecto a lo registrado en ventas. */
    public function getDiferenciaQrRealAttribute(): float
    {
        return round((float) $this->total_qr - (float) $this->ventas_qr, 2);
    }

    /** Verdadero si tanto el efectivo como el QR cuadran al centavo. */
    public function getCuadraAttribute(): bool
    {
        return abs($this->diferencia_efectivo) < 0.01 && abs($this->diferencia_qr_real) < 0.01;
    }
}