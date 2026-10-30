<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recetas', function (Blueprint $table) {
            $table->integer('rendimiento')->default(1)->after('nombre')
                  ->comment('Unidades que produce esta receta');
        });
    }

    public function down(): void
    {
        Schema::table('recetas', function (Blueprint $table) {
            $table->dropColumn('rendimiento');
        });
    }
};
