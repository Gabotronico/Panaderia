<x-guest-layout>
    {{-- La marca y la foto las pone el layout; acá va solo la tarjeta. --}}
    <div class="auth-card">
        <div class="auth-header">
            <h3>Iniciar Sesión</h3>
            <p>Ingresa con tu usuario del sistema</p>
        </div>

        @if (session('status'))
            <div class="alert alert-success mb-4">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-warning mb-4" role="alert">
                <i class="fas fa-rotate me-2"></i>{{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-4">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope me-2"></i>Correo Electrónico
                </label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       class="form-control @error('email') is-invalid @enderror"
                       required autofocus autocomplete="username" placeholder="correo@ejemplo.com">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">
                    <i class="fas fa-lock me-2"></i>Contraseña
                </label>
                <div class="campo-clave">
                    <input id="password" type="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           required autocomplete="current-password" placeholder="Ingresa tu contraseña">
                    <button type="button" class="ver-clave" id="ver-clave"
                            aria-label="Mostrar la contraseña" aria-pressed="false">
                        <i class="fas fa-eye-slash" aria-hidden="true"></i>
                    </button>
                </div>
                @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
                <div class="form-check mb-0">
                    <input type="checkbox" class="form-check-input" id="remember_me" name="remember">
                    <label class="form-check-label" for="remember_me">Recordar sesión</label>
                </div>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="small">¿Olvidaste tu contraseña?</a>
                @else
                    {{-- Sin correo configurado no hay recuperación automática. --}}
                    <span class="small text-muted">¿La olvidaste? Pedísela a tu administrador</span>
                @endif
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-right-to-bracket me-2"></i>Iniciar Sesión
                </button>
            </div>
        </form>

        <div class="pie-tarjeta">
            <i class="fas fa-shield-halved me-2"></i>Sistema desarrollado por
            <a href="https://www.linkedin.com/company/inc-groupbo/" target="_blank" rel="noopener noreferrer">INC Group</a>
        </div>
    </div>

    <script>
        // Mostrar u ocultar la contraseña mientras se escribe.
        document.getElementById('ver-clave').addEventListener('click', function () {
            const campo = document.getElementById('password');
            const visible = campo.type === 'text';

            campo.type = visible ? 'password' : 'text';
            this.setAttribute('aria-pressed', String(!visible));
            this.setAttribute('aria-label', visible ? 'Mostrar la contraseña' : 'Ocultar la contraseña');
            this.firstElementChild.className = visible ? 'fas fa-eye-slash' : 'fas fa-eye';
        });
    </script>
</x-guest-layout>
