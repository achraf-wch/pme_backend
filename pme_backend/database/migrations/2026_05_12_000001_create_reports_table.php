<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->string('sender_role', 40);
            $table->foreignId('sender_branch_id')->nullable()->constrained('party_branches')->nullOnDelete();
            $table->string('recipient_role', 40);
            $table->foreignId('recipient_branch_id')->nullable()->constrained('party_branches')->nullOnDelete();
            $table->string('title');
            $table->string('period_key', 40);
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->text('author_note')->nullable();
            $table->json('summary')->nullable();
            $table->string('pdf_path');
            $table->string('status', 20)->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
