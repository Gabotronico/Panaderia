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
        Schema::table('compras_insumo', function (Blueprint $table) {
            $table->decimal('cantidad', 12, 5)->change();
            $table->decimal('precio_unitario', 12, 5)->change();
        });
    }

    public function down(): void
    {
        Schema::table('compras_insumo', function (Blueprint $table) {
            $table->decimal('cantidad', 10, 2)->change();
            $table->decimal('precio_unitario', 10, 2)->change();
        });
    }
};
