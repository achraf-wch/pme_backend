<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('static_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('static_pages', 'images')) {
                $table->json('images')->nullable()->after('content');
            }
        });
    }

    public function down(): void
    {
        Schema::table('static_pages', function (Blueprint $table) {
            if (Schema::hasColumn('static_pages', 'images')) {
                $table->dropColumn('images');
            }
        });
    }
};
