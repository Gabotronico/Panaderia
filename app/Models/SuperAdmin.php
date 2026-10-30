<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Administrador del servicio (panel /panel del dominio central). Siempre
 * lee de la base central, aunque haya una panadería iniciada.
 */
class SuperAdmin extends Authenticatable
{
    use CentralConnection;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
