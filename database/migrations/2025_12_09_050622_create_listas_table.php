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
        Schema::create('listas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('alianza', 100)->nullable();
            $table->foreignId('provincia_id')
                ->constrained('provincias')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->enum('cargo', ['DIPUTADOS', 'SENADORES']);
            $table->timestamps();

            $table->index(['provincia_id', 'cargo']);
            $table->unique(['nombre', 'provincia_id', 'cargo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listas');
    }
};
