<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes');
            $table->foreignId('section_id')->nullable()->constrained('sections');
            $table->foreignId('subject_id')->constrained('subjects');
            $table->foreignId('teacher_id')->constrained('teachers');
            $table->foreignId('room_id')->nullable()->constrained('rooms');
            $table->enum('day', ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday']);
            $table->unsignedTinyInteger('period_number');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['class_id', 'section_id', 'day', 'period_number'], 'unique_class_period');
            $table->unique(['teacher_id', 'day', 'period_number'], 'unique_teacher_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetables');
    }
};
