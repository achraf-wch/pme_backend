<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            if (!Schema::hasColumn('media', 'audience')) {
                $table->json('audience')->nullable()->after('file_size');
            }
        });

        DB::table('media')->whereNull('audience')->update(['audience' => json_encode(['public'])]);
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            if (Schema::hasColumn('media', 'audience')) {
                $table->dropColumn('audience');
            }
        });
    }
};
