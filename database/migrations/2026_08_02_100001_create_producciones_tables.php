<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reemplaza el módulo de recetas por el de producción.
     *
     * La receta describía una fórmula guardada y al "usarla" movía stock sin
     * dejar rastro de la corrida. Producción invierte el enfoque: cada corrida
     * es un registro propio con los insumos que consumió y los productos que
     * salieron, así queda el historial de qué se hizo y con qué.
     *
     * El costo del insumo se copia al momento de producir. Si mañana cambia el
     * precio de la harina, la producción de hoy sigue mostrando lo que
     * realmente costó.
     */
    public function up(): void
    {
        Schema::create('producciones', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->text('observaciones')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();

            $table->index('fecha');
        });

        Schema::create('produccion_insumo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produccion_id')->constrained('producciones')->cascadeOnDelete();
            $table->foreignId('insumo_id')->constrained('insumos');
            $table->decimal('cantidad', 12, 3)->comment('Cantidad consumida');
            $table->decimal('costo_unitario', 12, 5)->default(0)
                  ->comment('Costo del insumo al momento de producir');
            $table->timestamps();
        });

        Schema::create('produccion_producto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produccion_id')->constrained('producciones')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos');
            $table->unsignedInteger('cantidad')->comment('Unidades que salieron para la venta');
            $table->timestamps();
        });

        // Las recetas dejan de existir: su función la cubre producción.
        Schema::dropIfExists('receta_insumo');
        Schema::dropIfExists('recetas');
    }

    public function down(): void
    {
        Schema::dropIfExists('produccion_producto');
        Schema::dropIfExists('produccion_insumo');
        Schema::dropIfExists('producciones');

        Schema::create('recetas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->integer('rendimiento')->default(1);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('receta_insumo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receta_id')->constrained('recetas')->onDelete('cascade');
            $table->foreignId('insumo_id')->constrained('insumos')->onDelete('cascade');
            $table->decimal('cantidad_necesaria', 10, 2);
            $table->timestamps();
        });
    }
};
