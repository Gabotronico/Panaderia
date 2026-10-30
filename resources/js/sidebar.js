/**
 * Secciones plegables del sidebar.
 *
 * Cada sección es un `.sidebar-label` seguido de su `ul.sidebar-nav`. El label
 * se convierte en un botón con chevron; el estado queda guardado en el
 * navegador para que se respete entre páginas.
 *
 * La sección que contiene la página actual siempre se muestra abierta: si no,
 * al entrar desde un enlace directo no se vería dónde estamos parados.
 */

const CLAVE = 'obrador.sidebar.secciones';

function claveDe(texto, indice) {
    const limpio = (texto || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

    return limpio || `seccion-${indice}`;
}

export default function iniciarSidebar() {
    const etiquetas = [...document.querySelectorAll('.sidebar-label')];
    if (etiquetas.length === 0) return;

    let guardadas = {};

    try {
        guardadas = JSON.parse(localStorage.getItem(CLAVE) || '{}') || {};
    } catch {
        guardadas = {};
    }

    function guardar() {
        try {
            localStorage.setItem(CLAVE, JSON.stringify(guardadas));
        } catch {
            // Modo privado o almacenamiento lleno: se mantiene solo en memoria.
        }
    }

    etiquetas.forEach((etiqueta, indice) => {
        const lista = etiqueta.nextElementSibling;

        if (!lista || !lista.classList.contains('sidebar-nav')) {
            return;
        }

        const clave = claveDe(etiqueta.textContent, indice);
        const idLista = `sidebar-${clave}`;
        lista.id = idLista;

        const boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'sidebar-label sidebar-label-toggle';
        boton.setAttribute('aria-controls', idLista);

        const texto = document.createElement('span');
        texto.textContent = (etiqueta.textContent || '').trim();

        const chevron = document.createElement('i');
        chevron.className = 'fas fa-chevron-down sidebar-chevron';
        chevron.setAttribute('aria-hidden', 'true');

        boton.append(texto, chevron);
        etiqueta.replaceWith(boton);

        function aplicar(abierta) {
            lista.classList.toggle('is-collapsed', !abierta);
            boton.setAttribute('aria-expanded', String(abierta));
        }

        const tienePaginaActiva = Boolean(lista.querySelector('a.active'));
        const estadoGuardado = Object.prototype.hasOwnProperty.call(guardadas, clave)
            ? Boolean(guardadas[clave])
            : true;

        aplicar(tienePaginaActiva || estadoGuardado);

        boton.addEventListener('click', () => {
            const abierta = boton.getAttribute('aria-expanded') === 'true';
            aplicar(!abierta);
            guardadas[clave] = !abierta;
            guardar();
        });
    });
}
