<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La panadería dejó de descontar del sueldo por llegar tarde.
     *
     * Las tardanzas se siguen registrando en asistencias: el día cuenta como
     * trabajado y los minutos de atraso quedan a la vista para conversarlo con
     * el empleado, pero ya no afectan lo que se le paga.
     */
    public function up(): void
    {
        Schema::table('planilla_empleado', function (Blueprint $table) {
            $table->dropColumn('descuento_tardanzas');
        });
    }

    public function down(): void
    {
        Schema::table('planilla_empleado', function (Blueprint $table) {
            $table->decimal('descuento_tardanzas', 10, 2)->default(0)->after('salario_bruto');
        });
    }
};
