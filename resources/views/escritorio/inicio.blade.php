<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Obrador</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: radial-gradient(circle at 20% 10%, #1b3a5c 0%, #0b1a2b 55%, #060e18 100%);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: #0f1b2d;
            -webkit-user-select: none;
            user-select: none;
        }
        .tarjeta {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 20px;
            padding: 34px 32px 28px;
            box-shadow: 0 28px 70px rgba(0, 0, 0, .45);
        }
        .marca {
            text-align: center;
            font-size: 11px;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: #c9922a;
            font-weight: 700;
            margin: 0 0 6px;
        }
        h1 {
            text-align: center;
            font-size: 21px;
            margin: 0 0 6px;
        }
        .bajada {
            text-align: center;
            color: #6b7280;
            font-size: 13px;
            line-height: 1.5;
            margin: 0 0 24px;
        }
        label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 6px;
        }
        input {
            width: 100%;
            padding: 11px 13px;
            border: 1px solid #d5dee7;
            border-radius: 10px;
            font-size: 15px;
            color: #0f1b2d;
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        input:focus {
            border-color: #c9922a;
            box-shadow: 0 0 0 3px rgba(201, 146, 42, .16);
        }
        .error {
            margin-top: 8px;
            padding: 9px 11px;
            border-radius: 9px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            font-size: 12px;
        }
        .pista {
            margin: 12px 0 0;
            font-size: 11.5px;
            color: #9ca3af;
            line-height: 1.5;
        }
        .pista code {
            background: #f1f5f9;
            border-radius: 4px;
            padding: 1px 4px;
            color: #334155;
        }
        button {
            width: 100%;
            margin-top: 20px;
            padding: 12px;
            border: 0;
            border-radius: 10px;
            background: #12304f;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s ease;
        }
        button:hover { background: #1b3a5c; }
        .cambiar {
            margin-top: 18px;
            padding-top: 14px;
            border-top: 1px solid #eef2f6;
            text-align: center;
        }
        .cambiar button {
            width: auto;
            margin: 0;
            padding: 0;
            background: none;
            color: #94a3b8;
            font-size: 11.5px;
            font-weight: 500;
            text-decoration: underline;
        }
        .cambiar button:hover { background: none; color: #64748b; }
    </style>
</head>
<body>
    <main class="tarjeta">
        <p class="marca">Obrador</p>
        <h1>Ingresá el código de tu panadería</h1>
        <p class="bajada">
            Con eso abrimos tu sistema en el servidor. Solo se pide la primera vez.
        </p>

        <form method="POST" action="{{ route('escritorio.guardar') }}">
            @csrf

            <label for="codigo">Código</label>
            <input id="codigo" name="codigo" value="{{ old('codigo', $codigo) }}"
                   placeholder="luna" autocomplete="off" autocapitalize="off" spellcheck="false"
                   autofocus maxlength="40">

            @error('codigo')
                <div class="error">{{ $message }}</div>
            @enderror

            <p class="pista">
                Es la parte final de la dirección del sistema:
                <code>obrador.incgroupbo.com/<strong>luna</strong></code>.
                Si no la sabés, pedísela a INC Group.
            </p>

            <button type="submit">Entrar</button>
        </form>

        @if($codigo)
            <div class="cambiar">
                <form method="POST" action="{{ route('escritorio.olvidar') }}">
                    @csrf
                    <button type="submit">Usar otra panadería</button>
                </form>
            </div>
        @endif
    </main>
</body>
</html>
