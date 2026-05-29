<?php

namespace App\Http\Resources\Concerns;

trait FormatsApiRelations
{
    protected function userSummary($user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'party_branch_id' => $user->party_branch_id,
            'is_active' => $user->is_active,
        ];
    }

    protected function branchSummary($branch): ?array
    {
        if (!$branch) {
            return null;
        }

        return [
            'id' => $branch->id,
            'name' => $branch->name,
            'type' => $branch->type,
            'parent_id' => $branch->parent_id,
            'city' => $branch->city,
            'region' => $branch->region,
        ];
    }
}
