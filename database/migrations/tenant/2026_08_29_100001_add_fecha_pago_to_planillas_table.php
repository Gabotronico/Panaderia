<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Separa "a qué período corresponde el sueldo" de "cuándo salió la plata".
 *
 * Hasta ahora el costo de la planilla se imputaba al mes en que ARRANCA el
 * período, y eso mandaba a agosto una semana como la del lunes 31/08 al sábado
 * 05/09, de la que solo el lunes fue de agosto. El criterio del negocio es de
 * caja: el sueldo pesa en el mes en que se pagó, que es el sábado de pago.
 *
 * Es el mismo par que ya usa gastos_pagos (periodo vs fecha_pago).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planillas', function (Blueprint $table) {
            $table->date('fecha_pago')->nullable()->after('estado');
        });

        // Las planillas ya pagadas no tienen fecha registrada: sin este relleno
        // desaparecerían de los reportes, porque el filtro pasa a ser por
        // fecha_pago. Se asume el cierre del período, que es el día de pago.
        DB::table('planillas')
            ->where('estado', 'pagada')
            ->whereNull('fecha_pago')
            ->update(['fecha_pago' => DB::raw('periodo_fin')]);
    }

    public function down(): void
    {
        Schema::table('planillas', function (Blueprint $table) {
            $table->dropColumn('fecha_pago');
        });
    }
};
