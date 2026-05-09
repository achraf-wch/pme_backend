<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            if (!Schema::hasColumn('news', 'type')) {
                $table->string('type', 40)->default('news')->after('title');
            }
            if (!Schema::hasColumn('news', 'topic')) {
                $table->string('topic')->nullable()->after('type');
            }
            if (!Schema::hasColumn('news', 'region')) {
                $table->string('region')->nullable()->after('topic');
            }
            if (!Schema::hasColumn('news', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('image_path');
            }
            if (!Schema::hasColumn('news', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('published_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            foreach (['type', 'topic', 'region', 'attachment_path', 'archived_at'] as $column) {
                if (Schema::hasColumn('news', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
