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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade'); // Connects to Course
            
            $table->string('title');
            $table->longText('content')->nullable(); // The text for the AI Chatbot to read
            $table->string('video_url')->nullable(); 
            $table->string('attachment_path')->nullable(); 
            
            $table->json('tags')->nullable(); // Important: ["loops", "variables"] for Recommendations
            $table->integer('priority_order')->default(0); // Lesson 1, Lesson 2...
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
