<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'almacen_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    public function cortesCaja()
    {
        return $this->hasMany(CorteCaja::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    public function esAdministrador(): bool
    {
        return $this->hasRole('Administrador');
    }
}