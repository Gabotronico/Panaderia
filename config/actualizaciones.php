<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Actualizaciones de la app de escritorio
    |--------------------------------------------------------------------------
    |
    | Los instaladores se publican como *Releases* en el repo de abajo. La app
    | consulta la API pública de GitHub para saber cuál es la última versión:
    | la web la usa para ofrecer la descarga y el escritorio para mostrar el
    | botón de actualizar (solo si hay algo más nuevo que lo instalado).
    |
    */

    'owner' => env('GITHUB_OWNER', 'Rene-Marcelo-Ardaya'),

    'repo' => env('GITHUB_REPO', 'panaderia-releases'),

    // Solo si el repo de releases fuera privado. Es un token de lectura; nunca
    // se empaqueta en la app (está en cleanup_env_keys de config/nativephp.php).
    'token' => env('GITHUB_AUTOUPDATE_TOKEN'),

    // Cuánto se guarda la respuesta de GitHub. Con 10 minutos alcanza: una
    // versión nueva no necesita verse al instante y así no se consulta la API
    // en cada carga del sistema.
    'cache_minutos' => (int) env('ACTUALIZACIONES_CACHE_MINUTOS', 10),

    'tiempo_espera' => (int) env('ACTUALIZACIONES_TIEMPO_ESPERA', 8),
];
