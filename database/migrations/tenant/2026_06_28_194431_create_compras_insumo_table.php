<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('compras_insumo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insumo_id')->constrained('insumos')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('cantidad', 10, 2);
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('total', 12, 2);
            $table->date('fecha');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras_insumo');
    }
};
