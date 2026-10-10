<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\InstalacionInicial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (User::query()->exists()) {
            return redirect()->route('login');
        }

        return view('setup.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if(User::query()->exists(), 403, 'La configuración inicial ya fue completada.');

        $validated = $request->validate([
            'business_name' => ['required', 'string', 'min:2', 'max:100'],
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
        ]);

        $user = InstalacionInicial::ejecutar(
            $validated['business_name'],
            $validated['name'],
            $validated['email'],
            $validated['password'],
        );

        config(['app.name' => $validated['business_name']]);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')
            ->with('success', 'La aplicación quedó configurada correctamente.');
    }
}
