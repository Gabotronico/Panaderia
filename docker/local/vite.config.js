// Config de Vite solo para el entorno local en Docker (compose.local.yaml).
//
// La de la raíz sirve para el build de producción y para desarrollar en la
// máquina. Acá se cambia lo justo para que funcione dentro de un contenedor:
//
//   - host 0.0.0.0: el servidor tiene que escuchar fuera del contenedor;
//   - origin localhost:5173: es la dirección que se escribe en public/hot, la
//     que abre el navegador. Sin esto quedaría http://0.0.0.0:5173, que el
//     navegador no resuelve y dejaría la página sin estilos;
//   - cors: laravel-vite-plugin, cuando hay origin, solo autoriza ese mismo
//     origen. Acá el navegador carga la app en :8094 y los assets en :5173,
//     así que hay que autorizar el origen de la app.
//
// Se usa con: npm run dev -- --config docker/local/vite.config.js
import { defineConfig, mergeConfig } from 'vite';
import base from '../../vite.config.js';

export default mergeConfig(base, defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        origin: 'http://localhost:5173',
        cors: {
            origin: ['http://localhost:8094', 'http://127.0.0.1:8094'],
        },
    },
}));
