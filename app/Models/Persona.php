<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Persona: la cuenta que inicia sesión. Vive en la base central y puede estar
 * vinculada a varias panaderías, cada una con su propio usuario (rol, almacén).
 */
class Persona extends Model
{
    protected $table = 'personas';

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password'];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function getConnectionName(): ?string
    {
        return config('tenancy.database.central_connection');
    }

    public static function deCorreo(string $email): ?self
    {
        $correo = mb_strtolower(trim($email));

        return $correo === '' ? null : self::query()->where('email', $correo)->first();
    }
}
