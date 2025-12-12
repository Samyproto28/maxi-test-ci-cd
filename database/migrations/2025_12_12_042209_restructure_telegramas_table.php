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
        // Eliminar columnas (el índice compuesto se elimina automáticamente con lista_id)
        Schema::table('telegramas', function (Blueprint $table) {
            // Eliminar columnas que se moveran a telegrama_votos
            $table->dropColumn(['lista_id', 'votos_diputados', 'votos_senadores']);

            // Agregar constraint UNIQUE en mesa_id (un telegrama por mesa)
            $table->unique('mesa_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegramas', function (Blueprint $table) {
            $table->dropUnique(['mesa_id']);
            
            // Restaurar columnas
            $table->foreignId('lista_id')->after('mesa_id')
                ->constrained('listas')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->unsignedInteger('votos_diputados')->default(0)->after('lista_id');
            $table->unsignedInteger('votos_senadores')->default(0)->after('votos_diputados');
            
            // Restaurar indice
            $table->index(['mesa_id', 'lista_id']);
            $table->unique(['mesa_id', 'lista_id']);
        });
    }
};
