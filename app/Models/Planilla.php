<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Planilla extends Model
{
    protected $fillable = [
        'tipo', 'periodo_inicio', 'periodo_fin',
        'estado', 'total_general', 'observaciones', 'user_id',
    ];

    protected $casts = [
        'periodo_inicio' => 'date:Y-m-d',
        'periodo_fin'    => 'date:Y-m-d',
        'total_general'  => 'decimal:2',
    ];

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
