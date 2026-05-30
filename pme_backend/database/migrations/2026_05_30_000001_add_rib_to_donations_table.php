<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            if (!Schema::hasColumn('donations', 'rib')) {
                $table->string('rib', 64)->nullable()->after('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            if (Schema::hasColumn('donations', 'rib')) {
                $table->dropColumn('rib');
            }
        });
    }
};
