<x-guest-layout>
    <div class="auth-card">
        <div class="auth-header">
            <h3>¿En qué panadería entrás?</h3>
            <p>{{ $persona->email }}</p>
        </div>

        <div class="auth-body">
            @error('tenant')
                <div class="alert alert-warning mb-3" role="alert">{{ $message }}</div>
            @enderror

            @foreach ($panaderias as $panaderia)
                <form method="POST" action="{{ route('entrada.seleccionar') }}" class="mb-2">
                    @csrf
                    <input type="hidden" name="tenant" value="{{ $panaderia['tenant_id'] }}">

                    <button type="submit"
                            class="btn btn-light border w-100 text-start py-2 px-3"
                            @disabled(! $panaderia['entra'])>
                        <span class="fw-semibold d-block">{{ $panaderia['nombre'] }}</span>
                        <span class="small text-muted d-block">
                            {{ $panaderia['rol'] ?? 'Usuario' }} · {{ $panaderia['tenant_id'] }}
                        </span>
                        @unless ($panaderia['entra'])
                            <span class="small text-danger d-block">Acceso suspendido</span>
                        @endunless
                    </button>
                </form>
            @endforeach

            <form method="POST" action="{{ route('entrada.salir') }}" class="text-center mt-3">
                @csrf
                <button type="submit" class="btn btn-link btn-sm text-muted">Usar otra cuenta</button>
            </form>
        </div>
    </div>
</x-guest-layout>
