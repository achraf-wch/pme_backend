<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesByPartyBranch;
use App\Models\Donation;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Media;
use App\Models\MembershipRequest;
use App\Models\News;
use App\Models\PartyBranch;
use App\Models\Poll;
use App\Models\Report;
use App\Models\Role;
use App\Models\Sympathizer;
use App\Models\User;
use App\Models\Volunteer;
use App\Models\Vote;
use App\Services\NotificationService;
use App\Services\SimplePdfService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    use ScopesByPartyBranch;

    public function __construct(
        private SimplePdfService $pdf,
        private NotificationService $notifications,
    ) {
    }

    public function sent(Request $request)
    {
        return response()->json(
            Report::with(['sender', 'senderBranch', 'recipientBranch'])
                ->where('sender_id', $request->user()->id)
                ->latest()
                ->get()
        );
    }

    public function received(Request $request)
    {
        $user = $request->user()->loadMissing('role');
        $role = $user->role?->name;

        $reports = Report::with(['sender', 'senderBranch', 'recipientBranch'])
            ->where('status', 'sent')
            ->where('recipient_role', $role)
            ->when(
                $role === 'regional_official',
                fn ($query) => $query->where('recipient_branch_id', $user->party_branch_id),
                fn ($query) => $query->whereNull('recipient_branch_id')
            )
            ->latest('sent_at')
            ->get();

        return response()->json($reports);
    }

    public function store(Request $request)
    {
        $user = $request->user()->loadMissing(['role', 'partyBranch']);
        $role = $user->role?->name;

        if (!in_array($role, ['local_official', 'regional_official', 'central_admin'], true)) {
            return response()->json(['message' => 'This role cannot send reports upward.'], 403);
        }

        $data = $request->validate([
            'period_key' => 'required|string|in:last_24_hours,last_week,last_month,last_year',
            'title' => 'nullable|string|max:180',
            'author_note' => 'nullable|string|max:5000',
            'send' => 'nullable|boolean',
        ]);

        [$periodStart, $periodEnd] = $this->periodFor($data['period_key']);
        [$recipientRole, $recipientBranchId] = $this->recipientFor($user);
        $summary = $this->summaryFor($user, $periodStart, $periodEnd);
        $title = $data['title'] ?: $this->defaultTitle($user, $data['period_key']);
        $send = (bool) ($data['send'] ?? false);

        $lines = $this->reportLines(
            $title,
            $user,
            $recipientRole,
            $recipientBranchId,
            $periodStart,
            $periodEnd,
            $summary,
            $data['author_note'] ?? null
        );

        $pdfPath = 'reports/' . now()->format('Y/m') . '/report-' . uniqid() . '.pdf';
        Storage::disk('public')->put($pdfPath, $this->pdf->make($lines));

        $report = Report::create([
            'sender_id' => $user->id,
            'sender_role' => $role,
            'sender_branch_id' => $user->party_branch_id,
            'recipient_role' => $recipientRole,
            'recipient_branch_id' => $recipientBranchId,
            'title' => $title,
            'period_key' => $data['period_key'],
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'author_note' => $data['author_note'] ?? null,
            'summary' => $summary,
            'pdf_path' => $pdfPath,
            'status' => $send ? 'sent' : 'draft',
            'sent_at' => $send ? now() : null,
        ]);

        if ($send) {
            $this->notifications->notifyAudience([$recipientRole], [
                'category' => 'report',
                'title' => 'Nouveau rapport reçu',
                'body' => "{$user->name} a envoyé un rapport.",
                'action_url' => '/dashboard',
                'action_label' => 'Voir les rapports',
                'source_type' => 'report',
                'source_id' => $report->id,
            ], $user->id, $recipientBranchId);
        }

        return response()->json($report->load(['sender', 'senderBranch', 'recipientBranch']), 201);
    }

    public function send(Request $request, Report $report)
    {
        if ($report->sender_id !== $request->user()->id) {
            abort(403);
        }

        if ($report->status !== 'sent') {
            $report->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }

        return response()->json($report->load(['sender', 'senderBranch', 'recipientBranch']));
    }

    public function download(Request $request, Report $report)
    {
        if (!$this->canAccess($request->user()->loadMissing('role'), $report)) {
            abort(403);
        }

        if (!Storage::disk('public')->exists($report->pdf_path)) {
            abort(404);
        }

        return Storage::disk('public')->download($report->pdf_path, "rapport-{$report->id}.pdf");
    }

    private function periodFor(string $key): array
    {
        $end = now();
        $start = match ($key) {
            'last_24_hours' => now()->subDay(),
            'last_week' => now()->subWeek(),
            'last_month' => now()->subMonth(),
            'last_year' => now()->subYear(),
        };

        return [$start, $end];
    }

    private function recipientFor(User $user): array
    {
        $role = $user->role?->name;

        if ($role === 'local_official') {
            $regionalBranchId = $user->partyBranch?->parent_id;
            if (!$regionalBranchId) {
                abort(422, 'No regional branch found for this local official.');
            }

            return ['regional_official', (int) $regionalBranchId];
        }

        if ($role === 'regional_official') {
            return ['central_admin', null];
        }

        return ['super_admin', null];
    }

    private function defaultTitle(User $user, string $periodKey): string
    {
        $label = match ($periodKey) {
            'last_24_hours' => '24 dernieres heures',
            'last_week' => 'derniere semaine',
            'last_month' => 'dernier mois',
            'last_year' => 'derniere annee',
        };

        return 'Rapport ' . ($user->partyBranch?->name ?: $user->role?->name) . ' - ' . $label;
    }

    private function summaryFor(User $user, $periodStart, $periodEnd): array
    {
        $branchIds = $this->managedBranchIdsVisibleTo($user);
        $userBranchIds = $this->userBranchIdsVisibleTo($user);

        $users = User::query();
        $this->scopeUserBranches($users, $userBranchIds);

        $events = Event::query();
        $this->scopeContentBranches($events, $branchIds);

        $news = News::query();
        $this->scopeContentBranches($news, $branchIds);

        $media = Media::query();
        $this->scopeContentBranches($media, $branchIds);

        $memberships = MembershipRequest::query();
        if ($branchIds !== null) {
            $memberships->where(function ($query) use ($branchIds) {
                $query->whereIn('regional_branch_id', $branchIds)
                    ->orWhereIn('local_branch_id', $branchIds)
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->whereIn('party_branch_id', $branchIds));
            });
        }

        $donations = Donation::query();
        if ($userBranchIds !== null) {
            $donations->whereHas('user', fn ($query) => $query->whereIn('party_branch_id', $userBranchIds));
        }

        $polls = Poll::query();
        if ($userBranchIds !== null) {
            $polls->whereHas('creator', fn ($query) => $query->whereIn('party_branch_id', $userBranchIds));
        }

        $pollIds = (clone $polls)->pluck('id');

        $eventIds = (clone $events)->pluck('id');

        return [
            'users' => [
                'total' => (clone $users)->count(),
                'new' => (clone $users)->whereBetween('created_at', [$periodStart, $periodEnd])->count(),
                'members' => $this->countUsersByRole($users, 'member'),
                'sympathizers' => $this->countUsersByRole($users, 'sympathizer'),
                'volunteers' => $this->countUsersByRole($users, 'volunteer'),
            ],
            'membership_requests' => [
                'created' => (clone $memberships)->whereBetween('created_at', [$periodStart, $periodEnd])->count(),
                'pending' => (clone $memberships)->where('status', 'pending')->count(),
                'approved' => (clone $memberships)->where('status', 'approved')->count(),
                'rejected' => (clone $memberships)->where('status', 'rejected')->count(),
            ],
            'events' => [
                'created' => (clone $events)->whereBetween('created_at', [$periodStart, $periodEnd])->count(),
                'upcoming' => (clone $events)->where('start_time', '>=', now())->count(),
                'registrations' => EventRegistration::whereIn('event_id', $eventIds)
                    ->whereBetween('registered_at', [$periodStart, $periodEnd])
                    ->count(),
            ],
            'content' => [
                'news_created' => (clone $news)->whereBetween('created_at', [$periodStart, $periodEnd])->count(),
                'news_published' => (clone $news)->where('is_published', true)->count(),
                'media_uploaded' => (clone $media)->whereBetween('created_at', [$periodStart, $periodEnd])->count(),
            ],
            'votes' => [
                'polls_created' => (clone $polls)->whereBetween('created_at', [$periodStart, $periodEnd])->count(),
                'votes_cast' => Vote::whereIn('poll_id', $pollIds)
                    ->whereBetween('voted_at', [$periodStart, $periodEnd])
                    ->count(),
            ],
            'finance' => [
                'donations_count' => (clone $donations)->whereBetween('created_at', [$periodStart, $periodEnd])->count(),
                'donations_amount' => (float) (clone $donations)
                    ->whereIn('status', ['completed', 'confirmed'])
                    ->whereBetween('created_at', [$periodStart, $periodEnd])
                    ->sum('amount'),
            ],
            'engagement' => [
                'sympathizer_requests' => $this->requestCount(Sympathizer::query(), $branchIds, $periodStart, $periodEnd),
                'volunteer_requests' => $this->requestCount(Volunteer::query(), $branchIds, $periodStart, $periodEnd),
            ],
        ];
    }

    private function countUsersByRole(Builder $query, string $role): int
    {
        return (clone $query)->whereHas('role', fn ($roleQuery) => $roleQuery->where('name', $role))->count();
    }

    private function requestCount(Builder $query, ?array $branchIds, $periodStart, $periodEnd): int
    {
        if ($branchIds !== null) {
            $query->whereIn('party_branch_id', $branchIds);
        }

        return $query->whereBetween('created_at', [$periodStart, $periodEnd])->count();
    }

    private function scopeUserBranches(Builder $query, ?array $branchIds): void
    {
        if ($branchIds !== null) {
            $query->whereIn('party_branch_id', $branchIds);
        }
    }

    private function scopeContentBranches(Builder $query, ?array $branchIds): void
    {
        if ($branchIds !== null) {
            $query->where(function ($scoped) use ($branchIds) {
                $scoped->whereNull('party_branch_id')->orWhereIn('party_branch_id', $branchIds);
            });
        }
    }

    private function reportLines(
        string $title,
        User $user,
        string $recipientRole,
        ?int $recipientBranchId,
        $periodStart,
        $periodEnd,
        array $summary,
        ?string $authorNote
    ): array {
        $recipientBranch = $recipientBranchId ? PartyBranch::find($recipientBranchId)?->name : 'National';
        $recipientRoleLabel = Role::where('name', $recipientRole)->value('name') ?: $recipientRole;

        return [
            'PME - RAPPORT ORGANISATIONNEL',
            '================================',
            '',
            $title,
            'Auteur: ' . $user->name . ' (' . $user->role?->name . ')',
            'Antenne auteur: ' . ($user->partyBranch?->name ?: 'National'),
            'Destinataire: ' . $recipientRoleLabel . ' - ' . $recipientBranch,
            'Periode: ' . $periodStart->format('d/m/Y H:i') . ' au ' . $periodEnd->format('d/m/Y H:i'),
            'Genere le: ' . now()->format('d/m/Y H:i'),
            '',
            'UTILISATEURS',
            '- Total visible: ' . $summary['users']['total'],
            '- Nouveaux comptes: ' . $summary['users']['new'],
            '- Membres: ' . $summary['users']['members'],
            '- Sympathisants: ' . $summary['users']['sympathizers'],
            '- Benevoles: ' . $summary['users']['volunteers'],
            '',
            'ADHESIONS ET ENGAGEMENT',
            '- Demandes creees: ' . $summary['membership_requests']['created'],
            '- En attente: ' . $summary['membership_requests']['pending'],
            '- Approuvees: ' . $summary['membership_requests']['approved'],
            '- Refusees: ' . $summary['membership_requests']['rejected'],
            '- Demandes sympathisants: ' . $summary['engagement']['sympathizer_requests'],
            '- Demandes benevoles: ' . $summary['engagement']['volunteer_requests'],
            '',
            'ACTIVITES',
            '- Evenements crees: ' . $summary['events']['created'],
            '- Evenements a venir: ' . $summary['events']['upcoming'],
            '- Reservations sur la periode: ' . $summary['events']['registrations'],
            '',
            'CONTENUS',
            '- Actualites creees: ' . $summary['content']['news_created'],
            '- Actualites publiees: ' . $summary['content']['news_published'],
            '- Medias ajoutes: ' . $summary['content']['media_uploaded'],
            '',
            'VOTES',
            '- Votes crees: ' . $summary['votes']['polls_created'],
            '- Participations: ' . $summary['votes']['votes_cast'],
            '',
            'FINANCES',
            '- Contributions: ' . $summary['finance']['donations_count'],
            '- Montant confirme: ' . number_format($summary['finance']['donations_amount'], 2, ',', ' ') . ' MAD',
            '',
            'POINT DE VUE / SUGGESTIONS',
            $authorNote ?: 'Aucune suggestion ajoutee.',
        ];
    }

    private function canAccess(User $user, Report $report): bool
    {
        if ((int) $report->sender_id === (int) $user->id) {
            return true;
        }

        $role = $user->role?->name;

        if ($report->status !== 'sent' || $report->recipient_role !== $role) {
            return false;
        }

        if ($role === 'regional_official') {
            return (int) $report->recipient_branch_id === (int) $user->party_branch_id;
        }

        return in_array($role, ['central_admin', 'super_admin'], true) && $report->recipient_branch_id === null;
    }
}
