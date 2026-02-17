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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade'); // Who creates the course?
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('difficulty_level')->default('beginner'); // For AI Recommendations
            $table->string('thumbnail')->nullable(); // Image cover
            
            $table->boolean('is_published')->default(false); // Draft mode
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
