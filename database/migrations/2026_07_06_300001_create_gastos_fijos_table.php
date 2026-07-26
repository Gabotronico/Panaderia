<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos_fijos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->enum('categoria', ['alquiler', 'servicios', 'mantenimiento', 'impuestos', 'otro']);
            $table->decimal('monto_estimado', 10, 2);
            $table->enum('frecuencia', ['mensual', 'bimestral', 'trimestral', 'semestral', 'anual'])->default('mensual');
            $table->tinyInteger('dia_vencimiento')->default(5)->comment('Día del mes en que vence');
            $table->tinyInteger('mes_inicio')->default(1)->comment('Mes base para calcular frecuencia (1=enero)');
            $table->string('proveedor')->nullable()->comment('Empresa o persona a quien se paga');
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos_fijos');
    }
};
