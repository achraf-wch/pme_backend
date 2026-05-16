<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('polls', function (Blueprint $table) {
            if (!Schema::hasColumn('polls', 'party_branch_id')) {
                $table->foreignId('party_branch_id')->nullable()->after('created_by')->constrained('party_branches')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('polls', function (Blueprint $table) {
            if (Schema::hasColumn('polls', 'party_branch_id')) {
                $table->dropConstrainedForeignId('party_branch_id');
            }
        });
    }
};
