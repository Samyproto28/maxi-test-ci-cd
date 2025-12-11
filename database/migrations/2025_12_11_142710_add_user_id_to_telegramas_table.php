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
        Schema::table('telegramas', function (Blueprint $table) {
            // Add user_id as foreign key to users table
            $table->unsignedBigInteger('user_id')->nullable()->after('usuario');

            // Add foreign key constraint with onDelete('SET NULL')
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            // Add index for performance
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegramas', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['user_id']);

            // Drop index
            $table->dropIndex(['user_id']);

            // Drop column
            $table->dropColumn('user_id');
        });
    }
};
