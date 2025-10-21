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
        Schema::table('preguntas_quiz_leccion', function (Blueprint $table) {
            $table->foreign(['id_quiz_leccion'], 'fkpreguntasq980735')->references(['id_quiz_leccion'])->on('quiz_leccion')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preguntas_quiz_leccion', function (Blueprint $table) {
            $table->dropForeign('fkpreguntasq980735');
        });
    }
};
