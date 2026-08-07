<x-guest-layout>
    {{-- La marca ya la muestra el layout; acá va solo el formulario. --}}
    <div class="auth-card">
        <div class="auth-header">
            <h3>Iniciar Sesión</h3>
            <p>Ingresá con tu usuario del sistema</p>
        </div>

                    <div class="auth-body">
                        <!-- Session Status -->
                        @if (session('status'))
                            <div class="alert alert-success mb-4">
                                {{ session('status') }}
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-warning mb-4" role="alert">
                                <i class="fas fa-rotate me-2"></i>{{ session('error') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <!-- Email Address -->
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-2"></i>Correo Electrónico
                                </label>
                                <input id="email" 
                                       type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       name="email" 
                                       value="{{ old('email') }}" 
                                       required 
                                       autofocus 
                                       autocomplete="username"
                                       placeholder="correo@ejemplo.com">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Password -->
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Contraseña
                                </label>
                                <input id="password" 
                                       type="password" 
                                       class="form-control @error('password') is-invalid @enderror" 
                                       name="password" 
                                       required 
                                       autocomplete="current-password"
                                       placeholder="••••••••">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Remember Me -->
                            <div class="mb-3 form-check">
                                <input type="checkbox" 
                                       class="form-check-input" 
                                       id="remember_me" 
                                       name="remember">
                                <label class="form-check-label" for="remember_me">
                                    Recordarme
                                </label>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                                </button>
                            </div>

                            <div class="text-center mt-3">
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="text-muted">
                                        ¿Olvidaste tu contraseña?
                                    </a>
                                @endif
                            </div>
                               <div class="text-center mt-3">
                                   <small class="text-muted">
                                     <i class="fas fa-info-circle me-1"></i>
                                        Contacte al administrador para obtener sus credenciales de acceso
                                     </small>
                                </div>
                        </form>
    </div>
    </div>

    <div class="text-center mt-3">
        <small class="text-white-50">
            Sistema desarrollado por
            <a href="https://www.linkedin.com/company/inc-groupbo/"
               target="_blank" rel="noopener noreferrer" class="text-white text-decoration-none fw-semibold">
                INC Group
            </a>
        </small>
    </div>
</x-guest-layout>
