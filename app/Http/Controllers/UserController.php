<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:ver-usuarios')->only(['index']);
        $this->middleware('permission:crear-usuarios')->only(['create', 'store']);
        $this->middleware('permission:editar-usuarios')->only(['edit', 'update']);
        $this->middleware('permission:eliminar-usuarios')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $buscar = $request->input('buscar');
        $rol    = $request->input('rol');

        $usuarios = User::with(['roles', 'almacen'])
            ->when($buscar, fn($q) => $q->where(function ($q) use ($buscar) {
                $q->where('name', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%");
            }))
            ->when($rol, fn($q) => $q->whereHas('roles', fn($r) => $r->where('name', $rol)))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $roles = Role::orderBy('name')->pluck('name');

        $resumen = [
            'total'          => User::count(),
            'administradores'=> User::role('Administrador')->count(),
            'cajeros'        => User::role('Cajero')->count(),
            'sin_almacen'    => User::role('Cajero')->whereNull('almacen_id')->count(),
        ];

        return view('usuarios.index', compact('usuarios', 'roles', 'buscar', 'rol', 'resumen'));
    }

    public function create()
    {
        return view('usuarios.create', [
            'roles'     => Role::orderBy('name')->get(),
            'almacenes' => Almacen::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|max:255|unique:users,email',
            'password'   => ['required', 'confirmed', Password::min(8)],
            'rol'        => ['required', Rule::exists('roles', 'name')],
            'almacen_id' => 'nullable|exists:almacenes,id',
        ], [], [
            'name'       => 'nombre',
            'rol'        => 'rol',
            'almacen_id' => 'almacén',
        ]);

        // El almacén solo aplica a los cajeros
        $almacenId = $datos['rol'] === 'Cajero' ? ($datos['almacen_id'] ?? null) : null;

        if ($datos['rol'] === 'Cajero' && !$almacenId) {
            return back()->withInput()->with('error',
                'Un cajero necesita un almacén asignado para poder registrar ventas.');
        }

        $usuario = User::create([
            'name'       => $datos['name'],
            'email'      => $datos['email'],
            'password'   => Hash::make($datos['password']),
            'almacen_id' => $almacenId,
        ]);

        $usuario->assignRole($datos['rol']);

        return redirect()->route('usuarios.index')
            ->with('success', "Usuario «{$usuario->name}» creado como {$datos['rol']}.");
    }

    public function edit(User $usuario)
    {
        return view('usuarios.edit', [
            'usuario'   => $usuario->load('roles', 'almacen'),
            'roles'     => Role::orderBy('name')->get(),
            'almacenes' => Almacen::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, User $usuario)
    {
        $datos = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => ['required', 'email', 'max:255', Rule::unique('users')->ignore($usuario->id)],
            'password'   => ['nullable', 'confirmed', Password::min(8)],
            'rol'        => ['required', Rule::exists('roles', 'name')],
            'almacen_id' => 'nullable|exists:almacenes,id',
        ], [], [
            'name'       => 'nombre',
            'rol'        => 'rol',
            'almacen_id' => 'almacén',
        ]);

        // No dejar al sistema sin administradores
        if ($usuario->hasRole('Administrador') && $datos['rol'] !== 'Administrador'
            && User::role('Administrador')->count() <= 1) {
            return back()->withInput()->with('error',
                'No se puede cambiar el rol: es el único administrador del sistema. ' .
                'Creá otro administrador antes de modificar este.');
        }

        $almacenId = $datos['rol'] === 'Cajero' ? ($datos['almacen_id'] ?? null) : null;

        if ($datos['rol'] === 'Cajero' && !$almacenId) {
            return back()->withInput()->with('error',
                'Un cajero necesita un almacén asignado para poder registrar ventas.');
        }

        $usuario->update([
            'name'       => $datos['name'],
            'email'      => $datos['email'],
            'almacen_id' => $almacenId,
        ]);

        if (!empty($datos['password'])) {
            $usuario->update(['password' => Hash::make($datos['password'])]);
        }

        $usuario->syncRoles([$datos['rol']]);

        return redirect()->route('usuarios.index')
            ->with('success', "Usuario «{$usuario->name}» actualizado.");
    }

    public function destroy(User $usuario)
    {
        if ($usuario->id === Auth::id()) {
            return back()->with('error', 'No podés eliminar tu propio usuario.');
        }

        if ($usuario->hasRole('Administrador') && User::role('Administrador')->count() <= 1) {
            return back()->with('error',
                'No se puede eliminar al único administrador del sistema.');
        }

        // Las ventas y cortes conservan el historial: no se borra si tiene movimientos
        if ($usuario->ventas()->exists() || $usuario->cortesCaja()->exists()) {
            return back()->with('error',
                "«{$usuario->name}» tiene ventas o cortes de caja registrados y no puede " .
                "eliminarse sin perder ese historial.");
        }

        $nombre = $usuario->name;
        $usuario->delete();

        return redirect()->route('usuarios.index')
            ->with('success', "Usuario «{$nombre}» eliminado.");
    }
}
