<?php

namespace App\Support\Actualizaciones;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Última versión publicada de la app de escritorio.
 *
 * Los instaladores viven como *Releases* en GitHub (repo configurado en
 * config/actualizaciones.php). La misma consulta sirve para las dos caras:
 *
 * - en el navegador, para ofrecer la descarga del .exe;
 * - en el escritorio, para mostrar "Actualizar" solo si la versión publicada
 *   es más nueva que la instalada (config('nativephp.version')).
 *
 * Nunca lanza: si GitHub no responde, devuelve null y la interfaz simplemente
 * no muestra el aviso.
 */
final class Actualizaciones
{
    private const CLAVE_CACHE = 'actualizaciones.ultima';

    /** Versión de la app que está corriendo (la empaquetada en el build). */
    public static function instalada(): string
    {
        return (string) config('nativephp.version', '0.0.0');
    }

    /** ¿Estamos dentro de la app de escritorio (Electron) y no en el navegador? */
    public static function escritorio(): bool
    {
        return (bool) config('nativephp-internal.running', false);
    }

    /**
     * Datos del último release publicado, o null si no se pudo consultar.
     *
     * @return array{version: string, nombre: string, notas: string, fecha: string|null, url: string, tamano: int, pagina: string}|null
     */
    public static function ultima(bool $forzar = false): ?array
    {
        if (! $forzar) {
            $guardada = Cache::get(self::CLAVE_CACHE);

            if (is_array($guardada)) {
                return $guardada;
            }
        }

        $datos = self::consultarGitHub();

        if ($datos !== null) {
            Cache::put(
                self::CLAVE_CACHE,
                $datos,
                now()->addMinutes((int) config('actualizaciones.cache_minutos', 10)),
            );
        }

        return $datos;
    }

    /** ¿La versión publicada es más nueva que la instalada? */
    public static function hayNueva(?array $ultima = null): bool
    {
        $ultima ??= self::ultima();

        return $ultima !== null
            && version_compare(self::instalada(), $ultima['version'], '<');
    }

    /**
     * Todo lo que necesita el encabezado para decidir qué botón mostrar.
     *
     * @return array{escritorio: bool, instalada: string, hay_actualizacion: bool, ultima: array|null}
     */
    public static function estado(): array
    {
        $ultima = self::ultima();

        return [
            'escritorio' => self::escritorio(),
            'instalada' => self::instalada(),
            'hay_actualizacion' => self::hayNueva($ultima),
            'ultima' => $ultima,
        ];
    }

    public static function olvidar(): void
    {
        Cache::forget(self::CLAVE_CACHE);
    }

    /**
     * @return array{version: string, nombre: string, notas: string, fecha: string|null, url: string, tamano: int, pagina: string}|null
     */
    private static function consultarGitHub(): ?array
    {
        $owner = (string) config('actualizaciones.owner');
        $repo = (string) config('actualizaciones.repo');

        if ($owner === '' || $repo === '') {
            return null;
        }

        try {
            $peticion = Http::withHeaders([
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent' => 'Obrador',
            ])
                ->timeout((int) config('actualizaciones.tiempo_espera', 8));

            if (($token = (string) config('actualizaciones.token')) !== '') {
                $peticion = $peticion->withToken($token);
            }

            $respuesta = $peticion->get("https://api.github.com/repos/{$owner}/{$repo}/releases/latest");

            if (! $respuesta->successful()) {
                Log::warning('No se pudo leer la última versión publicada.', [
                    'status' => $respuesta->status(),
                ]);

                return null;
            }

            return self::desdeRelease((array) $respuesta->json());
        } catch (\Throwable $exception) {
            Log::warning('Falló la consulta de actualizaciones.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $release
     * @return array{version: string, nombre: string, notas: string, fecha: string|null, url: string, tamano: int, pagina: string}|null
     */
    private static function desdeRelease(array $release): ?array
    {
        $tag = (string) ($release['tag_name'] ?? '');
        $version = ltrim($tag, 'vV');

        if ($version === '') {
            return null;
        }

        $assets = collect($release['assets'] ?? [])->filter(
            fn ($asset) => is_array($asset) && str_ends_with(strtolower((string) ($asset['name'] ?? '')), '.exe'),
        );

        // El instalador NSIS se llama "...-Setup-1.0.16.exe".
        $instalador = $assets->first(
            fn ($asset) => str_contains(strtolower((string) $asset['name']), 'setup'),
        ) ?? $assets->first();

        if ($instalador === null) {
            return null;
        }

        return [
            'version' => $version,
            'nombre' => (string) $instalador['name'],
            'notas' => trim((string) ($release['body'] ?? '')),
            'fecha' => $release['published_at'] ?? null,
            'url' => (string) $instalador['browser_download_url'],
            'tamano' => (int) ($instalador['size'] ?? 0),
            'pagina' => (string) ($release['html_url'] ?? ''),
        ];
    }
}
