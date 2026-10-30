<?php

namespace App\Http\Controllers;

use App\Support\Respaldos\Respaldo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class BackupController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index(): View
    {
        return view('system.backups', [
            'databaseSize' => Respaldo::tamanoBase(),
        ]);
    }

    public function download(): BinaryFileResponse|RedirectResponse
    {
        if (! class_exists(ZipArchive::class)) {
            return back()->with('error', 'El servidor no dispone de soporte ZIP para crear el respaldo.');
        }

        try {
            $ruta = Respaldo::exportar();
        } catch (\Throwable $exception) {
            Log::error('No se pudo generar el respaldo.', ['exception' => $exception]);

            return back()->with('error', 'No se pudo generar el respaldo: '.$exception->getMessage());
        }

        return response()->download($ruta, basename($ruta), [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    public function restore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'backup' => ['required', 'file', 'max:524288'],
            'confirmation' => ['accepted'],
        ], [
            'backup.required' => 'Seleccione un archivo de respaldo.',
            'backup.max' => 'El respaldo no puede superar 512 MB.',
            'confirmation.accepted' => 'Debe confirmar que desea reemplazar los datos actuales.',
        ]);

        if (strtolower($validated['backup']->getClientOriginalExtension()) !== 'zip') {
            return back()->with('error', 'El archivo seleccionado debe tener extensión .zip.');
        }

        if (! class_exists(ZipArchive::class)) {
            return back()->with('error', 'El servidor no dispone de soporte ZIP para restaurar respaldos.');
        }

        try {
            Respaldo::restaurar($validated['backup']->getRealPath());
        } catch (\Throwable $exception) {
            Log::error('No se pudo restaurar el respaldo.', ['exception' => $exception]);

            return back()->with('error', 'No se pudo restaurar el respaldo: '.$exception->getMessage());
        }

        // Los usuarios ahora son los del respaldo: la sesión actual no vale.
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with(
            'status',
            'Respaldo restaurado correctamente. Inicie sesión nuevamente.'
        );
    }
}
