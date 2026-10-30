<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Licencia de cada panadería, sincronizada desde el panel central de INC Group.
 *
 * Vive en la base central (no en la de cada panadería) porque el panel del
 * sistema muestra el estado de todas y porque la sincronización llega antes de
 * que exista una sesión de panadería.
 *
 * version es la del central: solo se aplican versiones mayores, así un mensaje
 * atrasado no pisa un cambio más nuevo. payload guarda el último estado
 * recibido tal cual, para poder auditarlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_licenses', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->unique();
            $table->string('status', 20)->default('pending');
            $table->string('plan', 40)->default('basico');
            // null = sin límite.
            $table->unsignedInteger('max_admins')->nullable();
            $table->unsignedInteger('max_cajeros')->nullable();
            // null = no vence.
            $table->timestamp('valid_until')->nullable();
            $table->unsignedBigInteger('version')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_licenses');
    }
};
