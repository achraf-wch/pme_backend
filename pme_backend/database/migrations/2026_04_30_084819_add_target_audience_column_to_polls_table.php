<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('polls', function (Blueprint $table) {
            if (!Schema::hasColumn('polls', 'target_audience')) {
                $table->json('target_audience')->nullable()->after('end_date');
            }
        });
    }

    public function down()
    {
        Schema::table('polls', function (Blueprint $table) {
            if (Schema::hasColumn('polls', 'target_audience')) {
                $table->dropColumn('target_audience');
            }
        });
    }
};