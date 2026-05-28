<?php

namespace App\Http\Controllers;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Vote;
use App\Http\Requests\StorePollRequest;
use App\Http\Requests\UpdatePollRequest;
use App\Http\Requests\VotePollRequest;
use App\Http\Resources\PollResource;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Concerns\RecordsAuditLogs;
use App\Http\Controllers\Concerns\ScopesByPartyBranch;
use App\Services\NotificationService;

class PollController extends Controller
{
    use RecordsAuditLogs;
    use ScopesByPartyBranch;

    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * Admin: list all polls.
     */
    public function index()
    {
        $polls = Poll::with(['options', 'partyBranch'])->latest()->get();
        return PollResource::collection($polls);
    }

    /**
     * Public / member-facing: active polls the user (or guest) can see.
     *
     * GET /api/polls/feed
     * - Guests see polls with 'public' in target_audience
     * - Authenticated users see 'public' + their role
     *
     * Note: 'public' audience means the poll is shown (read-only / displayed)
     * on the homepage. Voting still requires userCanVote() to pass.
     */
    public function feed(Request $request)
    {
        $user = ($request->user('sanctum') ?: $request->user())?->load('role');
        $role = optional($user?->role)->name;
        $now  = now();

        $polls = Poll::with(['options', 'partyBranch'])
            ->where('end_date', '>=', $now)
            ->visibleTo($role, $user)
            ->get()
            ->map(function ($poll) use ($user) {
                $poll->can_vote = $user ? $poll->userCanVote($user) : false;
                $poll->has_voted = $user
                    ? Vote::where('poll_id', $poll->id)->where('user_id', $user->id)->exists()
                    : false;
                return $poll;
            });

        return PollResource::collection($polls);
    }

    /**
     * Member: get active polls the authenticated user is allowed to vote on.
     */
    public function active(Request $request)
    {
        $user = $request->user()->load('role');
        $now  = now();

        $polls = Poll::with(['options', 'partyBranch'])
            ->where('end_date', '>=', $now)
            ->visibleTo(optional($user->role)->name, $user)
            ->get()
            ->map(function ($poll) use ($user) {
                $poll->can_vote = $poll->userCanVote($user);
                $poll->has_voted = Vote::where('poll_id', $poll->id)
                    ->where('user_id', $user->id)
                    ->exists();
                return $poll;
            })
            ->values();

        return PollResource::collection($polls);
    }

    /**
     * Admin: create a new poll.
     */
    public function store(StorePollRequest $request)
    {
        $data = $request->validated();

        $user = $request->user();
        $this->ensureAudienceAllowedForWrite($user, $data['target_audience']);
        $this->ensureCanManageBranch($user, $data['party_branch_id'] ?? null);
        $branchId = $this->branchIdForWrite($user, $data['party_branch_id'] ?? null);

        $poll = DB::transaction(function () use ($request, $data, $branchId, $user) {
            $poll = Poll::create([
                'title'           => $data['title'],
                'description'     => $data['description'] ?? null,
                'start_date'      => $data['start_date'],
                'end_date'        => $data['end_date'],
                'is_secret'       => $request->boolean('is_secret'),
                'created_by'      => $user->id,
                'party_branch_id' => $branchId,
                'target_audience' => $data['target_audience'],
            ]);

            foreach ($data['options'] as $index => $optionText) {
                PollOption::create([
                    'poll_id'       => $poll->id,
                    'option_text'   => $optionText,
                    'display_order' => $index,
                ]);
            }

            $this->audit($request, 'poll.created', $poll, [
                'target_audience' => $poll->target_audience,
                'party_branch_id' => $poll->party_branch_id,
            ]);

            $this->notifications->notifyAudience($poll->target_audience ?? ['public'], [
                'category' => 'poll',
                'title' => 'Nouveau vote ouvert',
                'body' => $poll->title,
                'action_url' => '/member/active-polls',
                'action_label' => 'Participer',
                'source_type' => 'poll',
                'source_id' => $poll->id,
            ], $user->id, $poll->party_branch_id);

            return $poll;
        });

        return (new PollResource($poll->load(['options', 'partyBranch'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Member: submit a vote.
     */
    public function vote(VotePollRequest $request)
    {
        $data = $request->validated();
        try {
            DB::transaction(function () use ($request, $data) {
                $user = $request->user()->load('role');
                $poll = Poll::whereKey($data['poll_id'])->lockForUpdate()->firstOrFail();
                $option = PollOption::where('id', $data['option_id'])
                    ->where('poll_id', $poll->id)
                    ->lockForUpdate()
                    ->first();

                if (!$option) {
                    throw new HttpResponseException(response()->json(['message' => 'Selected option does not belong to this poll'], 422));
                }

                $now  = now();

                if ($poll->start_date > $now || $poll->end_date < $now) {
                    throw new HttpResponseException(response()->json(['message' => 'Poll is not active'], 400));
                }

                if (!$poll->userCanVote($user)) {
                    throw new HttpResponseException(response()->json(['message' => 'You are not allowed to vote in this poll'], 403));
                }

                $existing = Vote::where('poll_id', $poll->id)
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->exists();

                if ($existing) {
                    throw new HttpResponseException(response()->json(['message' => 'You have already voted'], 400));
                }

                Vote::create([
                    'poll_id'   => $poll->id,
                    'option_id' => $data['option_id'],
                    'user_id'   => $user->id,
                    'voted_at'  => now(),
                ]);

                $this->audit($request, 'poll.vote_cast', $poll, [
                    'is_secret' => $poll->is_secret,
                    'option_id' => $poll->is_secret ? null : $data['option_id'],
                ]);
            });
        } catch (QueryException $exception) {
            if (in_array($exception->getCode(), ['23000', '23505'], true)) {
                return response()->json(['message' => 'You have already voted'], 400);
            }

            throw $exception;
        }

        return response()->json(['message' => 'Vote recorded successfully']);
    }

    public function update(UpdatePollRequest $request, $id)
    {
        $poll = Poll::withCount('votes')->findOrFail($id);

        if ($poll->votes_count > 0 && $request->has('options')) {
            return response()->json(['message' => 'Poll options cannot be changed after votes are cast'], 422);
        }

        $data = $request->validated();

        if (array_key_exists('target_audience', $data)) {
            $this->ensureAudienceAllowedForWrite($request->user(), $data['target_audience']);
        }

        if (array_key_exists('party_branch_id', $data)) {
            $this->ensureCanManageBranch($request->user(), $data['party_branch_id']);
            $data['party_branch_id'] = $this->branchIdForWrite($request->user(), $data['party_branch_id']);
        }

        if (array_key_exists('is_secret', $data)) {
            $data['is_secret'] = $request->boolean('is_secret');
        }

        $options = $data['options'] ?? null;
        unset($data['options']);

        $poll->update($data);

        if ($options) {
            $poll->options()->delete();
            foreach ($options as $index => $optionText) {
                PollOption::create([
                    'poll_id' => $poll->id,
                    'option_text' => $optionText,
                    'display_order' => $index,
                ]);
            }
        }

        $this->audit($request, 'poll.updated', $poll, [
            'target_audience' => $poll->target_audience,
            'party_branch_id' => $poll->party_branch_id,
        ]);

        return new PollResource($poll->load(['options', 'partyBranch']));
    }

    public function destroy(Request $request, $id)
    {
        $poll = Poll::findOrFail($id);
        $this->audit($request, 'poll.deleted', $poll, ['title' => $poll->title]);
        $poll->delete();

        return response()->json(['message' => 'Deleted']);
    }

    /**
     * Admin: get results of a poll.
     */
    public function results($id)
    {
        $poll    = Poll::with('options')->findOrFail($id);
        $results = [];

        foreach ($poll->options as $option) {
            $results[] = [
                'option_id'   => $option->id,
                'option_text' => $option->option_text,
                'votes'       => $option->votes()->count(),
            ];
        }

        return response()->json([
            'poll'        => new PollResource($poll),
            'results'     => $results,
            'total_votes' => $poll->votes()->count(),
        ]);
    }
}
