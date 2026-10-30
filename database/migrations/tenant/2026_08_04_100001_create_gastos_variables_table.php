<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gastos que no se repiten mes a mes: transporte, una reparación puntual,
     * una compra de urgencia.
     *
     * Se separan de los gastos fijos porque el flujo es distinto. El gasto
     * fijo se define una vez y el sistema le genera un pago por período; el
     * variable se carga el día que ocurre, con su monto real, y no se repite.
     * Por eso no tiene frecuencia, ni vencimiento, ni tabla de pagos aparte.
     *
     * A diferencia de los fijos, estos pesan sobre la UTILIDAD BRUTA: son
     * costo de operar el negocio antes de sueldos y gastos de estructura.
     */
    public function up(): void
    {
        Schema::create('gastos_variables', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('concepto', 150);
            $table->string('categoria', 50)->default('otro');
            $table->decimal('monto', 10, 2);
            $table->string('proveedor', 150)->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();

            $table->index('fecha');
            $table->index('categoria');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos_variables');
    }
};
