<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Horario de trabajo del empleado.
     *
     * Sin esto la tardanza y las horas extra se cargaban a mano en cada
     * registro de asistencia: no había contra qué comparar la hora marcada.
     * Con el horario definido, el sistema calcula ambos valores solo.
     *
     * Queda nullable porque no todos los puestos tienen horario fijo; los que
     * no lo tengan siguen con la carga manual de siempre.
     */
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->time('hora_entrada')->nullable()->after('fecha_ingreso')
                  ->comment('Hora de entrada programada');
            $table->time('hora_salida')->nullable()->after('hora_entrada')
                  ->comment('Hora de salida programada');
            $table->unsignedSmallInteger('minutos_tolerancia')->nullable()->after('hora_salida')
                  ->comment('Minutos de gracia antes de contar tardanza; null usa el valor de config/nomina.php');
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn(['hora_entrada', 'hora_salida', 'minutos_tolerancia']);
        });
    }
};
