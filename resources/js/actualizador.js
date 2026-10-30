/**
 * Actualizador de la app de escritorio.
 *
 * En el navegador ofrece la descarga del instalador del último release.
 * Dentro de la app empaquetada además descarga con progreso y ofrece instalar
 * ahora o más tarde. Los datos salen de /sistema/actualizaciones.
 */

const INTERVALO_MS = 800;

function formatoBytes(bytes) {
    const valor = Number(bytes || 0);
    if (valor < 1024) return `${valor} B`;
    if (valor < 1024 * 1024) return `${(valor / 1024).toFixed(0)} KB`;
    return `${(valor / (1024 * 1024)).toFixed(1)} MB`;
}

function formatoFecha(valor) {
    if (!valor) return '';
    const fecha = new Date(valor);
    if (Number.isNaN(fecha.getTime())) return '';
    return fecha.toLocaleDateString('es-BO', { day: '2-digit', month: 'long', year: 'numeric' });
}

export default function iniciarActualizador() {
    const raiz = document.getElementById('actualizador');
    if (!raiz) return;

    const escritorio = raiz.dataset.escritorio === '1';
    const csrf = raiz.dataset.csrf || document.querySelector('meta[name="csrf-token"]')?.content || '';

    const urls = {
        estado: raiz.dataset.urlEstado,
        comprobar: raiz.dataset.urlComprobar,
        descargar: raiz.dataset.urlDescargar,
        progreso: raiz.dataset.urlProgreso,
        instalar: raiz.dataset.urlInstalar,
    };

    const boton = document.getElementById('actualizador-boton');
    const etiqueta = document.getElementById('actualizador-etiqueta');
    const modalEl = document.getElementById('actualizador-modal');
    const cerrar = document.getElementById('actualizador-cerrar');
    const pasos = new Map(
        [...modalEl.querySelectorAll('[data-paso]')].map((seccion) => [seccion.dataset.paso, seccion]),
    );

    let estado = null;
    let modal = null;
    let intervalo = null;

    async function pedir(url, opciones = {}) {
        const respuesta = await fetch(url, {
            ...opciones,
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
                ...(opciones.headers || {}),
            },
        });

        if (!respuesta.ok) {
            throw new Error(`El servidor respondió ${respuesta.status}.`);
        }

        return respuesta.json();
    }

    function paso(nombre) {
        pasos.forEach((seccion, clave) => {
            seccion.hidden = clave !== nombre;
        });
        cerrar.hidden = nombre === 'descarga';
    }

    function barra(percent, texto) {
        const valor = Math.max(0, Math.min(100, Number(percent) || 0));
        const elemento = document.getElementById('actualizador-barra');
        elemento.style.width = `${valor}%`;
        elemento.setAttribute('aria-valuenow', String(valor));
        document.getElementById('actualizador-progreso-texto').textContent = texto ?? `${valor} %`;
    }

    function detener() {
        if (intervalo !== null) {
            clearInterval(intervalo);
            intervalo = null;
        }
    }

    function pintarBoton() {
        const descarga = estado?.descarga ?? { estado: 'inactiva' };

        if (!estado || !estado.ultima) {
            boton.hidden = true;
            return;
        }

        if (!escritorio) {
            etiqueta.textContent = 'Descargar';
            boton.hidden = false;
            return;
        }

        if (descarga.estado === 'lista') {
            etiqueta.textContent = 'Instalar actualización';
            boton.hidden = false;
            return;
        }

        if (descarga.estado === 'descargando') {
            etiqueta.textContent = 'Descargando…';
            boton.hidden = false;
            return;
        }

        if (estado.hay_actualizacion) {
            etiqueta.textContent = `Actualizar a ${estado.ultima.version}`;
            boton.hidden = false;
            return;
        }

        boton.hidden = true;
    }

    function pintarInfo() {
        const ultima = estado?.ultima;
        if (!ultima) return;

        document.getElementById('actualizador-titulo').textContent = 'Nueva versión disponible';
        document.getElementById('actualizador-version').textContent = `Versión ${ultima.version}`;

        const detalles = [formatoFecha(ultima.fecha), ultima.tamano ? formatoBytes(ultima.tamano) : '']
            .filter(Boolean)
            .join(' · ');
        document.getElementById('actualizador-fecha').textContent = detalles;

        document.getElementById('actualizador-notas').textContent = ultima.notas || 'Sin detalles de la versión.';

        const enlace = document.getElementById('actualizador-descarga-web');
        const descargar = document.getElementById('actualizador-descargar');

        if (escritorio) {
            enlace.hidden = true;
            descargar.hidden = false;
        } else {
            enlace.href = ultima.url;
            enlace.hidden = false;
            descargar.hidden = true;
        }
    }

    function pintarLista(descarga) {
        document.getElementById('actualizador-titulo').textContent = 'Actualización lista';
        document.getElementById('actualizador-lista-texto').textContent = descarga?.version
            ? `La versión ${descarga.version} ya se descargó.`
            : 'La actualización ya se descargó.';
    }

    function pintarError(mensaje) {
        document.getElementById('actualizador-titulo').textContent = 'No se pudo actualizar';
        document.getElementById('actualizador-error-texto').textContent = mensaje || 'Ocurrió un error inesperado.';
        paso('error');
    }

    function abrir() {
        modal ??= new window.bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });

        pintarInfo();

        const descarga = estado?.descarga ?? { estado: 'inactiva' };

        if (escritorio && descarga.estado === 'lista') {
            pintarLista(descarga);
            paso('lista');
        } else if (escritorio && descarga.estado === 'descargando') {
            barra(descarga.percent ?? 0);
            paso('descarga');
            seguir();
        } else {
            paso('info');
        }

        modal.show();
    }

    function seguir() {
        detener();

        intervalo = setInterval(async () => {
            try {
                const descarga = await pedir(urls.progreso);
                actualizarDescarga(descarga);
            } catch {
                detener();
                pintarError('Se perdió la comunicación con el actualizador.');
            }
        }, INTERVALO_MS);
    }

    function actualizarDescarga(descarga) {
        if (!descarga || descarga.estado === 'inactiva') return;

        if (descarga.estado === 'descargando') {
            const detalles = descarga.total
                ? `${formatoBytes(descarga.transferred ?? 0)} de ${formatoBytes(descarga.total)}`
                : '';
            const velocidad = descarga.bytes_por_segundo
                ? ` · ${formatoBytes(descarga.bytes_por_segundo)}/s`
                : '';
            barra(descarga.percent ?? 0, `${(descarga.percent ?? 0).toFixed(0)} %${detalles ? ` · ${detalles}` : ''}${velocidad}`);

            if (!pasos.get('descarga')?.hidden) return;
            paso('descarga');

            return;
        }

        if (descarga.estado === 'lista') {
            detener();
            pintarLista(descarga);
            paso('lista');
            pintarBoton();

            return;
        }

        if (descarga.estado === 'error') {
            detener();
            pintarError(descarga.mensaje);
            pintarBoton();
        }
    }

    async function descargar() {
        paso('descarga');
        barra(0, 'Preparando la descarga…');

        try {
            await pedir(urls.descargar, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ version: estado?.ultima?.version ?? null }),
            });

            seguir();
        } catch (error) {
            pintarError(error.message);
        }
    }

    async function instalar() {
        const botonInstalar = document.getElementById('actualizador-instalar');
        botonInstalar.disabled = true;
        botonInstalar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Instalando…';

        try {
            await pedir(urls.instalar, { method: 'POST' });
        } catch (error) {
            botonInstalar.disabled = false;
            botonInstalar.innerHTML = '<i class="fas fa-power-off me-1"></i>Instalar ahora';
            pintarError(error.message);
        }
    }

    boton.addEventListener('click', abrir);
    document.getElementById('actualizador-descargar').addEventListener('click', descargar);
    document.getElementById('actualizador-reintentar').addEventListener('click', descargar);
    document.getElementById('actualizador-instalar').addEventListener('click', instalar);

    // Al cargar cualquier pantalla se consulta el estado (GitHub se cachea en
    // el servidor, así que esto no pega en la API en cada clic).
    pedir(urls.estado)
        .then((respuesta) => {
            estado = respuesta;
            pintarBoton();
        })
        .catch(() => {
            boton.hidden = true;
        });
}
