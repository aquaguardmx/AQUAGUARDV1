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
        Schema::table('pregunta_encuesta', function (Blueprint $table) {
            $table->foreign(['id_nivel_trivia'], 'fkpreguntaen635572')->references(['id_nivel_trivia'])->on('nivel_trivia')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pregunta_encuesta', function (Blueprint $table) {
            $table->dropForeign('fkpreguntaen635572');
        });
    }
};
