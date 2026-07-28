<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Configuración inicial</title>
    @vite('resources/js/app.js')
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 32px 16px;
            background:
                radial-gradient(circle at 10% 10%, rgba(129, 140, 248, .35), transparent 34%),
                radial-gradient(circle at 90% 90%, rgba(168, 85, 247, .28), transparent 32%),
                #0f172a;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        .setup-card {
            width: min(720px, 100%);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .15);
            border-radius: 22px;
            background: #fff;
            box-shadow: 0 28px 80px rgba(0, 0, 0, .35);
        }

        .setup-header {
            padding: 30px 34px;
            color: #fff;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
        }

        .setup-icon {
            width: 54px;
            height: 54px;
            display: grid;
            place-items: center;
            margin-bottom: 18px;
            border-radius: 15px;
            background: rgba(255, 255, 255, .16);
            font-size: 24px;
        }

        .setup-body { padding: 32px 34px 36px; }
        .form-label { font-weight: 600; color: #334155; }
        .form-control { min-height: 46px; border-radius: 10px; }
        .form-control:focus { border-color: #6366f1; box-shadow: 0 0 0 .2rem rgba(99, 102, 241, .15); }
        .btn-setup {
            min-height: 48px;
            border: 0;
            border-radius: 11px;
            color: #fff;
            font-weight: 700;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
        }
        .security-note {
            padding: 12px 14px;
            border-radius: 10px;
            color: #475569;
            background: #f1f5f9;
            font-size: .82rem;
        }
    </style>
</head>
<body>
    <main class="setup-card">
        <header class="setup-header">
            <div class="setup-icon"><i class="fas fa-bread-slice"></i></div>
            <h1 class="h3 fw-bold mb-2">Configuración inicial</h1>
            <p class="mb-0 opacity-75">Configure el negocio y cree la cuenta administradora de esta PC.</p>
        </header>

        <section class="setup-body">
            @if (session('error'))
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-rotate me-2"></i>{{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <div class="fw-semibold mb-1">Revise la información ingresada:</div>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('setup.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="business_name" class="form-label">Nombre del negocio</label>
                    <input id="business_name" name="business_name" type="text"
                           class="form-control @error('business_name') is-invalid @enderror"
                           value="{{ old('business_name', 'Mi Panadería') }}" maxlength="100" required autofocus>
                    @error('business_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Nombre del administrador</label>
                        <input id="name" name="name" type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" maxlength="100" autocomplete="name" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Correo electrónico</label>
                        <input id="email" name="email" type="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}" maxlength="150" autocomplete="username" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label">Contraseña</label>
                        <input id="password" name="password" type="password"
                               class="form-control @error('password') is-invalid @enderror"
                               autocomplete="new-password" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                        <input id="password_confirmation" name="password_confirmation" type="password"
                               class="form-control" autocomplete="new-password" required>
                    </div>
                </div>

                <div class="security-note my-4">
                    <i class="fas fa-shield-halved me-2 text-primary"></i>
                    Use al menos 10 caracteres, incluyendo mayúsculas, minúsculas y números.
                    Esta cuenta tendrá acceso completo al sistema y a los respaldos.
                </div>

                <button type="submit" class="btn btn-setup w-100">
                    <i class="fas fa-check me-2"></i>Finalizar configuración
                </button>
            </form>
        </section>
    </main>
</body>
</html>
