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
        Schema::table('leccion', function (Blueprint $table) {
            $table->foreign(['id_curso'], 'fkleccion554499')->references(['id_curso'])->on('cursos')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leccion', function (Blueprint $table) {
            $table->dropForeign('fkleccion554499');
        });
    }
};
