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
