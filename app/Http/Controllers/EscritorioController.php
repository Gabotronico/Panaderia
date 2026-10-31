<?php

namespace App\Http\Controllers;

use App\Support\AltaPanaderia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pantalla de arranque de la app de escritorio.
 *
 * La app ya no trabaja contra una base local: la primera vez pide el código de
 * la panadería, lo recuerda y de ahí en adelante abre el sistema del VPS en la
 * ventana. Solo existe dentro de la app empaquetada (en el navegador da 404).
 */
class EscritorioController extends Controller
{
    public function raiz(): RedirectResponse
    {
        $codigo = $this->codigo();

        return $codigo === null
            ? redirect()->route('escritorio.mostrar')
            : redirect()->away($this->urlDe($codigo));
    }

    public function mostrar(): View
    {
        abort_unless($this->enEscritorio(), 404);

        return view('escritorio.inicio', ['codigo' => $this->codigo()]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        abort_unless($this->enEscritorio(), 404);

        $request->merge(['codigo' => strtolower(trim((string) $request->input('codigo')))]);

        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/'],
        ], [
            'codigo.required' => 'Escribí el código de tu panadería.',
            'codigo.regex' => 'El código solo tiene minúsculas, números y guiones.',
        ]);

        if (in_array($datos['codigo'], AltaPanaderia::RESERVADOS, true)) {
            return back()->withInput()->withErrors([
                'codigo' => 'Ese código está reservado por el sistema.',
            ]);
        }

        $this->recordar($datos['codigo']);

        return redirect()->away($this->urlDe($datos['codigo']));
    }

    public function olvidar(): RedirectResponse
    {
        abort_unless($this->enEscritorio(), 404);

        try {
            @unlink($this->ruta());
        } catch (\Throwable) {
            // Si no se puede borrar, igual vuelve a la pantalla.
        }

        return redirect()->route('escritorio.mostrar');
    }

    private function enEscritorio(): bool
    {
        return (bool) config('nativephp-internal.running', false);
    }

    private function ruta(): string
    {
        return storage_path((string) config('escritorio.archivo', 'escritorio-panaderia.txt'));
    }

    private function codigo(): ?string
    {
        $ruta = $this->ruta();

        if (! is_file($ruta)) {
            return null;
        }

        $codigo = trim((string) @file_get_contents($ruta));

        return preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', $codigo) === 1 ? $codigo : null;
    }

    private function recordar(string $codigo): void
    {
        @file_put_contents($this->ruta(), $codigo);
    }

    private function urlDe(string $codigo): string
    {
        return config('escritorio.url_base').'/'.$codigo;
    }
}
