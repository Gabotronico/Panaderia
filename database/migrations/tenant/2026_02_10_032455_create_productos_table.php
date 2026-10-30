<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias')->onDelete('cascade');
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->decimal('precio_venta', 10, 2);
            $table->integer('stock')->default(0);
            $table->integer('stock_minimo')->default(5);
            $table->string('imagen')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};