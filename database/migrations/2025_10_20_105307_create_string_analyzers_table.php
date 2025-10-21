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
        Schema::create('string_analyzers', function (Blueprint $table) {
            $table->id();
            $table->string('hash_value');
            $table->string('input_string');
            $table->boolean('is_palindrome');
            $table->integer("length");
            $table->integer("word_count");
            $table->integer("unique_character_count");
            $table->json('character_frequency_map');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('string_analyzers');
    }
};