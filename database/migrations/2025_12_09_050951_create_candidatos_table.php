<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Crea tabla candidatos con relaciones a listas y provincias.
     * Incluye constraint único para evitar duplicados en posición de lista.
     */
    public function up(): void
    {
        Schema::create('candidatos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);

            // Foreign key a listas con cascade delete/update
            $table->foreignId('lista_id')
                  ->constrained('listas')
                  ->onDelete('cascade')  // Si se elimina lista, eliminar candidatos
                  ->onUpdate('cascade');

            // Foreign key a provincias con restrict delete y cascade update
            $table->foreignId('provincia_id')
                  ->constrained('provincias')
                  ->onDelete('restrict')  // No permitir eliminar provincia si tiene candidatos
                  ->onUpdate('cascade');

            $table->enum('cargo', ['DIPUTADOS', 'SENADORES']);
            $table->unsignedInteger('orden');  // Posición en lista (1, 2, 3...)
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['lista_id', 'orden']);
            $table->unique(['lista_id', 'orden']);  // Un candidato por posición en lista
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidatos');
    }
};
