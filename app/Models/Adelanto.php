<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adelanto extends Model
{
    protected $fillable = [
        'empleado_id', 'monto', 'fecha', 'descripcion', 'planilla_id', 'user_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha' => 'date:Y-m-d',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    public function planilla()
    {
        return $this->belongsTo(Planilla::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
