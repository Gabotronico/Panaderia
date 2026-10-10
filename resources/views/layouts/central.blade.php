<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') · Obrador</title>

    @vite('resources/js/app.js')

    {{-- Misma paleta que la aplicación de cada panadería (layouts/app). --}}
    <style>
        :root {
            --primary: #12304f;
            --primary-dark: #0d2540;
            --oro: #d9a441;
            --bg-main: #f1f5f9;
            --border: #e2e8f0;
            --text-heading: #0f172a;
            --text-muted: #64748b;
        }
        body { background: var(--bg-main); font-family: 'Inter', system-ui, sans-serif; color: #374151; }
        .central-bar { background: var(--primary-dark); border-bottom: 3px solid var(--oro); }
        .central-bar .marca { color: #fff; font-weight: 800; letter-spacing: .12em; text-decoration: none; }
        .central-bar .marca small { color: var(--oro); font-weight: 600; letter-spacing: .08em; font-size: .7rem; }
        .central-bar .usuario { color: #9db2ca; font-size: .875rem; }
        .titulo { color: var(--text-heading); font-weight: 700; }
        .card { border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.07); }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover, .btn-primary:focus { background: var(--primary-dark); border-color: var(--primary-dark); }
        .table > :not(caption) > * > * { padding: .75rem 1rem; }
        code.id { color: var(--primary); background: #dce9f8; padding: .1rem .4rem; border-radius: 4px; }
    </style>
</head>
<body>
    <nav class="central-bar py-3 mb-4">
        <div class="container d-flex align-items-center justify-content-between gap-3">
            <a href="{{ route('central.panaderias.index') }}" class="marca">
                OBRADOR <small class="d-none d-sm-inline ms-2">PANEL DE ADMINISTRACIÓN</small>
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="usuario d-none d-md-inline">
                    <i class="fas fa-user-shield me-1"></i>{{ auth('superadmin')->user()->name }}
                </span>
                <form method="POST" action="{{ route('central.logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-light">
                        <i class="fas fa-right-from-bracket me-1"></i>Salir
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <main class="container pb-5">
        <x-alerts />
        @yield('content')
    </main>
</body>
</html>
