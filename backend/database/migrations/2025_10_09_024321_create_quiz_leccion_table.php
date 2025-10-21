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
        Schema::create('quiz_leccion', function (Blueprint $table) {
            $table->increments('id_quiz_leccion');
            $table->string('titulo_quiz', 100);
            $table->smallInteger('porcetaje_aprobatorio');
            $table->integer('id_leccion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_leccion');
    }
};
