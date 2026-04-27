<?php

namespace App\Http\Controllers;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PollController extends Controller
{
    // Admin: list all polls
    public function index()
    {
        $polls = Poll::with('options')->get();
        return response()->json($polls);
    }

    // Member: get active polls that user is allowed to vote on
   public function active(Request $request)
{
    $user = $request->user()->load('role'); // ✅ add this
    $now = now();

    $polls = Poll::with('options')
        ->where('start_date', '<=', $now)
        ->where('end_date', '>=', $now)
        ->get();

    $filtered = $polls->filter(function ($poll) use ($user) {
        return $poll->userCanVote($user);
    })->values();

    return response()->json($filtered);
}

    // Admin: create a new poll
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'target_audience' => 'required|array|min:1',
            'target_audience.*' => 'string|in:visitor,sympathizer,member,admin,local_official,central_admin,super_admin',
            'options' => 'required|array|min:2',
            'options.*' => 'required|string',
        ]);

        $poll = Poll::create([
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_secret' => false,
            'created_by' => Auth::id(),
            'target_audience' => $request->target_audience,
        ]);

        foreach ($request->options as $index => $optionText) {
            PollOption::create([
                'poll_id' => $poll->id,
                'option_text' => $optionText,
                'display_order' => $index,
            ]);
        }

        return response()->json($poll->load('options'), 201);
    }

    // Member: submit vote
    public function vote(Request $request)
    {
        $request->validate([
            'poll_id' => 'required|exists:polls,id',
            'option_id' => 'required|exists:poll_options,id',
        ]);

        $user = $request->user();
        $poll = Poll::findOrFail($request->poll_id);

        // Check poll is active
        $now = now();
        if ($poll->start_date > $now || $poll->end_date < $now) {
            return response()->json(['message' => 'Poll is not active'], 400);
        }

        // Check user can vote
        if (!$poll->userCanVote($user)) {
            return response()->json(['message' => 'You are not allowed to vote in this poll'], 403);
        }

        // Check if already voted
        $existing = Vote::where('poll_id', $poll->id)
                        ->where('user_id', $user->id)
                        ->exists();
        if ($existing) {
            return response()->json(['message' => 'You have already voted'], 400);
        }

        Vote::create([
            'poll_id' => $poll->id,
            'option_id' => $request->option_id,
            'user_id' => $user->id,
            'voted_at' => now(),
        ]);

        return response()->json(['message' => 'Vote recorded successfully']);
    }

    // Admin: get results of a poll
    public function results($id)
    {
        $poll = Poll::with('options')->findOrFail($id);
        $results = [];
        foreach ($poll->options as $option) {
            $results[] = [
                'option_id' => $option->id,
                'option_text' => $option->option_text,
                'votes' => $option->votes()->count(),
            ];
        }
        return response()->json([
            'poll' => $poll,
            'results' => $results,
            'total_votes' => $poll->votes()->count(),
        ]);
    }
}