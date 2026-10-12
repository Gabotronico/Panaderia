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
         * Pantalla de ingreso: la panadería de noche a la izquierda, la marca
         * al centro y la tarjeta de acceso a la derecha. Azul noche del horno
         * y dorado del pan; las variables permiten cambiar la marca en un
         * solo lugar.
         */
        :root {
            --obrador-noche:     #071526;
            --obrador-azul:      #0c2138;
            --obrador-oro:       #e0a94a;
            --obrador-oro-claro: #f3cd85;
            --obrador-texto:     #dbe4ef;
            --obrador-tenue:     #93a5ba;
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--obrador-texto);
            background:
                radial-gradient(circle at 78% 12%, rgba(224,169,74,.08) 0%, transparent 42%),
                radial-gradient(circle at 60% 95%, rgba(224,169,74,.06) 0%, transparent 38%),
                linear-gradient(120deg, var(--obrador-noche) 0%, var(--obrador-azul) 55%, #081a2e 100%);
        }

        /* La foto ocupa el borde izquierdo y se funde con el fondo. */
        .foto {
            position: fixed;
            inset: 0 auto 0 0;
            width: min(36vw, 620px);
            background: url('{{ asset('images/login-panaderia.jpg') }}') center / cover no-repeat;
            -webkit-mask-image: linear-gradient(90deg, #000 55%, transparent 100%);
                    mask-image: linear-gradient(90deg, #000 55%, transparent 100%);
            opacity: .92;
        }

        .escena {
            position: relative;
            min-height: 100vh;
            display: grid;
            grid-template-columns: min(30vw, 520px) minmax(0, 1fr) minmax(360px, 34rem);
            align-items: center;
            gap: clamp(1.5rem, 4vw, 4.5rem);
            padding: 2.5rem clamp(1.25rem, 4vw, 4.5rem);
        }

        /* ── Marca ───────────────────────────────────────────────── */
        .presentacion { grid-column: 2; max-width: 27rem; justify-self: center; }

        .marca-nombre {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: clamp(2.6rem, 4.2vw, 3.9rem);
            font-weight: 700;
            letter-spacing: .14em;
            color: #fff;
            margin: 0;
            line-height: 1;
            text-align: center;
        }
        /* Franja dorada con la espiga al medio, como en el logo */
        .marca-franja {
            display: flex;
            align-items: center;
            gap: .7rem;
            margin: .85rem 0 .6rem;
            color: var(--obrador-oro);
        }
        .marca-franja::before,
        .marca-franja::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--obrador-oro), transparent);
        }
        .marca-sub {
            font-size: .8rem;
            font-weight: 700;
            letter-spacing: .26em;
            text-transform: uppercase;
            text-align: center;
            color: var(--obrador-oro-claro);
            margin: 0;
        }

        .lema {
            font-family: 'Kaushan Script', cursive;
            line-height: 1.2;
            text-align: center;
            transform: rotate(-4deg);
        }
        .lema::after {
            content: '';
            display: block;
            width: 7rem;
            height: .28rem;
            margin: .45rem auto 0;
            border-radius: 50%;
            background: linear-gradient(90deg, transparent, var(--obrador-oro), transparent);
        }
        .lema-marca { font-size: 1.75rem; color: #fff; margin: 1.6rem 0 2.2rem; }
        .lema-cierre { font-size: 1.45rem; color: var(--obrador-oro-claro); margin: 2.4rem 0 0; }

        .ventajas { list-style: none; margin: 0; padding: 0; display: grid; gap: 1.5rem; }
        .ventajas li { display: flex; align-items: center; gap: 1.1rem; }
        .ventajas .icono {
            flex: 0 0 auto;
            width: 3.6rem;
            height: 3.6rem;
            border-radius: 50%;
            border: 2px solid var(--obrador-oro);
            display: grid;
            place-items: center;
            color: var(--obrador-oro);
            font-size: 1.35rem;
        }
        .ventajas strong { display: block; color: #fff; font-size: 1.02rem; }
        .ventajas span { font-size: .9rem; color: var(--obrador-texto); line-height: 1.45; }

        /* ── Tarjeta de acceso ───────────────────────────────────── */
        .acceso { grid-column: 3; width: 100%; }

        .auth-card {
            background: rgba(9, 24, 43, .86);
            -webkit-backdrop-filter: blur(6px);
                    backdrop-filter: blur(6px);
            border: 1.5px solid rgba(224,169,74,.75);
            border-radius: 22px;
            box-shadow: 0 30px 70px rgba(0,0,0,.45);
            padding: clamp(1.75rem, 3vw, 3rem) clamp(1.4rem, 2.6vw, 2.75rem);
            color: var(--obrador-texto);
        }
        .auth-header { text-align: center; margin-bottom: 2rem; }
        .auth-header h3 {
            font-size: clamp(1.6rem, 2.3vw, 2.1rem);
            font-weight: 800;
            color: #fff;
            margin: 0 0 .5rem;
        }
        .auth-header p { font-size: .98rem; color: var(--obrador-texto); margin: 0; }

        .auth-card .form-label { font-weight: 500; color: #fff; font-size: .98rem; margin-bottom: .6rem; }
        .auth-card .form-label i { color: var(--obrador-oro); }
        .auth-card .form-control {
            background: rgba(255,255,255,.035);
            border: 1px solid rgba(219,228,239,.32);
            border-radius: 12px;
            color: #fff;
            padding: .95rem 1.1rem;
        }
        .auth-card .form-control::placeholder { color: var(--obrador-tenue); }
        .auth-card .form-control:focus {
            background: rgba(255,255,255,.06);
            border-color: var(--obrador-oro);
            box-shadow: 0 0 0 .2rem rgba(224,169,74,.22);
            color: #fff;
        }
        /* El autocompletado del navegador pinta el campo de blanco. */
        .auth-card .form-control:-webkit-autofill {
            -webkit-text-fill-color: #fff;
            -webkit-box-shadow: 0 0 0 100px #10263f inset;
            caret-color: #fff;
        }
        .auth-card .form-control.is-invalid { border-color: #f08a8a; }
        .auth-card .invalid-feedback { color: #f6a5a5; }

        .campo-clave { position: relative; }
        .campo-clave .form-control { padding-right: 3.2rem; }
        .ver-clave {
            position: absolute;
            inset: 0 .35rem 0 auto;
            width: 2.6rem;
            border: 0;
            background: none;
            color: var(--obrador-texto);
        }
        .ver-clave:hover, .ver-clave:focus-visible { color: var(--obrador-oro-claro); }

        .auth-card .form-check-input {
            width: 1.25rem;
            height: 1.25rem;
            margin-top: .1rem;
            background-color: transparent;
            border: 1.5px solid rgba(219,228,239,.5);
        }
        .auth-card .form-check-input:checked {
            background-color: var(--obrador-oro);
            border-color: var(--obrador-oro);
        }
        .auth-card .form-check-input:focus { box-shadow: 0 0 0 .2rem rgba(224,169,74,.22); }
        .auth-card .form-check-label { color: #fff; padding-left: .25rem; }

        .auth-card a { color: var(--obrador-oro-claro); text-decoration: none; }
        .auth-card a:hover { color: #fff; text-decoration: underline; }
        .auth-card .text-muted { color: var(--obrador-tenue) !important; }

        .auth-card .btn-primary {
            background: linear-gradient(180deg, #f6cf85 0%, var(--obrador-oro) 100%);
            border: 0;
            border-radius: 12px;
            color: #1b1407;
            font-weight: 700;
            padding: .95rem;
            transition: filter .15s ease, transform .15s ease;
        }
        .auth-card .btn-primary:hover,
        .auth-card .btn-primary:focus-visible { filter: brightness(1.07); color: #1b1407; }
        .auth-card .btn-primary:active { transform: translateY(1px); color: #1b1407; }

        .pie-tarjeta {
            margin-top: 2rem;
            padding-top: 1.4rem;
            border-top: 1px solid rgba(219,228,239,.16);
            text-align: center;
            font-size: .8rem;
            color: var(--obrador-texto);
        }
        .pie-tarjeta a { color: #fff; font-weight: 700; }

        /* Vistas que todavía traen su propia grilla de Bootstrap. */
        .acceso .container, .acceso .row, .acceso [class*="col-"] {
            width: 100%; max-width: none; flex: none; margin: 0; padding: 0;
        }

        /* ── Pantallas medianas y chicas ─────────────────────────── */
        /* Tres columnas no entran: la foto queda de fondo, atenuada, y la
           marca se corre a su derecha. */
        @media (max-width: 1499.98px) {
            .foto { width: 28vw; opacity: .5; }
            .escena { grid-template-columns: minmax(0, 1fr) minmax(340px, 30rem); }
            .presentacion { grid-column: 1; justify-self: end; max-width: 24rem; }
            .acceso { grid-column: 2; }
        }
        @media (max-width: 899.98px) {
            .foto { width: 100%; opacity: .16; -webkit-mask-image: none; mask-image: none; }
            .escena { grid-template-columns: minmax(0, 32rem); justify-content: center; gap: 1.75rem; }
            .presentacion, .acceso { grid-column: 1; }
            .presentacion { max-width: none; justify-self: stretch; }
            .ventajas, .lema-cierre { display: none; }
            .lema-marca { margin: 1.1rem 0 0; font-size: 1.4rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            .auth-card .btn-primary { transition: none; }
        }
    </style>
</head>
<body>
    <div class="foto" aria-hidden="true"></div>

    <main class="escena">
        <section class="presentacion" aria-label="Obrador">
            <h1 class="marca-nombre">OBRADOR</h1>
            <div class="marca-franja"><i class="fas fa-wheat-awn"></i></div>
            <p class="marca-sub">Sistema de Ventas e Inventario</p>

            <p class="lema lema-marca">Tu panadería,<br>en buenas manos</p>

            <ul class="ventajas">
                <li>
                    <span class="icono"><i class="fas fa-store"></i></span>
                    <div>
                        <strong>Control de ventas</strong>
                        <span>Gestiona tus ventas diarias de forma rápida y segura.</span>
                    </div>
                </li>
                <li>
                    <span class="icono"><i class="fas fa-box-open"></i></span>
                    <div>
                        <strong>Inventario en tiempo real</strong>
                        <span>Lleva el control de tus insumos y productos.</span>
                    </div>
                </li>
                <li>
                    <span class="icono"><i class="fas fa-chart-column"></i></span>
                    <div>
                        <strong>Reportes detallados</strong>
                        <span>Toma mejores decisiones con información al instante.</span>
                    </div>
                </li>
            </ul>

            <p class="lema lema-cierre">Más orden, más sabor,<br>mejores resultados.</p>
        </section>

        <section class="acceso">
            {{ $slot }}
        </section>
    </main>
</body>
</html>
