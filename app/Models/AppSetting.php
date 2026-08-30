<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AppSetting extends Model
{
    /**
     * Memoización por proceso para el nombre del negocio: el provider lo
     * consulta en el arranque de cada petición y sin caché serían dos viajes
     * a la base (chequeo de tabla + lectura) en cada clic. El proceso PHP
     * vive mientras la app esté abierta, así que basta con invalidar al
     * escribir. write() se encarga de eso.
     */
    private static bool $nombreLeido = false;

    private static ?string $nombreGuardado = null;

    protected $fillable = [
        'key',
        'value',
    ];

    public static function read(string $key, ?string $default = null): ?string
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    /**
     * Devuelve el nombre del negocio cacheado, o null mientras no se pueda
     * leer (instalación inicial, tabla inexistente).
     */
    public static function nombreEnCache(): ?string
    {
        if (! self::$nombreLeido && Schema::hasTable('app_settings')) {
            self::$nombreLeido = true;
            self::$nombreGuardado = static::query()->where('key', 'business_name')->value('value');
        }

        return self::$nombreLeido ? self::$nombreGuardado : null;
    }

    public static function write(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        if ($key === 'business_name') {
            self::$nombreLeido = false;
        }
    }
}
