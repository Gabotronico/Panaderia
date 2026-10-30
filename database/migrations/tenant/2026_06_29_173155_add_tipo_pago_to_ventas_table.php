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
        Schema::table('ventas', function (Blueprint $table) {
            $table->enum('tipo_pago', ['efectivo', 'qr'])->default('efectivo')->after('total');
            $table->decimal('monto_recibido', 10, 2)->nullable()->after('tipo_pago');
            $table->decimal('cambio', 10, 2)->default(0)->after('monto_recibido');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['tipo_pago', 'monto_recibido', 'cambio']);
        });
    }
};
