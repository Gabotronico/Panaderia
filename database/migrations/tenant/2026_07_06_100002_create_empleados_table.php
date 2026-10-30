<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('ci', 20)->unique();
            $table->string('telefono', 20)->nullable();
            $table->foreignId('cargo_id')->constrained('cargos');
            $table->decimal('salario_base', 10, 2);
            $table->enum('tipo_pago', ['mensual', 'semanal']);
            $table->decimal('factor_hora_extra', 4, 2)->default(1.50);
            $table->date('fecha_ingreso');
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
