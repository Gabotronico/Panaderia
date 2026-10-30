<?php

namespace App\Support\Actualizaciones;

use Illuminate\Support\Facades\Cache;
use Native\Desktop\Events\AutoUpdater\DownloadProgress;
use Native\Desktop\Events\AutoUpdater\Error;
use Native\Desktop\Events\AutoUpdater\UpdateDownloaded;

/**
 * Estado de la descarga de la actualización, compartido entre la app de
 * escritorio (que avisa por eventos) y la pantalla que la muestra.
 *
 * Los eventos de Electron llegan a Laravel; acá se guardan en caché para que la
 * página los consulte por polling. Es más simple y confiable que intentar
 * cruzar el puente de eventos hasta el JavaScript de la vista.
 */
final class Descarga
{
    private const CLAVE = 'actualizaciones.descarga';

    /** @return array<string, mixed> */
    public static function estado(): array
    {
        $guardado = Cache::get(self::CLAVE);

        return is_array($guardado) ? $guardado : ['estado' => 'inactiva'];
    }

    public static function preparar(string $version): void
    {
        Cache::put(self::CLAVE, [
            'estado' => 'descargando',
            'version' => $version,
            'percent' => 0,
            'transferred' => 0,
            'total' => 0,
            'bytes_por_segundo' => 0,
            'mensaje' => null,
            'actualizado_en' => now()->toIso8601String(),
        ], now()->addMinutes(30));
    }

    public static function progreso(DownloadProgress $evento): void
    {
        Cache::put(self::CLAVE, [
            'estado' => 'descargando',
            'percent' => round(min(100, max(0, $evento->percent)), 1),
            'transferred' => $evento->transferred,
            'total' => $evento->total,
            'bytes_por_segundo' => $evento->bytesPerSecond,
            'mensaje' => null,
            'actualizado_en' => now()->toIso8601String(),
        ], now()->addMinutes(30));
    }

    public static function lista(UpdateDownloaded $evento): void
    {
        Cache::put(self::CLAVE, [
            'estado' => 'lista',
            'version' => $evento->version,
            'percent' => 100,
            'mensaje' => null,
            'actualizado_en' => now()->toIso8601String(),
        ], now()->addHour());
    }

    public static function error(Error $evento): void
    {
        Cache::put(self::CLAVE, [
            'estado' => 'error',
            'mensaje' => $evento->message,
            'actualizado_en' => now()->toIso8601String(),
        ], now()->addMinutes(10));
    }

    public static function olvidar(): void
    {
        Cache::forget(self::CLAVE);
    }
}
