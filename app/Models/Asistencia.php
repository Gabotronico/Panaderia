<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $fillable = [
        'empleado_id', 'fecha', 'estado', 'hora_entrada', 'hora_salida',
        'minutos_tardanza', 'horas_extra', 'observaciones', 'user_id',
    ];

    protected $casts = [
        'fecha'      => 'date',
        'horas_extra' => 'decimal:2',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
