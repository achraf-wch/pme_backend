<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            if (!Schema::hasColumn('news', 'auto_share_social')) {
                $table->boolean('auto_share_social')->default(false)->after('audience');
            }
            if (!Schema::hasColumn('news', 'social_channels')) {
                $table->json('social_channels')->nullable()->after('auto_share_social');
            }
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            foreach (['social_channels', 'auto_share_social'] as $column) {
                if (Schema::hasColumn('news', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
