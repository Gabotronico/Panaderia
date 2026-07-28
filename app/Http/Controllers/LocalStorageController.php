<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LocalStorageController extends Controller
{
    public function __invoke(string $path): BinaryFileResponse
    {
        $root = realpath(storage_path('app/public'));
        $file = $root ? realpath($root.DIRECTORY_SEPARATOR.$path) : false;

        abort_unless(
            $root
            && $file
            && File::isFile($file)
            && str_starts_with(strtolower($file), strtolower($root.DIRECTORY_SEPARATOR)),
            404
        );

        return response()->file($file, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
