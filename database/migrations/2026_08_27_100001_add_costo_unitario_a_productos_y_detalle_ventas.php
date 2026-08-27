<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Da al sistema una noción de cuánto cuesta lo que vende.
     *
     * Hasta acá `productos` solo guardaba `precio_venta`, así que lo que
     * Finanzas llamaba "utilidad bruta" era en realidad ingresos − compras del
     * mes. Comprar una tonelada de harina en agosto para usarla hasta octubre
     * hundía agosto y regalaba septiembre, aunque el negocio hubiera andado
     * igual los tres meses.
     *
     * Se agregan dos columnas con roles distintos:
     *
     * - `productos.costo_unitario` es el costo promedio ponderado ACTUAL: se
     *   recalcula en cada corrida de producción contra el stock que ya había.
     *   Es un valor vivo, sirve para valorizar inventario y para saber si el
     *   precio de venta cubre el costo.
     *
     * - `detalle_ventas.costo_unitario` es el costo CONGELADO al vender: se
     *   copia del producto en el momento de la venta y no se toca nunca más.
     *   Sin esto, cualquier cambio de costo posterior movería el resultado de
     *   meses ya cerrados.
     *
     * Cuatro decimales porque el pan se cuenta por unidad y el costo unitario
     * de una pieza barata cae fácil por debajo del centavo; redondear a dos
     * en el unitario y recién después multiplicar por cientos de unidades
     * arrastraba un error visible al total.
     *
     * Las filas existentes quedan en 0 A PROPÓSITO: el costo histórico
     * anterior a este cambio NO se puede reconstruir. No hay registro de qué
     * insumos se consumieron para lo que ya se vendió (producción es reciente
     * y la mayoría de las ventas viejas son directas, sin detalle). Poner un
     * costo estimado sería inventar un número y ensuciar el histórico, así que
     * se deja en 0 y Finanzas informa aparte cuánta venta del período no tiene
     * costo conocido, para que un margen falsamente enorme no pase inadvertido.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->decimal('costo_unitario', 12, 4)->default(0)->after('precio_venta')
                  ->comment('Costo promedio ponderado actual, recalculado en cada producción');
        });

        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->decimal('costo_unitario', 12, 4)->default(0)->after('precio_unitario')
                  ->comment('Costo del producto congelado al momento de vender; no se recalcula');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn('costo_unitario');
        });

        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->dropColumn('costo_unitario');
        });
    }
};
