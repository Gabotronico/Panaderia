<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Nombre único ignorando mayúsculas, acentos de capitalización y espacios.
 *
 * La regla `unique` de Laravel compara con `=`, y en SQLite eso distingue
 * mayúsculas: "Harina" y "harina" entraban las dos como registros distintos.
 *
 * La comparación se hace en PHP y no con LOWER() del motor porque LOWER() de
 * SQLite solo baja caracteres ASCII: "PIÑA" quedaría como "piÑa" y volvería a
 * colarse el duplicado. Se traen los nombres y se comparan con mb_strtolower,
 * que sí entiende ñ y acentos. Para un catálogo de panadería —decenas o pocos
 * cientos de filas— el costo es irrelevante.
 */
class NombreUnico implements ValidationRule
{
    public function __construct(
        private string $tabla,
        private ?int $ignorarId = null,
        private string $mensaje = 'Ya existe un registro con ese nombre.',
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $buscado = $this->normalizar((string) $value);

        $duplicado = DB::table($this->tabla)
            ->when($this->ignorarId !== null, fn ($q) => $q->where('id', '!=', $this->ignorarId))
            ->pluck('nombre')
            ->first(fn ($nombre) => $this->normalizar((string) $nombre) === $buscado);

        if ($duplicado !== null) {
            $fail(str_replace(':existente', $duplicado, $this->mensaje));
        }
    }

    private function normalizar(string $texto): string
    {
        // Varios espacios seguidos también cuentan como el mismo nombre:
        // "Pan  integral" y "Pan integral" son el mismo producto.
        $texto = trim($texto);

        // Con /u, preg_replace devuelve null si el texto no es UTF-8 válido
        // (un pegado raro, un teclado viejo). En ese caso se colapsa sin
        // Unicode en lugar de dejar pasar un null.
        $colapsado = preg_replace('/\s+/u', ' ', $texto) ?? preg_replace('/\s+/', ' ', $texto);

        return mb_strtolower($colapsado);
    }
}
