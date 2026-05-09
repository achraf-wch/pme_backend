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

        // Helper to count users by role name
        $countByRole = fn($name) => User::whereHas('role', fn($q) => $q->where('name', $name))->count();

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
                'total'     => News::count(),
                'published' => News::where('is_published', true)->count(),
            ],
        ];

        if (in_array($role, ['local_official', 'regional_official'], true)) {
            return response()->json([
                ...$base,
                'scope' => [
                    'role' => $role,
                    'level' => 'partial_reports',
                    'message' => 'Partial activity reports for local and regional officials.',
                ],
            ]);
        }

        $full = [
            'users' => [
                'total'        => User::count(),
                'members'      => $countByRole('member'),
                'sympathizers' => $countByRole('sympathizer'),
                'volunteers'   => $countByRole('volunteer'),
                'local_officials' => $countByRole('local_official'),
                'regional_officials' => $countByRole('regional_official'),
                'central_admins' => $countByRole('central_admin'),
                'supervisors' => $countByRole('super_admin'),
            ],
            'membership_requests' => [
                'pending'  => MembershipRequest::where('status', 'pending')->count(),
                'approved' => MembershipRequest::where('status', 'approved')->count(),
                'rejected' => MembershipRequest::where('status', 'rejected')->count(),
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
