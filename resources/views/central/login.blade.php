<x-guest-layout>
    <div class="auth-card">
        <div class="auth-header">
            <h3>Panel de administración</h3>
            <p>Acceso para administrar las panaderías</p>
        </div>

        <div class="auth-body">
            @if (session('error'))
                <div class="alert alert-warning mb-4" role="alert">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('central.login') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope me-2"></i>Correo electrónico
                    </label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           class="form-control @error('email') is-invalid @enderror"
                           required autofocus autocomplete="username">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-2"></i>Contraseña
                    </label>
                    <input id="password" type="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           required autocomplete="current-password">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Recordarme</label>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-right-to-bracket me-2"></i>Ingresar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
