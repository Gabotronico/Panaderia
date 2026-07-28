<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registra qué usuario cerró el corte. Normalmente es el mismo cajero
     * que lo abrió, pero el administrador puede cerrar cajas ajenas y en
     * ese caso debe quedar constancia de quién hizo el arqueo.
     */
    public function up(): void
    {
        Schema::table('cortes_caja', function (Blueprint $table) {
            $table->foreignId('cerrado_por')
                  ->nullable()
                  ->after('estado')
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Usuario que realizó el cierre');
        });
    }

    public function down(): void
    {
        Schema::table('cortes_caja', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cerrado_por');
        });
    }
};
