{{--
    Actualizador de la app de escritorio.

    - En el navegador el botón dice "Descargar" y el modal lleva al .exe del
      último release publicado en GitHub.
    - En la app de escritorio dice "Actualizar" y solo aparece si hay una
      versión más nueva (o una descarga ya hecha). El modal descarga con
      progreso, bloquea el cierre mientras baja y al terminar ofrece instalar
      ahora o más tarde.

    La lógica vive en resources/js/actualizador.js. Los datos salen de
    /sistema/actualizaciones.
--}}
<div id="actualizador"
     data-escritorio="{{ \App\Support\Actualizaciones\Actualizaciones::escritorio() ? '1' : '0' }}"
     data-csrf="{{ csrf_token() }}"
     data-url-estado="{{ route('system.updates.estado') }}"
     data-url-comprobar="{{ route('system.updates.comprobar') }}"
     data-url-descargar="{{ route('system.updates.descargar') }}"
     data-url-progreso="{{ route('system.updates.progreso') }}"
     data-url-instalar="{{ route('system.updates.instalar') }}">

    <button type="button" id="actualizador-boton" class="topbar-update-btn" hidden>
        <i class="fas fa-cloud-arrow-down" aria-hidden="true"></i>
        <span id="actualizador-etiqueta">Descargar</span>
    </button>
</div>

<style>
    /* El envoltorio es el que se pega a la derecha (al lado del perfil). */
    #actualizador {
        margin-left: auto;
        display: flex;
        align-items: center;
    }

    .topbar-update-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-right: 4px;
        padding: 7px 14px;
        border-radius: 999px;
        border: 1px solid rgba(217, 164, 65, .55);
        background: rgba(217, 164, 65, .14);
        color: #f3d9a4;
        font-size: .8rem;
        font-weight: 600;
        line-height: 1;
        cursor: pointer;
        transition: background .15s ease, color .15s ease, transform .15s ease;
    }

    .topbar-update-btn:hover {
        background: #d4a017;
        color: #12304f;
        transform: translateY(-1px);
    }

    .topbar-update-btn[hidden] { display: none !important; }

    .topbar-update-btn i { font-size: .85rem; }

    .actualizador-caja { border: 0; border-radius: 16px; }

    .actualizador-icono {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 46px;
        height: 46px;
        border-radius: 12px;
        background: rgba(18, 48, 79, .08);
        color: #12304f;
        font-size: 1.15rem;
    }

    .actualizador-icono-ok { background: rgba(22, 163, 74, .12); color: #15803d; }
    .actualizador-icono-error { background: rgba(220, 38, 38, .12); color: #b91c1c; }

    .actualizador-notas {
        max-height: 180px;
        overflow-y: auto;
        padding: 12px 14px;
        border-radius: 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        font-size: .82rem;
        color: #475569;
        white-space: pre-line;
    }

    .actualizador-barra { height: 10px; border-radius: 999px; }
    .actualizador-barra .progress-bar { border-radius: 999px; }
</style>
