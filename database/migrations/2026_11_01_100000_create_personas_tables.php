<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identidad por encima de las panaderías.
 *
 * Cada panadería sigue teniendo su tabla de usuarios (con su rol y almacén),
 * pero el correo y la contraseña viven una sola vez acá: la persona es la que
 * inicia sesión y elige en cuál de sus panaderías entrar.
 *
 * Las tres tablas van en la base central.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personas', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->timestamps();
        });

        // Qué usuario de cada panadería corresponde a esta persona.
        Schema::create('persona_tenant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->string('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['persona_id', 'tenant_id']);
            $table->unique(['tenant_id', 'user_id']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        // Token de un solo uso que pasa la sesión del central a la panadería.
        Schema::create('tokens_entrada', function (Blueprint $table) {
            $table->id();
            // Se guarda el hash: el valor que viaja en la URL no queda en la base.
            $table->string('token', 64)->unique();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->string('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tokens_entrada');
        Schema::dropIfExists('persona_tenant');
        Schema::dropIfExists('personas');
    }
};
