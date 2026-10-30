<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quienes administran el servicio desde el dominio central: dan de alta
 * panaderías y sus administradores. No son usuarios de ninguna panadería;
 * esos viven en la base de cada una.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('super_admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('super_admins');
    }
};
