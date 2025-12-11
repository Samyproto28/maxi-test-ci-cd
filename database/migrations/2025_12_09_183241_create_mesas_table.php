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
        Schema::create('mesas', function (Blueprint $table) {
            $table->id();
            $table->string('id_mesa', 20)->unique();
            $table->foreignId('provincia_id')
                ->constrained('provincias')
                ->onDelete('restrict')
                ->onUpdate('cascade');
            $table->string('circuito', 50)->nullable();
            $table->string('establecimiento', 200)->nullable();
            $table->unsignedInteger('electores');
            $table->timestamps();

            $table->index('id_mesa');
            $table->index(['provincia_id', 'circuito']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mesas');
    }
};
