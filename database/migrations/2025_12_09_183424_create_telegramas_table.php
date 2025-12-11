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
        Schema::create('telegramas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mesa_id')
                ->constrained('mesas')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('lista_id')
                ->constrained('listas')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->unsignedInteger('votos_diputados')->default(0);
            $table->unsignedInteger('votos_senadores')->default(0);
            $table->unsignedInteger('blancos')->default(0);
            $table->unsignedInteger('nulos')->default(0);
            $table->unsignedInteger('recurridos')->default(0);
            $table->string('usuario', 100);
            $table->timestamps();

            $table->index(['mesa_id', 'lista_id']);
            $table->unique(['mesa_id', 'lista_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegramas');
    }
};
