<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Donation;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MembershipRequest;
use App\Models\News;
use App\Models\Poll;
use App\Models\NewsletterSubscriber;
use App\Models\Sympathizer;
use App\Models\Volunteer;
use App\Models\AuditLog;
use App\Http\Controllers\Concerns\ScopesByPartyBranch;

class StatsController extends Controller
{
    use ScopesByPartyBranch;

    public function index()
    {
        $user = request()->user();
        $role = $user?->loadMissing('role')->role?->name;

        $votesCount = 0;
        if (class_exists(\App\Models\Vote::class)) {
            $votesCount = \App\Models\Vote::count();
        } elseif (class_exists(\App\Models\PollVote::class)) {
            $votesCount = \App\Models\PollVote::count();
        }

        $userQuery = User::query();
        $visibleUserBranchIds = $this->userBranchIdsVisibleTo($user);
        if ($visibleUserBranchIds !== null) {
            $userQuery->whereIn('party_branch_id', $visibleUserBranchIds);
        }

        $newsQuery = News::query();
        $membershipQuery = MembershipRequest::query();
        if ($user) {
            $this->applyBranchScope($newsQuery, $user);
            $branchIds = $this->userBranchIdsVisibleTo($user);
            if ($branchIds !== null) {
                $membershipQuery->where(function ($query) use ($branchIds) {
                    $query->whereIn('regional_branch_id', $branchIds)
                        ->orWhereIn('local_branch_id', $branchIds)
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->whereIn('party_branch_id', $branchIds));
                });
            }
        }

        // Helper to count users by role name inside the visible branch scope.
        $countByRole = fn($name) => (clone $userQuery)->whereHas('role', fn($q) => $q->where('name', $name))->count();

        $eventQuery = Event::query();
        if ($user) {
            $this->applyBranchScope($eventQuery, $user);
        }

        $base = [
            'events' => [
                'total'         => (clone $eventQuery)->count(),
                'registrations' => EventRegistration::whereIn('event_id', (clone $eventQuery)->pluck('id'))->count(),
            ],
            'news' => [
                'total'     => (clone $newsQuery)->count(),
                'published' => (clone $newsQuery)->where('is_published', true)->count(),
            ],
        ];

        if (in_array($role, ['local_official', 'regional_official'], true)) {
            return response()->json([
                ...$base,
                'users' => [
                    'total'        => (clone $userQuery)->count(),
                    'members'      => $countByRole('member'),
                    'sympathizers' => $countByRole('sympathizer'),
                    'volunteers'   => $countByRole('volunteer'),
                    'local_officials' => $countByRole('local_official'),
                    'regional_officials' => $countByRole('regional_official'),
                ],
                'membership_requests' => [
                    'pending'  => (clone $membershipQuery)->where('status', 'pending')->count(),
                    'approved' => (clone $membershipQuery)->where('status', 'approved')->count(),
                    'rejected' => (clone $membershipQuery)->where('status', 'rejected')->count(),
                ],
                'sympathizers' => [
                    'total' => class_exists(\App\Models\Sympathizer::class)
                        ? Sympathizer::query()->whereIn('party_branch_id', $visibleUserBranchIds ?? [])->count() : 0,
                ],
                'volunteers' => [
                    'total' => class_exists(\App\Models\Volunteer::class)
                        ? Volunteer::query()->whereIn('party_branch_id', $visibleUserBranchIds ?? [])->count() : 0,
                ],
                'scope' => [
                    'role' => $role,
                    'level' => 'partial_reports',
                    'message' => 'Partial activity, user, and content reports for the assigned branch.',
                ],
            ]);
        }

        $full = [
            'users' => [
                'total'        => (clone $userQuery)->count(),
                'members'      => $countByRole('member'),
                'sympathizers' => $countByRole('sympathizer'),
                'volunteers'   => $countByRole('volunteer'),
                'local_officials' => $countByRole('local_official'),
                'regional_officials' => $countByRole('regional_official'),
                'central_admins' => $countByRole('central_admin'),
                'supervisors' => $countByRole('super_admin'),
            ],
            'membership_requests' => [
                'pending'  => (clone $membershipQuery)->where('status', 'pending')->count(),
                'approved' => (clone $membershipQuery)->where('status', 'approved')->count(),
                'rejected' => (clone $membershipQuery)->where('status', 'rejected')->count(),
            ],
            'donations' => [
                'total'  => Donation::count(),
                'amount' => Donation::whereIn('status', ['completed', 'confirmed'])->sum('amount'),
            ],
            ...$base,
            'polls' => [
                'total' => Poll::count(),
                'votes' => $votesCount,
            ],
            'newsletter' => [
                'subscribers' => class_exists(\App\Models\NewsletterSubscriber::class)
                    ? NewsletterSubscriber::count() : 0,
            ],
            'sympathizers' => [
                'total' => class_exists(\App\Models\Sympathizer::class)
                    ? Sympathizer::count() : 0,
            ],
            'volunteers' => [
                'total' => class_exists(\App\Models\Volunteer::class)
                    ? Volunteer::count() : 0,
            ],
        ];

        if ($role === 'super_admin') {
            $full['audit'] = [
                'total' => class_exists(\App\Models\AuditLog::class) ? AuditLog::count() : 0,
                'recent_sensitive_actions' => class_exists(\App\Models\AuditLog::class)
                    ? AuditLog::latest()->limit(10)->get()
                    : [],
            ];
        }

        return response()->json($full);
    }
}
