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
        Schema::create('auditoria', function (Blueprint $table) {
            $table->id();
            $table->string('tabla', 50)->index(); // Nombre de la tabla afectada
            $table->unsignedBigInteger('registro_id')->index(); // ID del registro modificado
            $table->enum('accion', ['CREATE', 'UPDATE', 'DELETE']);
            $table->json('datos_anteriores')->nullable(); // Estado anterior (NULL en CREATE)
            $table->json('datos_nuevos')->nullable(); // Estado nuevo (NULL en DELETE)
            $table->string('usuario', 100); // Usuario que realizó el cambio
            $table->timestamp('created_at')->useCurrent(); // Fecha/hora del cambio (solo created_at, no updated_at)

            // Índices compuestos para búsquedas eficientes
            $table->index(['tabla', 'registro_id']);
            $table->index(['usuario', 'created_at']);
            $table->index('created_at'); // Para consultas por fecha
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};
