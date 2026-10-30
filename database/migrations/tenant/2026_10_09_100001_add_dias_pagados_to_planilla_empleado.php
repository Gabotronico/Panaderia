<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Días que efectivamente se pagaron, con fracción: desde que se paga por
 * horas a quien no cumple su jornada, ya no se deducen de contar estados
 * (presente + medio día × 0,5). Nullable porque las planillas anteriores no
 * lo guardaron; para ellas se sigue mostrando el cálculo por estado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planilla_empleado', function (Blueprint $table) {
            $table->decimal('dias_pagados', 6, 2)->nullable()->after('dias_medio');
        });
    }

    public function down(): void
    {
        Schema::table('planilla_empleado', function (Blueprint $table) {
            $table->dropColumn('dias_pagados');
        });
    }
};
