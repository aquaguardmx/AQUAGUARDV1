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
        Schema::table('nivel_trivia', function (Blueprint $table) {
            $table->foreign(['id_trivia'], 'fkniveltrivi354259')->references(['id_trivia'])->on('trivia')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nivel_trivia', function (Blueprint $table) {
            $table->dropForeign('fkniveltrivi354259');
        });
    }
};
