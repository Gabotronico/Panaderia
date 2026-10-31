<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pase de un solo uso que lleva la sesión del login central a una panadería.
 * Guarda el hash del token: el valor que viaja en la URL nunca se almacena.
 */
class TokenEntrada extends Model
{
    protected $table = 'tokens_entrada';

    protected $fillable = ['token', 'persona_id', 'tenant_id', 'user_id', 'expires_at', 'used_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function getConnectionName(): ?string
    {
        return config('tenancy.database.central_connection');
    }

    public function vigente(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }
}
