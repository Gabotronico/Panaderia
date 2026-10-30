<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gasto_fijo_id')->constrained('gastos_fijos')->cascadeOnDelete();
            $table->string('periodo', 7)->comment('Formato YYYY-MM');
            $table->date('fecha_vencimiento');
            $table->decimal('monto_estimado', 10, 2);
            $table->decimal('monto_real', 10, 2)->nullable()->comment('Monto efectivamente pagado');
            $table->date('fecha_pago')->nullable();
            $table->enum('estado', ['pendiente', 'pagado', 'vencido'])->default('pendiente');
            $table->string('referencia')->nullable()->comment('N° de recibo o factura');
            $table->string('observaciones')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['gasto_fijo_id', 'periodo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos_pagos');
    }
};
