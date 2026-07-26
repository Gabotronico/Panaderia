<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planillas', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['mensual', 'semanal']);
            $table->date('periodo_inicio');
            $table->date('periodo_fin');
            $table->enum('estado', ['borrador', 'cerrada', 'pagada'])->default('borrador');
            $table->decimal('total_general', 12, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planillas');
    }
};
