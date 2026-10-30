<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso suspendido · {{ $negocio }}</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: #0f1b2d;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: #0f1b2d;
        }
        .tarjeta {
            width: 100%;
            max-width: 460px;
            background: #fff;
            border-radius: 18px;
            padding: 32px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, .35);
            text-align: center;
        }
        .marca {
            font-size: 12px;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: #d4a017;
            font-weight: 700;
            margin: 0 0 20px;
        }
        h1 { font-size: 20px; margin: 0 0 10px; }
        p { margin: 0 0 10px; color: #4b5563; font-size: 14px; line-height: 1.6; }
        .negocio { font-weight: 600; color: #0f1b2d; }
        .nota {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <main class="tarjeta">
        <p class="marca">Obrador</p>
        <h1>El acceso está suspendido</h1>
        <p>
            La licencia de <span class="negocio">{{ $negocio }}</span> no está activa.
            Los datos siguen guardados; se vuelven a habilitar apenas se regularice.
        </p>
        <p>Comunicate con INC Group para reactivar el servicio.</p>
        <p class="nota">
            Estado: {{ $estado === 'revoked' ? 'licencia revocada' : 'acceso suspendido' }}
        </p>
    </main>
</body>
</html>
