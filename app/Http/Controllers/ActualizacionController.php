<?php

namespace App\Http\Controllers;

use App\Support\Actualizaciones\Actualizaciones;
use App\Support\Actualizaciones\Descarga;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Native\Desktop\Facades\AutoUpdater;

/**
 * Actualizaciones de la app de escritorio.
 *
 * `estado` lo consulta el encabezado en todas las pantallas (es barato: la
 * respuesta de GitHub se cachea). Descargar e instalar solo existen dentro de
 * la app empaquetada; en el navegador esas rutas responden 404.
 */
class ActualizacionController extends Controller
{
    public function estado(): JsonResponse
    {
        return response()->json([
            ...Actualizaciones::estado(),
            'descarga' => Descarga::estado(),
        ]);
    }

    public function descargar(Request $request): JsonResponse
    {
        abort_unless(Actualizaciones::escritorio(), 404);

        $version = (string) ($request->input('version') ?: Actualizaciones::instalada());

        Descarga::preparar($version);
        AutoUpdater::downloadUpdate();

        return response()->json(['iniciada' => true], 202);
    }

    public function progreso(): JsonResponse
    {
        abort_unless(Actualizaciones::escritorio(), 404);

        return response()->json(Descarga::estado());
    }

    public function instalar(): JsonResponse
    {
        abort_unless(Actualizaciones::escritorio(), 404);

        // Cierra la app, aplica la actualización y la vuelve a abrir.
        AutoUpdater::quitAndInstall();

        return response()->json(['instalando' => true], 202);
    }

    /** Vuelve a consultar GitHub sin esperar el caché. */
    public function comprobar(): JsonResponse
    {
        Actualizaciones::olvidar();

        return response()->json([
            ...Actualizaciones::estado(),
            'descarga' => Descarga::estado(),
        ]);
    }
}
