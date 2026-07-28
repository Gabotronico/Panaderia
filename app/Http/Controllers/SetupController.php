<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\User;
use Database\Seeders\CategoriaSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        $user = DB::transaction(function () use ($validated): User {
            app(RoleSeeder::class)->run();
            app(CategoriaSeeder::class)->run();

            AppSetting::write('business_name', $validated['business_name']);
            AppSetting::write('installed_at', now()->toIso8601String());

            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            $user->assignRole('Administrador');

            return $user;
        });

        config(['app.name' => $validated['business_name']]);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')
            ->with('success', 'La aplicación quedó configurada correctamente.');
    }
}
