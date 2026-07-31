<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Elimina las horas extra de toda la nómina.
     *
     * La panadería no liquida horas extra: el pago sale de los días
     * efectivamente trabajados y del descuento por atraso. Mantener las
     * columnas obligaba a cargar un dato que nadie usaba y ensuciaba la
     * planilla y su PDF con montos siempre en cero.
     *
     * El horario del empleado se conserva porque sigue siendo la referencia
     * para calcular los minutos de atraso.
     */
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn('factor_hora_extra');
        });

        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropColumn('horas_extra');
        });

        Schema::table('planilla_empleado', function (Blueprint $table) {
            $table->dropColumn(['horas_extra', 'monto_horas_extra']);
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->decimal('factor_hora_extra', 4, 2)->default(1.50)->after('tipo_pago');
        });

        Schema::table('asistencias', function (Blueprint $table) {
            $table->decimal('horas_extra', 5, 2)->default(0)->after('minutos_tardanza');
        });

        Schema::table('planilla_empleado', function (Blueprint $table) {
            $table->decimal('horas_extra', 6, 2)->default(0)->after('dias_medio');
            $table->decimal('monto_horas_extra', 10, 2)->default(0)->after('horas_extra');
        });
    }
};
