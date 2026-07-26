<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planilla_empleado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planilla_id')->constrained('planillas')->cascadeOnDelete();
            $table->foreignId('empleado_id')->constrained('empleados');
            $table->unsignedSmallInteger('dias_trabajados')->default(0);
            $table->unsignedSmallInteger('dias_ausentes')->default(0);
            $table->unsignedSmallInteger('dias_tardanza')->default(0);
            $table->unsignedSmallInteger('dias_medio')->default(0);
            $table->decimal('horas_extra', 6, 2)->default(0);
            $table->decimal('monto_horas_extra', 10, 2)->default(0);
            $table->decimal('adelantos_descontados', 10, 2)->default(0);
            $table->decimal('salario_bruto', 10, 2)->default(0);
            $table->decimal('descuento_tardanzas', 10, 2)->default(0);
            $table->decimal('total_neto', 10, 2)->default(0);
            $table->unique(['planilla_id', 'empleado_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planilla_empleado');
    }
};
