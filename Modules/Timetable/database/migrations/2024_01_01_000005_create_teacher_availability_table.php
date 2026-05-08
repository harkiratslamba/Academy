<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedTinyInteger('period_number')->nullable();
            $table->enum('status', ['available', 'unavailable', 'on_leave'])->default('available');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['teacher_id', 'date', 'period_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_availabilities');
    }
};
