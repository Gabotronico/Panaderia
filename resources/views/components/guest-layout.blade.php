<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Obrador') }}</title>

    @vite('resources/js/app.js')

    <style>
        /*
         * Identidad Obrador: azul noche del horno y dorado del pan.
         * Las variables permiten cambiar la marca en un solo lugar.
         */
        :root {
            --obrador-azul:       #12304f;
            --obrador-azul-claro: #1c4570;
            --obrador-oro:        #d9a441;
            --obrador-oro-claro:  #e8c684;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            background:
                radial-gradient(circle at 20% 15%, rgba(217,164,65,.10) 0%, transparent 45%),
                radial-gradient(circle at 85% 80%, rgba(217,164,65,.07) 0%, transparent 40%),
                linear-gradient(150deg, #0d2540 0%, var(--obrador-azul) 45%, #0a1e35 100%);
        }

        .marca {
            text-align: center;
            margin-bottom: 1.75rem;
        }
        .marca-nombre {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 2.6rem;
            font-weight: 700;
            letter-spacing: .14em;
            color: #fff;
            margin: .85rem 0 0;
            line-height: 1;
        }
        /* Franja dorada con la espiga al medio, como en el logo */
        .marca-franja {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .6rem;
            margin: .7rem auto .5rem;
            max-width: 17rem;
        }
        .marca-franja::before,
        .marca-franja::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--obrador-oro), transparent);
        }
        .marca-sub {
            font-size: .7rem;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: var(--obrador-oro-claro);
            margin: 0;
        }

        .auth-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 24px 60px rgba(0,0,0,.34);
            overflow: hidden;
            border: 1px solid rgba(217,164,65,.28);
        }
        .auth-header {
            background: var(--obrador-azul);
            color: #fff;
            padding: 1.1rem 1.75rem;
            text-align: center;
            border-bottom: 3px solid var(--obrador-oro);
        }
        .auth-header h3 {
            font-size: 1.05rem;
            font-weight: 600;
            letter-spacing: .06em;
            margin: 0;
        }
        .auth-header p {
            font-size: .78rem;
            color: var(--obrador-oro-claro);
            margin: .2rem 0 0;
        }
        .auth-body { padding: 2rem 2rem 1.75rem; }

        .form-label { font-weight: 600; color: #33445c; font-size: .88rem; }
        .form-control { padding: .65rem .85rem; border-color: #d8dfe8; }
        .form-control:focus {
            border-color: var(--obrador-oro);
            box-shadow: 0 0 0 .2rem rgba(217,164,65,.2);
        }
        .input-group-text { background: #f4f7fa; border-color: #d8dfe8; color: var(--obrador-azul); }

        .btn-primary {
            background: var(--obrador-azul);
            border: none;
            padding: .7rem;
            font-weight: 600;
            letter-spacing: .04em;
            transition: background .15s ease;
        }
        .btn-primary:hover,
        .btn-primary:focus { background: var(--obrador-azul-claro); }

        .form-check-input:checked {
            background-color: var(--obrador-azul);
            border-color: var(--obrador-azul);
        }

        .pie-marca {
            text-align: center;
            margin-top: 1.5rem;
            font-size: .72rem;
            color: rgba(255,255,255,.42);
        }

        /* Módulos que resume el logo */
        .modulos {
            display: flex;
            justify-content: center;
            gap: 1.6rem;
            margin-top: 1.1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(217,164,65,.22);
        }
        .modulos span {
            font-size: .62rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: rgba(232,198,132,.75);
        }
        .modulos i { display: block; font-size: .95rem; margin-bottom: .28rem; }
    </style>
</head>
<body>
    <div style="width:100%; max-width:29rem;">

        {{-- Marca: horno de ladrillo con el pan adentro, como el logo.
             Es SVG para que se vea nítido en cualquier pantalla. --}}
        <div class="marca">
            <svg width="112" height="96" viewBox="0 0 112 96" fill="none" aria-label="Obrador">
                <g fill="#d9a441">
                    {{-- Arco del horno, ladrillo por ladrillo --}}
                    <rect x="12" y="52" width="13" height="30" rx="2"/>
                    <rect x="87" y="52" width="13" height="30" rx="2"/>
                    <rect x="12" y="36" width="13" height="13" rx="2"/>
                    <rect x="87" y="36" width="13" height="13" rx="2"/>
                    <rect x="16" y="21" width="13" height="13" rx="2" transform="rotate(-22 22 27)"/>
                    <rect x="83" y="21" width="13" height="13" rx="2" transform="rotate(22 90 27)"/>
                    <rect x="30" y="11" width="13" height="13" rx="2" transform="rotate(-13 36 17)"/>
                    <rect x="69" y="11" width="13" height="13" rx="2" transform="rotate(13 76 17)"/>
                    <rect x="49" y="7" width="14" height="13" rx="2"/>
                </g>
                {{-- Pan --}}
                <path d="M32 78c0-9 10-16 24-16s24 7 24 16c0 2-1 3-3 3H35c-2 0-3-1-3-3z" fill="#e8c684"/>
                <g stroke="#c98f2e" stroke-width="2" stroke-linecap="round">
                    <path d="M44 70l4 6M56 68l4 7M68 70l4 6"/>
                </g>
                {{-- Vapor --}}
                <g stroke="#d9a441" stroke-width="2.4" stroke-linecap="round" opacity=".85">
                    <path d="M48 51c-3-4 3-6 0-10"/>
                    <path d="M56 49c-3-4 3-7 0-11"/>
                    <path d="M64 51c-3-4 3-6 0-10"/>
                </g>
            </svg>

            <h1 class="marca-nombre">OBRADOR</h1>

            <div class="marca-franja">
                <i class="fas fa-wheat-awn" style="color:var(--obrador-oro); font-size:.95rem;"></i>
            </div>

            <p class="marca-sub">Sistema de Ventas e Inventario</p>

            <div class="modulos">
                <span><i class="fas fa-clipboard-check"></i>Inventario</span>
                <span><i class="fas fa-cart-shopping"></i>Ventas</span>
                <span><i class="fas fa-chart-column"></i>Reportes</span>
            </div>
        </div>

        {{ $slot }}

        <div class="pie-marca">
            {{ config('app.name', 'Obrador') }} · Sistema de gestión para panaderías
        </div>
    </div>
</body>
</html>
