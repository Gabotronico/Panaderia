<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deja las columnas de fecha guardadas como día puro ('2026-07-27').
     *
     * El cast 'date' de Eloquent guardaba con la hora pegada
     * ('2026-07-27 00:00:00'). En MySQL daba igual porque la columna es DATE y
     * el motor la recorta, pero SQLite guarda el texto tal cual y lo compara
     * como texto. Eso rompía dos cosas:
     *
     *  - updateOrCreate() de asistencias buscaba por '2026-07-27', no
     *    encontraba la fila guardada como '2026-07-27 00:00:00' e intentaba
     *    insertar de nuevo, chocando contra el índice único (empleado, fecha).
     *  - Los rangos whereBetween dejaban fuera el último día del período,
     *    porque '2026-08-01 00:00:00' es mayor que '2026-08-01' al comparar
     *    como texto.
     *
     * Los modelos ya pasaron a 'date:Y-m-d' para que lo nuevo entre limpio;
     * esto arregla lo que quedó guardado antes.
     */
    private const COLUMNAS = [
        'asistencias'    => ['fecha'],
        'planillas'      => ['periodo_inicio', 'periodo_fin'],
        'adelantos'      => ['fecha'],
        'compras_insumo' => ['fecha'],
        'mermas_insumos' => ['fecha'],
        'cortes_caja'    => ['fecha_corte'],
        'empleados'      => ['fecha_ingreso'],
        'gastos_pagos'   => ['fecha_vencimiento', 'fecha_pago'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNAS as $tabla => $columnas) {
            if (!Schema::hasTable($tabla)) {
                continue;
            }

            foreach ($columnas as $columna) {
                if (!Schema::hasColumn($tabla, $columna)) {
                    continue;
                }

                // SUBSTR existe en SQLite y en MySQL. Solo se tocan las filas
                // que traen hora, para no reescribir la tabla entera.
                DB::table($tabla)
                    ->whereNotNull($columna)
                    ->where($columna, 'like', '% %')
                    ->update([$columna => DB::raw("SUBSTR($columna, 1, 10)")]);
            }
        }
    }

    /**
     * No se revierte: volver a pegarle ' 00:00:00' a las fechas sería
     * reintroducir el error a propósito.
     */
    public function down(): void
    {
        //
    }
};
