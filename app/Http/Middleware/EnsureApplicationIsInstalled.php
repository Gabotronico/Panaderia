<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicationIsInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('setup.*') || ! Schema::hasTable('users')) {
            return $next($request);
        }

        if (! User::query()->exists()) {
            return redirect()->route('setup.create');
        }

        return $next($request);
    }
}
