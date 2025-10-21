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
        Schema::table('usuarios', function (Blueprint $table) {
            $table->foreign(['id_escuela'], 'fkusuarios688476')->references(['id_escuela'])->on('escuelas')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['id_rol'], 'fkusuarios985059')->references(['id_rol'])->on('roles')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropForeign('fkusuarios688476');
            $table->dropForeign('fkusuarios985059');
        });
    }
};
