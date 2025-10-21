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
        Schema::table('cursos_inscritos_usuarios', function (Blueprint $table) {
            $table->foreign(['id_usuario'], 'fkcursos_ins186027')->references(['id_usuario'])->on('usuarios')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['id_curso'], 'fkcursos_ins56310')->references(['id_curso'])->on('cursos')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cursos_inscritos_usuarios', function (Blueprint $table) {
            $table->dropForeign('fkcursos_ins186027');
            $table->dropForeign('fkcursos_ins56310');
        });
    }
};
