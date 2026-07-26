<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('almacenes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Stock de cada producto por almacén
        Schema::create('almacen_producto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('almacen_id')->constrained('almacenes')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->integer('stock')->default(0);
            $table->timestamps();
            $table->unique(['almacen_id', 'producto_id']);
        });

        // Cajero asignado a un almacén
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('almacen_id')->nullable()->after('id')
                  ->constrained('almacenes')->nullOnDelete();
        });

        // Venta originada desde un almacén
        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('almacen_id')->nullable()->after('user_id')
                  ->constrained('almacenes')->nullOnDelete();
        });

        // Producto que genera la receta (opcional)
        Schema::table('recetas', function (Blueprint $table) {
            $table->foreignId('producto_id')->nullable()->after('id')
                  ->constrained('productos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('recetas', fn($t) => $t->dropForeignIdFor(\App\Models\Producto::class));
        Schema::table('ventas',  fn($t) => $t->dropForeignIdFor(\App\Models\Almacen::class));
        Schema::table('users',   fn($t) => $t->dropForeignIdFor(\App\Models\Almacen::class));
        Schema::dropIfExists('almacen_producto');
        Schema::dropIfExists('almacenes');
    }
};
