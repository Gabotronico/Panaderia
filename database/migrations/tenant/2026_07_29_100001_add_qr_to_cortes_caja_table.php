<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Separa el cobro por QR del efectivo en el arqueo.
     *
     * Hasta ahora el cierre asumía que todas las ventas del turno entraban a
     * la caja como efectivo, así que el esperado era monto_inicial +
     * total_ventas. Con QR eso deja de ser cierto: la plata del QR nunca pasa
     * por el cajón. A partir de aquí el turno se desglosa en ventas_efectivo /
     * ventas_qr y cada medio se arquea contra su propio contado.
     */
    public function up(): void
    {
        Schema::table('cortes_caja', function (Blueprint $table) {
            $table->decimal('ventas_efectivo', 10, 2)->default(0)->after('total_ventas')
                  ->comment('Ventas del turno cobradas en efectivo');
            $table->decimal('ventas_qr', 10, 2)->default(0)->after('ventas_efectivo')
                  ->comment('Ventas del turno cobradas por QR');
            $table->decimal('total_qr', 10, 2)->default(0)->after('total_efectivo')
                  ->comment('QR verificado por el cajero al cerrar');
            $table->decimal('diferencia_qr', 10, 2)->default(0)->after('diferencia')
                  ->comment('total_qr - ventas_qr');
        });

        // Los cortes ya cerrados se arquearon con la fórmula vieja, donde todo
        // el turno contaba como efectivo. Se copia total_ventas a
        // ventas_efectivo para que su diferencia histórica siga dando igual.
        DB::table('cortes_caja')
            ->where('estado', 'cerrado')
            ->update(['ventas_efectivo' => DB::raw('total_ventas')]);
    }

    public function down(): void
    {
        Schema::table('cortes_caja', function (Blueprint $table) {
            $table->dropColumn(['ventas_efectivo', 'ventas_qr', 'total_qr', 'diferencia_qr']);
        });
    }
};
