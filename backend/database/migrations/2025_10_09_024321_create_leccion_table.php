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
        Schema::create('leccion', function (Blueprint $table) {
            $table->increments('id_leccion');
            $table->string('titulo_leccion', 100);
            $table->text('contenido_leccion');
            $table->string('video')->nullable();
            $table->integer('orden');
            $table->integer('id_curso');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leccion');
    }
};
