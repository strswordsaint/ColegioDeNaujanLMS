<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // 'pending' = waiting for teacher, 'approved' = enrolled, 'rejected' = blocked/history
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved')->after('course_id');
        });
    }

    public function down()
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};