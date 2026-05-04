<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['national', 'regional', 'local'])->default('local');
            $table->foreignId('parent_id')->nullable()->constrained('party_branches')->nullOnDelete();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_branches');
    }
};
