<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('membership_requests', 'review_stage')) {
                $table->string('review_stage', 40)->default('pending')->after('status');
            }
            if (!Schema::hasColumn('membership_requests', 'central_reviewed_by')) {
                $table->foreignId('central_reviewed_by')->nullable()->after('motivation')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('membership_requests', 'central_reviewed_at')) {
                $table->timestamp('central_reviewed_at')->nullable()->after('central_reviewed_by');
            }
            if (!Schema::hasColumn('membership_requests', 'super_reviewed_by')) {
                $table->foreignId('super_reviewed_by')->nullable()->after('central_reviewed_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('membership_requests', 'super_reviewed_at')) {
                $table->timestamp('super_reviewed_at')->nullable()->after('super_reviewed_by');
            }
            if (!Schema::hasColumn('membership_requests', 'country')) {
                $table->string('country', 120)->nullable()->after('motivation');
            }
            if (!Schema::hasColumn('membership_requests', 'regional_branch_id')) {
                $table->foreignId('regional_branch_id')->nullable()->after('country')->constrained('party_branches')->nullOnDelete();
            }
            if (!Schema::hasColumn('membership_requests', 'local_branch_id')) {
                $table->foreignId('local_branch_id')->nullable()->after('regional_branch_id')->constrained('party_branches')->nullOnDelete();
            }
            if (!Schema::hasColumn('membership_requests', 'age')) {
                $table->unsignedTinyInteger('age')->nullable()->after('local_branch_id');
            }
            if (!Schema::hasColumn('membership_requests', 'sex')) {
                $table->string('sex', 30)->nullable()->after('age');
            }
        });

        Schema::table('sympathizers', function (Blueprint $table) {
            if (!Schema::hasColumn('sympathizers', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('sympathizers', 'party_branch_id')) {
                $table->foreignId('party_branch_id')->nullable()->after('phone')->constrained('party_branches')->nullOnDelete();
            }
            if (!Schema::hasColumn('sympathizers', 'status')) {
                $table->string('status', 40)->default('pending')->after('message');
            }
            if (!Schema::hasColumn('sympathizers', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('sympathizers', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
        });

        Schema::table('volunteers', function (Blueprint $table) {
            if (!Schema::hasColumn('volunteers', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('volunteers', 'party_branch_id')) {
                $table->foreignId('party_branch_id')->nullable()->after('phone')->constrained('party_branches')->nullOnDelete();
            }
            if (!Schema::hasColumn('volunteers', 'status')) {
                $table->string('status', 40)->default('pending')->after('motivation');
            }
            if (!Schema::hasColumn('volunteers', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('volunteers', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
        });

        Schema::table('news', function (Blueprint $table) {
            if (!Schema::hasColumn('news', 'party_branch_id')) {
                $table->foreignId('party_branch_id')->nullable()->after('author_id')->constrained('party_branches')->nullOnDelete();
            }
        });

        Schema::table('media', function (Blueprint $table) {
            if (!Schema::hasColumn('media', 'party_branch_id')) {
                $table->foreignId('party_branch_id')->nullable()->after('uploaded_by')->constrained('party_branches')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            if (Schema::hasColumn('news', 'party_branch_id')) {
                $table->dropConstrainedForeignId('party_branch_id');
            }
        });

        Schema::table('media', function (Blueprint $table) {
            if (Schema::hasColumn('media', 'party_branch_id')) {
                $table->dropConstrainedForeignId('party_branch_id');
            }
        });

        Schema::table('volunteers', function (Blueprint $table) {
            foreach (['reviewed_at', 'status'] as $column) {
                if (Schema::hasColumn('volunteers', $column)) {
                    $table->dropColumn($column);
                }
            }
            foreach (['reviewed_by', 'party_branch_id', 'user_id'] as $column) {
                if (Schema::hasColumn('volunteers', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }
        });

        Schema::table('sympathizers', function (Blueprint $table) {
            foreach (['reviewed_at', 'status'] as $column) {
                if (Schema::hasColumn('sympathizers', $column)) {
                    $table->dropColumn($column);
                }
            }
            foreach (['reviewed_by', 'party_branch_id', 'user_id'] as $column) {
                if (Schema::hasColumn('sympathizers', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }
        });

        Schema::table('membership_requests', function (Blueprint $table) {
            foreach (['sex', 'age', 'country', 'super_reviewed_at', 'central_reviewed_at', 'review_stage'] as $column) {
                if (Schema::hasColumn('membership_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
            foreach (['local_branch_id', 'regional_branch_id', 'super_reviewed_by', 'central_reviewed_by'] as $column) {
                if (Schema::hasColumn('membership_requests', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }
        });
    }
};
