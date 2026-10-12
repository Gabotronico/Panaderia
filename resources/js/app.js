import './bootstrap';

import 'bootstrap/dist/css/bootstrap.min.css';
import * as bootstrap from 'bootstrap';
import '@fortawesome/fontawesome-free/css/all.min.css';
import '@fontsource/inter/latin-300.css';
import '@fontsource/inter/latin-400.css';
import '@fontsource/inter/latin-500.css';
import '@fontsource/inter/latin-600.css';
import '@fontsource/inter/latin-700.css';
import '@fontsource/inter/latin-800.css';
// Manuscrita de los lemas de la pantalla de ingreso.
import '@fontsource/kaushan-script/latin-400.css';
import Chart from 'chart.js/auto';

window.bootstrap = bootstrap;
window.Chart = Chart;

const navigationStatus = {
    overlay: null,
    timer: null,
};

function ensureNavigationOverlay() {
    if (navigationStatus.overlay) {
        return navigationStatus.overlay;
    }

    const style = document.createElement('style');
    style.textContent = `
        #desktop-navigation-status {
            position: fixed; inset: 0; z-index: 20000; display: none;
            align-items: center; justify-content: center;
            background: rgba(15, 23, 42, .20); cursor: progress;
        }
        #desktop-navigation-status.is-visible { display: flex; }
        #desktop-navigation-status .status-card {
            min-width: 210px; padding: 18px 22px; text-align: center;
            color: #334155; background: #fff; border-radius: 12px;
            box-shadow: 0 18px 50px rgba(15, 23, 42, .22);
            font: 600 14px Inter, 'Segoe UI', sans-serif;
        }
        #desktop-navigation-status .spinner-border { width: 1.4rem; height: 1.4rem; }
        #desktop-navigation-status .status-detail {
            display: block; margin-top: 8px; color: #64748b;
            font-size: 12px; font-weight: 400;
        }
    `;

    const overlay = document.createElement('div');
    overlay.id = 'desktop-navigation-status';
    overlay.setAttribute('aria-live', 'polite');
    overlay.innerHTML = `
        <div class="status-card">
            <span class="spinner-border spinner-border-sm text-primary me-2" aria-hidden="true"></span>
            <span class="status-text">Cargando…</span>
            <span class="status-detail"></span>
        </div>
    `;

    document.head.appendChild(style);
    document.body.appendChild(overlay);
    navigationStatus.overlay = overlay;

    return overlay;
}

function showNavigationStatus() {
    const overlay = ensureNavigationOverlay();
    overlay.querySelector('.status-text').textContent = 'Cargando…';
    overlay.querySelector('.status-detail').textContent = '';
    overlay.classList.add('is-visible');

    clearTimeout(navigationStatus.timer);
    navigationStatus.timer = setTimeout(() => {
        overlay.querySelector('.status-text').textContent = 'La operación está tardando';
        overlay.querySelector('.status-detail').textContent = 'Espere unos segundos. El incidente quedará registrado.';
    }, 8000);
}

function hideNavigationStatus() {
    clearTimeout(navigationStatus.timer);
    navigationStatus.overlay?.classList.remove('is-visible');
}

document.addEventListener('click', (event) => {
    const link = event.target instanceof Element ? event.target.closest('a[href]') : null;

    if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) {
        return;
    }

    const target = new URL(link.href, window.location.href);
    const staysInApp = target.origin === window.location.origin;
    const changesPage = target.href !== window.location.href && !link.hasAttribute('download');

    if (staysInApp && changesPage && (!link.target || link.target === '_self')) {
        setTimeout(showNavigationStatus, 100);
    }
});

document.addEventListener('submit', (event) => {
    if (event.defaultPrevented) {
        return;
    }

    if (event.submitter) {
        setTimeout(() => {
            event.submitter.disabled = true;
        }, 0);
    }

    showNavigationStatus();
});

window.addEventListener('pageshow', hideNavigationStatus);
window.addEventListener('load', hideNavigationStatus);

/*
 * Búsqueda en vivo de los listados.
 *
 * Un formulario de filtros con data-busqueda-en-vivo busca solo mientras se
 * escribe, sin botón ni recarga: pide la misma página con los filtros y
 * reemplaza las zonas marcadas con data-refrescar="clave" por las que trae
 * la respuesta. El campo de texto queda fuera de esas zonas, así no pierde
 * el foco ni lo que se está tipeando.
 *
 * Sin JavaScript el formulario sigue funcionando: es un GET común.
 */
const ESPERA_BUSQUEDA_MS = 300;

document.querySelectorAll('form[data-busqueda-en-vivo]').forEach((form) => {
    let temporizador = null;
    let pedido = null;

    const zonas = () => document.querySelectorAll('[data-refrescar]');

    const buscar = async () => {
        clearTimeout(temporizador);

        const destino = new URL(form.action, window.location.href);
        const filtros = new URLSearchParams();
        new FormData(form).forEach((valor, campo) => {
            if (valor !== '') {
                filtros.append(campo, valor);
            }
        });
        destino.search = filtros.toString();

        // Cada tecla reemplaza la búsqueda anterior: si su respuesta llegara
        // después, pisaría resultados más nuevos con otros más viejos.
        pedido?.abort();
        pedido = new AbortController();

        zonas().forEach((zona) => {
            zona.style.transition = 'opacity .15s ease';
            zona.style.opacity = '.55';
        });

        try {
            const respuesta = await fetch(destino, {
                signal: pedido.signal,
                credentials: 'same-origin',
                headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
            });

            // Sesión vencida u otro desvío: que lo resuelva una carga normal.
            if (!respuesta.ok || new URL(respuesta.url).pathname !== destino.pathname) {
                window.location.assign(destino);
                return;
            }

            const pagina = new DOMParser().parseFromString(await respuesta.text(), 'text/html');

            zonas().forEach((zona) => {
                const nueva = pagina.querySelector(`[data-refrescar="${zona.dataset.refrescar}"]`);
                if (nueva) {
                    zona.replaceWith(nueva);
                } else {
                    zona.style.opacity = '';
                }
            });

            window.history.replaceState(null, '', destino);
        } catch (error) {
            if (error.name !== 'AbortError') {
                window.location.assign(destino);
            }
        }
    };

    form.addEventListener('input', (event) => {
        if (event.target.matches('input[type="text"], input[type="search"]')) {
            clearTimeout(temporizador);
            temporizador = setTimeout(buscar, ESPERA_BUSQUEDA_MS);
        }
    });

    form.addEventListener('change', (event) => {
        if (event.target.matches('select')) {
            buscar();
        }
    });

    // Enter busca al instante, sin recargar.
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        buscar();
    });

    // "Limpiar" vacía los filtros sin salir de la página.
    form.addEventListener('click', (event) => {
        const limpiar = event.target.closest('[data-busqueda-limpiar]');
        if (!limpiar) {
            return;
        }

        event.preventDefault();
        form.querySelectorAll('input[type="text"], input[type="search"]').forEach((campo) => { campo.value = ''; });
        form.querySelectorAll('select').forEach((lista) => { lista.selectedIndex = 0; });
        form.querySelector('input[type="text"], input[type="search"]')?.focus();
        buscar();
    });
});
