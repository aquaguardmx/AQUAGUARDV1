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
        Schema::table('opciones_quiz', function (Blueprint $table) {
            $table->foreign(['id_pregunta_leccion'], 'fkopcionesqu581501')->references(['id_pregunta_leccion'])->on('preguntas_quiz_leccion')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opciones_quiz', function (Blueprint $table) {
            $table->dropForeign('fkopcionesqu581501');
        });
    }
};
