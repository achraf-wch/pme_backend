<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->normalizeTable('events', 'created_by');
        $this->normalizeTable('news', 'author_id');
        $this->normalizeTable('media', 'uploaded_by');
    }

    public function down(): void
    {
        // Data normalization cannot be safely reversed.
    }

    private function normalizeTable(string $table, string $userColumn): void
    {
        if (!DB::getSchemaBuilder()->hasTable($table)
            || !DB::getSchemaBuilder()->hasColumn($table, 'party_branch_id')
            || !DB::getSchemaBuilder()->hasColumn($table, 'audience')) {
            return;
        }

        DB::table($table)
            ->join('users', "{$table}.{$userColumn}", '=', 'users.id')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->whereIn('roles.name', ['local_official', 'regional_official'])
            ->whereNotNull('users.party_branch_id')
            ->select("{$table}.id", 'users.party_branch_id')
            ->orderBy("{$table}.id")
            ->chunk(100, function ($rows) use ($table) {
                foreach ($rows as $row) {
                    DB::table($table)
                        ->where('id', $row->id)
                        ->update([
                            'party_branch_id' => $row->party_branch_id,
                            'audience' => json_encode(['member']),
                            'updated_at' => now(),
                        ]);
                }
            });
    }
};
