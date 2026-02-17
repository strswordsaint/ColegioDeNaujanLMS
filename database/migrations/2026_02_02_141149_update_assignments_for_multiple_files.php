<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('assignments', function (Blueprint $table) {
            // Add new JSON column
            $table->text('attachment_paths')->nullable()->after('description');
            // Drop old column
            $table->dropColumn('attachment_path');
        });
    }

    public function down()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('attachment_path')->nullable();
            $table->dropColumn('attachment_paths');
        });
    }
};