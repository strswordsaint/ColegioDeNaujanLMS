<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('approval_status')->default('pending'); // pending, approved, rejected
            $table->dateTime('available_from')->nullable(); // When it appears
            $table->dateTime('available_until')->nullable(); // When it goes to archive
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['approval_status', 'available_from', 'available_until']);
        });
    }
};