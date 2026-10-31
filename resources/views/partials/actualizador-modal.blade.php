{{--
    Modal del actualizador. Va fuera del encabezado a propósito: el header
    tiene z-index propio y su contexto de apilamiento deja el fondo del modal
    (que Bootstrap agrega al body) por encima del diálogo, bloqueando los clics.
--}}
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
                            <a id="actualizador-releases" class="btn btn-light border" hidden
                               href="#" target="_blank" rel="noopener">
                                <i class="fas fa-box-archive me-1"></i>Ver releases
                            </a>
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
