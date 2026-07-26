<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $fillable = [
        'nombre', 'apellido', 'ci', 'telefono', 'cargo_id',
        'salario_base', 'tipo_pago', 'factor_hora_extra',
        'fecha_ingreso', 'activo', 'observaciones',
    ];

    protected $casts = [
        'salario_base'      => 'decimal:2',
        'factor_hora_extra' => 'decimal:2',
        'fecha_ingreso'     => 'date',
        'activo'            => 'boolean',
    ];

    public function cargo()
    {
        return $this->belongsTo(Cargo::class);
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class);
    }

    public function adelantos()
    {
        return $this->hasMany(Adelanto::class);
    }

    public function planillaDetalles()
    {
        return $this->hasMany(PlanillaEmpleado::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }

    public function getValorDiaAttribute(): float
    {
        return (float) $this->salario_base / 30;
    }

    public function getTarifaHoraAttribute(): float
    {
        return $this->valor_dia / 8;
    }
}
