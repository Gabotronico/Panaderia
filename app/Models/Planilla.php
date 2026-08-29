<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Planilla extends Model
{
    protected $fillable = [
        'tipo', 'periodo_inicio', 'periodo_fin',
        'estado', 'fecha_pago', 'total_general', 'observaciones', 'user_id',
    ];

    protected $casts = [
        'periodo_inicio' => 'date:Y-m-d',
        'periodo_fin'    => 'date:Y-m-d',
        'fecha_pago'     => 'date:Y-m-d',
        'total_general'  => 'decimal:2',
    ];

    /**
     * Día que se propone al marcarla pagada: el cierre del período, que es el
     * sábado de pago. Si ese sábado todavía no llegó se ofrece hoy, para no
     * sugerir una fecha futura que la validación va a rechazar igual.
     */
    public function getFechaPagoSugeridaAttribute(): \Carbon\Carbon
    {
        $hoy = \Carbon\Carbon::today();

        return $this->periodo_fin->gt($hoy) ? $hoy : $this->periodo_fin->copy();
    }

    public function detalles()
    {
        return $this->hasMany(PlanillaEmpleado::class);
    }

    public function adelantos()
    {
        return $this->hasMany(Adelanto::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
