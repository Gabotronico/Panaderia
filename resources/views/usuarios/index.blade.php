@extends('layouts.app')
@section('page-title', 'Usuarios')
@section('content')

<x-page-header title="Usuarios del Sistema" icon="user-shield"
               subtitle="Administradores y cajeros con acceso al sistema">
    @can('crear-usuarios')
    <a href="{{ route('usuarios.create') }}" class="btn btn-primary">
        <i class="fas fa-user-plus me-1"></i>Nuevo Usuario
    </a>
    @endcan
</x-page-header>

<x-alerts />

{{-- Resumen --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <x-stat-card label="Total de usuarios" :value="$resumen['total']"
                     icon="users" variant="primary" sub="Con acceso al sistema" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat-card label="Administradores" :value="$resumen['administradores']"
                     icon="user-shield" variant="info" sub="Acceso completo" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat-card label="Cajeros" :value="$resumen['cajeros']"
                     icon="cash-register" variant="success" sub="Ventas y cortes de caja" />
    </div>
    <div class="col-6 col-lg-3">
        <x-stat-card label="Cajeros sin almacén" :value="$resumen['sin_almacen']"
                     icon="triangle-exclamation"
                     :variant="$resumen['sin_almacen'] > 0 ? 'warning' : 'neutral'"
                     sub="No pueden vender hasta asignarles uno" />
    </div>
</div>

{{-- Filtros --}}
<form method="GET" action="{{ route('usuarios.index') }}" class="filter-bar">
    <div class="row g-2 align-items-center">
        <div class="col-sm-5 col-md-4">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text" name="buscar" class="form-control border-start-0 ps-0"
                       placeholder="Nombre o correo…" value="{{ $buscar }}" autocomplete="off">
            </div>
        </div>
        <div class="col-sm-4 col-md-3">
            <select name="rol" class="form-select">
                <option value="">— Todos los roles —</option>
                @foreach($roles as $r)
                    <option value="{{ $r }}" @selected($rol === $r)>{{ $r }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter me-1"></i>Filtrar
            </button>
            @if($buscar || $rol)
                <a href="{{ route('usuarios.index') }}" class="btn btn-light border">Limpiar</a>
            @endif
        </div>
        <div class="col-auto ms-auto text-muted" style="font-size:.82rem;">
            {{ $usuarios->total() }} usuario(s)
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Correo</th>
                        <th class="text-center">Rol</th>
                        <th>Almacén asignado</th>
                        <th>Alta</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usuarios as $u)
                    @php
                        $esAdmin = $u->hasRole('Administrador');
                        $soyYo   = $u->id === auth()->id();
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-inicial {{ $esAdmin ? 'avatar-admin' : 'avatar-cajero' }}">
                                    {{ strtoupper(mb_substr($u->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold text-dark">
                                        {{ $u->name }}
                                        @if($soyYo)
                                            <span class="badge bg-light text-muted border ms-1"
                                                  style="font-size:.62rem;">vos</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted">{{ $u->email }}</td>
                        <td class="text-center">
                            @forelse($u->roles as $r)
                                <span class="badge bg-{{ $r->name === 'Administrador' ? 'info' : 'success' }}">
                                    {{ $r->name }}
                                </span>
                            @empty
                                <span class="badge bg-danger">Sin rol</span>
                            @endforelse
                        </td>
                        <td>
                            @if($u->almacen)
                                <i class="fas fa-store text-muted me-1"></i>{{ $u->almacen->nombre }}
                            @elseif($esAdmin)
                                <span class="text-muted">— no aplica —</span>
                            @else
                                <span class="badge bg-warning text-dark">Sin asignar</span>
                            @endif
                        </td>
                        <td class="text-muted" style="font-size:.82rem;">
                            {{ $u->created_at?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td class="text-end text-nowrap">
                            @can('editar-usuarios')
                            <a href="{{ route('usuarios.edit', $u) }}" class="btn btn-warning btn-sm" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            @endcan
                            @can('eliminar-usuarios')
                                @unless($soyYo)
                                <form action="{{ route('usuarios.destroy', $u) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar el usuario «{{ $u->name }}»?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endunless
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-0">
                            <x-empty-state icon="user-slash"
                                           title="No hay usuarios que coincidan"
                                           message="Ajustá los filtros o creá un usuario nuevo.">
                                @can('crear-usuarios')
                                <a href="{{ route('usuarios.create') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-user-plus me-1"></i>Nuevo Usuario
                                </a>
                                @endcan
                            </x-empty-state>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($usuarios->hasPages())
        <div class="d-flex justify-content-center py-3 border-top">
            {{ $usuarios->links() }}
        </div>
        @endif
    </div>
</div>

@push('styles')
<style>
    .avatar-inicial {
        width: 34px; height: 34px;
        border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: .72rem; font-weight: 700; color: #fff;
        flex-shrink: 0;
    }
    .avatar-admin  { background: linear-gradient(135deg, #0284c7, #38bdf8); }
    .avatar-cajero { background: linear-gradient(135deg, #16a34a, #22c55e); }
</style>
@endpush
@endsection
