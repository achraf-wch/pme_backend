<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('polls', function (Blueprint $table) {
            $table->json('target_audience')->nullable()->after('is_secret');
        });
    }

    public function down()
    {
        Schema::table('polls', function (Blueprint $table) {
            $table->dropColumn('target_audience');
        });
    }
};