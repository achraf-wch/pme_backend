<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            if (!Schema::hasColumn('donations', 'frequency')) {
                $table->string('frequency', 20)->default('once')->after('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            if (Schema::hasColumn('donations', 'frequency')) {
                $table->dropColumn('frequency');
            }
        });
    }
};
