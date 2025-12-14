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
        Schema::create('residencias', function (Blueprint $table) {
            $table->id('id_residencia');
            $table->string('nombre');
            $table->string('schema_relacionado')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('fecha_creacion')->nullable();
            $table->integer('usuario_creacion')->nullable();
            $table->timestamp('fecha_modificacion')->nullable();
            $table->integer('usuario_modificacion')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('residencias');
    }
};

