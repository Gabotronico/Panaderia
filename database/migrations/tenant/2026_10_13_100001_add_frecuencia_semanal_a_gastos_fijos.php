<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gastos fijos semanales (ej. el bono de alimentación que se paga cada
 * sábado).
 *
 * - frecuencia y categoria dejan de ser ENUM: sumar un valor a un ENUM es una
 *   migración por motor cada vez; como texto, los valores válidos los decide
 *   la validación (GastoFijo::FRECUENCIAS y ::CATEGORIAS).
 * - dia_semana: qué día vence un gasto semanal (1 = lunes … 7 = domingo).
 * - Un gasto semanal genera un pago por semana, así que la unicidad de
 *   gastos_pagos pasa de (gasto, mes) a (gasto, fecha de vencimiento). El
 *   índice nuevo se crea antes de quitar el viejo: en MySQL ese índice es el
 *   que sostiene la clave foránea a gastos_fijos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gastos_fijos', function (Blueprint $table) {
            $table->string('frecuencia', 20)->default('mensual')->change();
            $table->string('categoria', 30)->change();
            $table->unsignedTinyInteger('dia_semana')->nullable()->after('dia_vencimiento')
                ->comment('Día en que vence un gasto semanal: 1 = lunes … 7 = domingo');
        });

        Schema::table('gastos_pagos', function (Blueprint $table) {
            $table->unique(['gasto_fijo_id', 'fecha_vencimiento']);
            $table->index('periodo');
        });

        Schema::table('gastos_pagos', function (Blueprint $table) {
            $table->dropUnique(['gasto_fijo_id', 'periodo']);
        });
    }

    public function down(): void
    {
        Schema::table('gastos_pagos', function (Blueprint $table) {
            $table->unique(['gasto_fijo_id', 'periodo']);
        });

        Schema::table('gastos_pagos', function (Blueprint $table) {
            $table->dropUnique(['gasto_fijo_id', 'fecha_vencimiento']);
            $table->dropIndex(['periodo']);
        });

        Schema::table('gastos_fijos', function (Blueprint $table) {
            $table->dropColumn('dia_semana');
        });
    }
};
