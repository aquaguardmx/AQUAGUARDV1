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
        Schema::table('quiz_leccion', function (Blueprint $table) {
            $table->foreign(['id_leccion'], 'fkquizleccio84830')->references(['id_leccion'])->on('leccion')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_leccion', function (Blueprint $table) {
            $table->dropForeign('fkquizleccio84830');
        });
    }
};
