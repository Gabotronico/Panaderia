<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marca las ventas registradas por monto total, sin detalle de productos.
     *
     * Se guardan en la misma tabla que las ventas normales a propósito: así
     * entran solas en el arqueo del corte de caja, en el resumen financiero y
     * en el reporte de ventas, sin tocar nada de eso. Lo único que las
     * distingue es que no tienen detalle, y por eso tampoco aparecen en
     * "productos más vendidos", que se arma desde el detalle.
     *
     * La bandera existe para poder mostrarlas distinto y para no ofrecer el
     * detalle de productos donde no lo hay.
     */
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->boolean('es_directa')->default(false)->after('estado')
                  ->comment('Venta registrada por monto total, sin detalle de productos');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('es_directa');
        });
    }
};
