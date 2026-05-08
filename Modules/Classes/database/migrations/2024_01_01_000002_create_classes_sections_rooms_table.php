<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('class_name')->unique();
            $table->timestamps();
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->string('section_name', 10);
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number', 20)->unique();
            $table->integer('capacity')->default(40);
            $table->string('floor', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('rooms');
    }
};
