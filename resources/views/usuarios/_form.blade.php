@php
    $u          = $usuario ?? null;
    $esEdicion  = $u !== null;
    $rolActual  = old('rol', $u?->roles->first()?->name ?? 'Cajero');
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nombre completo <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $u?->name) }}" placeholder="Ej: María Fernández" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Correo electrónico <span class="text-danger">*</span></label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $u?->email) }}" placeholder="usuario@panaderialuna.com" required>
        <div class="form-text">Con este correo inicia sesión.</div>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <hr class="my-2">
        <div class="section-title">Rol y permisos</div>
    </div>

    <div class="col-12">
        <div class="row g-3">
            @foreach($roles as $r)
            @php
                $esAdmin = $r->name === 'Administrador';
            @endphp
            <div class="col-md-6">
                <label class="rol-card {{ $rolActual === $r->name ? 'rol-card-activa' : '' }}"
                       for="rol_{{ $r->id }}">
                    <input type="radio" name="rol" id="rol_{{ $r->id }}" value="{{ $r->name }}"
                           class="form-check-input rol-radio" @checked($rolActual === $r->name)
                           onchange="cambiarRol(this.value)">
                    <div class="ms-2">
                        <div class="fw-semibold">
                            <i class="fas fa-{{ $esAdmin ? 'user-shield text-info' : 'cash-register text-success' }} me-1"></i>
                            {{ $r->name }}
                        </div>
                        <div class="text-muted" style="font-size:.78rem;">
                            @if($esAdmin)
                                Acceso completo: productos, insumos, recursos humanos,
                                gastos, reportes y creación de usuarios.
                            @else
                                Solo registra ventas y maneja el corte de caja de su almacén.
                            @endif
                        </div>
                    </div>
                </label>
            </div>
            @endforeach
        </div>
        @error('rol')<div class="text-danger mt-1" style="font-size:.82rem;">{{ $message }}</div>@enderror
    </div>

    {{-- El almacén solo tiene sentido para cajeros --}}
    <div class="col-md-6" id="bloqueAlmacen">
        <label class="form-label">Almacén asignado <span class="text-danger">*</span></label>
        <select name="almacen_id" class="form-select @error('almacen_id') is-invalid @enderror">
            <option value="">— Seleccionar almacén —</option>
            @foreach($almacenes as $a)
                <option value="{{ $a->id }}" @selected(old('almacen_id', $u?->almacen_id) == $a->id)>
                    {{ $a->nombre }}
                </option>
            @endforeach
        </select>
        <div class="form-text">El cajero vende contra el stock de este almacén.</div>
        @error('almacen_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <hr class="my-2">
        <div class="section-title">
            Contraseña
            @if($esEdicion)
                <span class="text-muted fw-normal text-lowercase">— dejala vacía para no cambiarla</span>
            @endif
        </div>
    </div>

    <div class="col-md-6">
        <label class="form-label">
            Contraseña @unless($esEdicion)<span class="text-danger">*</span>@endunless
        </label>
        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
               autocomplete="new-password" {{ $esEdicion ? '' : 'required' }}>
        <div class="form-text">Mínimo 8 caracteres.</div>
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">
            Repetir contraseña @unless($esEdicion)<span class="text-danger">*</span>@endunless
        </label>
        <input type="password" name="password_confirmation" class="form-control"
               autocomplete="new-password" {{ $esEdicion ? '' : 'required' }}>
    </div>
</div>

@push('styles')
<style>
    .rol-card {
        display: flex;
        align-items: flex-start;
        padding: 12px 14px;
        border: 1px solid var(--border);
        border-radius: 10px;
        cursor: pointer;
        transition: border-color .15s, background .15s;
        height: 100%;
        margin: 0;
    }
    .rol-card:hover      { border-color: #a5b4fc; background: #fafbff; }
    .rol-card-activa     { border-color: var(--primary); background: #f5f6ff; }
    .rol-radio           { margin-top: 3px; flex-shrink: 0; }
</style>
@endpush

@push('scripts')
<script>
function cambiarRol(rol) {
    document.querySelectorAll('.rol-card').forEach(c => c.classList.remove('rol-card-activa'));
    document.querySelector(`.rol-radio[value="${rol}"]`)?.closest('.rol-card')
            ?.classList.add('rol-card-activa');

    // El almacén solo aplica a cajeros
    const bloque = document.getElementById('bloqueAlmacen');
    const select = bloque.querySelector('select');
    if (rol === 'Cajero') {
        bloque.style.display = '';
        select.required = true;
    } else {
        bloque.style.display = 'none';
        select.required = false;
        select.value = '';
    }
}

cambiarRol(@json($rolActual));
</script>
@endpush
