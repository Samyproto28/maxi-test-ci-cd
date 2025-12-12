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
        Schema::create('telegrama_votos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegrama_id')
                ->constrained('telegramas')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('lista_id')
                ->constrained('listas')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->unsignedInteger('votos_diputados')->default(0);
            $table->unsignedInteger('votos_senadores')->default(0);
            $table->timestamps();

            $table->unique(['telegrama_id', 'lista_id']);
            $table->index('telegrama_id');
            $table->index('lista_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegrama_votos');
    }
};
