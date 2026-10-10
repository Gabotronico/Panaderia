<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Devuelve las horas extra a la planilla, pero cargadas a mano.
     *
     * La versión anterior las calculaba sola desde el horario del empleado y
     * las multiplicaba por un factor fijo; se quitó porque nadie liquidaba así.
     * Acá el administrador decide dos cosas por separado: cuántas horas
     * reconoce y cuánta plata paga por ellas. Por eso el monto es una columna
     * propia y no un factor sobre la tarifa: la panadería a veces paga un
     * monto acordado que no sale de ninguna fórmula.
     *
     * Van en el detalle de la planilla y no en la asistencia porque son una
     * decisión de liquidación, no un hecho del día.
     */
    public function up(): void
    {
        Schema::table('planilla_empleado', function (Blueprint $table) {
            $table->decimal('horas_extra', 6, 2)->default(0)->after('dias_medio');
            $table->decimal('monto_horas_extra', 10, 2)->default(0)->after('horas_extra');
            $table->string('detalle_horas_extra')->nullable()->after('monto_horas_extra');
        });
    }

    public function down(): void
    {
        Schema::table('planilla_empleado', function (Blueprint $table) {
            $table->dropColumn(['horas_extra', 'monto_horas_extra', 'detalle_horas_extra']);
        });
    }
};
