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

    <div class="modal fade" id="actualizador-modal" tabindex="-1"
         aria-labelledby="actualizador-titulo" aria-hidden="true"
         data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content actualizador-caja">
                <div class="modal-header">
                    <h5 class="modal-title" id="actualizador-titulo">Actualización</h5>
                    <button type="button" class="btn-close" id="actualizador-cerrar"
                            data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    {{-- Versión disponible --}}
                    <section data-paso="info" hidden>
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <span class="actualizador-icono"><i class="fas fa-box-open"></i></span>
                            <div>
                                <p class="mb-1 fw-semibold" id="actualizador-version"></p>
                                <p class="text-muted small mb-0" id="actualizador-fecha"></p>
                            </div>
                        </div>
                        <div class="actualizador-notas" id="actualizador-notas"></div>
                        <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Ahora no</button>
                            <a id="actualizador-descarga-web" class="btn btn-primary" hidden
                               href="#" rel="noopener">
                                <i class="fas fa-download me-1"></i>Descargar para Windows
                            </a>
                            <button type="button" id="actualizador-descargar" class="btn btn-primary" hidden>
                                <i class="fas fa-download me-1"></i>Descargar actualización
                            </button>
                        </div>
                    </section>

                    {{-- Descargando --}}
                    <section data-paso="descarga" hidden>
                        <p class="text-center fw-semibold mb-1" id="actualizador-descarga-titulo">
                            Descargando la actualización
                        </p>
                        <p class="text-center text-muted small mb-4">
                            No cierres el sistema hasta que termine.
                        </p>
                        <div class="progress actualizador-barra" role="progressbar" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar progress-bar-striped progress-bar-animated"
                                 id="actualizador-barra" style="width: 0%"></div>
                        </div>
                        <p class="text-center small text-muted mt-2 mb-0" id="actualizador-progreso-texto">0 %</p>
                    </section>

                    {{-- Lista para instalar --}}
                    <section data-paso="lista" hidden>
                        <div class="text-center py-2">
                            <span class="actualizador-icono actualizador-icono-ok"><i class="fas fa-circle-check"></i></span>
                            <p class="fw-semibold mb-1 mt-3">La actualización está lista</p>
                            <p class="text-muted small mb-0" id="actualizador-lista-texto"></p>
                        </div>
                        <p class="text-muted small text-center mt-3 mb-0">
                            Al instalar, el sistema se cierra y vuelve a abrir solo.
                        </p>
                        <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Más tarde</button>
                            <button type="button" id="actualizador-instalar" class="btn btn-primary">
                                <i class="fas fa-power-off me-1"></i>Instalar ahora
                            </button>
                        </div>
                    </section>

                    {{-- Error --}}
                    <section data-paso="error" hidden>
                        <div class="text-center py-2">
                            <span class="actualizador-icono actualizador-icono-error"><i class="fas fa-triangle-exclamation"></i></span>
                            <p class="fw-semibold mb-1 mt-3">No se pudo actualizar</p>
                            <p class="text-muted small mb-0" id="actualizador-error-texto"></p>
                        </div>
                        <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cerrar</button>
                            <button type="button" id="actualizador-reintentar" class="btn btn-primary">
                                <i class="fas fa-rotate-right me-1"></i>Reintentar
                            </button>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .topbar-update-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-left: auto;
        margin-right: 12px;
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
