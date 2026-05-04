<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('party_branch_id')->nullable()->after('role_id')->constrained('party_branches')->nullOnDelete();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('party_branch_id')->nullable()->after('created_by')->constrained('party_branches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('party_branch_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('party_branch_id');
        });
    }
};
