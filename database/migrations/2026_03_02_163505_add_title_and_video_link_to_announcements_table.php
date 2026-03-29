<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('title')->nullable()->after('user_id');
            $table->string('video_link')->nullable()->after('title');
        });
    }

    public function down()
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn(['title', 'video_link']);
        });
    }
};