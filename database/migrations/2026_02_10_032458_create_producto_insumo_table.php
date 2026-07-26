<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_insumo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('insumo_id')->constrained('insumos')->onDelete('cascade');
            $table->decimal('cantidad_necesaria', 10, 2); // Cantidad del insumo necesaria para hacer 1 producto
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_insumo');
    }
};