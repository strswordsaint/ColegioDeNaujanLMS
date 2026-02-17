<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('id');
            $table->string('school_id')->nullable()->after('email');
            $table->string('contact_number')->nullable()->after('school_id');
            $table->string('avatar')->nullable()->after('name');
            // Make password nullable because the initial Google creation won't have one
            $table->string('password')->nullable()->change(); 
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'school_id', 'contact_number', 'avatar']);
            // We cannot easily revert password to not null without data, so we leave it
        });
    }
};