<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Http\Controllers\Concerns\ScopesByPartyBranch;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    use ScopesByPartyBranch;

    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * Admin: all events regardless of audience.
     */
    public function index()
    {
        $query = Event::with(['creator', 'partyBranch'])->latest();

        if ($user = request()->user()) {
            $this->applyBranchScope($query, $user);
        }

        return response()->json($query->get());
    }

    /**
     * Public / member-facing: only events the user (or guest) can see.
     *
     * GET /api/events/feed
     * Works for:
     *  - unauthenticated guests → sees 'public' events only
     *  - authenticated users    → sees 'public' + their role
     */
    public function feed(Request $request)
    {
        $user = $request->user('sanctum') ?: $request->user();
        $role = optional($user?->loadMissing('role')->role)->name;

        $events = Event::with('creator')
            ->visibleTo($role)
            ->latest('start_time')
            ->get();

        return response()->json($events);
    }

    public function show($id)
    {
        return response()->json(Event::with(['creator', 'registrations'])->findOrFail($id));
    }

    /**
     * Admin: create an event.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'location'      => 'required|string|max:255',
            'start_time'    => 'required|date',
            'end_time'      => 'required|date|after:start_time',
            'max_attendees' => 'nullable|integer|min:1',
            'audience'      => 'required|array|min:1',
            'audience.*'    => 'string|in:public,visitor,sympathizer,volunteer,member,local_official,regional_official,central_admin,super_admin',
            'party_branch_id' => 'nullable|exists:party_branches,id',
            'attachment'    => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx|max:10240',
        ]);

        $user = $request->user();
        $data['created_by'] = $user->id;
        $data['party_branch_id'] = $this->branchIdForWrite($user, $data['party_branch_id'] ?? null);

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('events', 'public');
        }

        unset($data['attachment']);

        $event = Event::create($data);

        $this->notifications->notifyAudience($event->audience ?? ['public'], [
            'category' => 'event',
            'title' => 'Nouvelle activité',
            'body' => "{$event->title} - {$event->location}",
            'action_url' => '/events',
            'action_label' => 'Voir l’activité',
            'source_type' => 'event',
            'source_id' => $event->id,
        ], $user->id);

        return response()->json($event->load(['creator', 'partyBranch']), 201);
    }

    /**
     * Admin: update an event.
     */
    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        $this->ensureCanAccessEvent($request, $event);

        $data = $request->validate([
            'title'         => 'sometimes|required|string|max:255',
            'description'   => 'nullable|string',
            'location'      => 'sometimes|required|string|max:255',
            'start_time'    => 'sometimes|required|date',
            'end_time'      => 'sometimes|required|date|after:start_time',
            'max_attendees' => 'nullable|integer|min:1',
            'audience'      => 'sometimes|required|array|min:1',
            'audience.*'    => 'string|in:public,visitor,sympathizer,volunteer,member,local_official,regional_official,central_admin,super_admin',
            'party_branch_id' => 'nullable|exists:party_branches,id',
            'attachment'    => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx|max:10240',
        ]);

        if (array_key_exists('party_branch_id', $data)) {
            $data['party_branch_id'] = $this->branchIdForWrite($request->user(), $data['party_branch_id']);
        }

        if ($request->hasFile('attachment')) {
            if ($event->attachment_path) {
                Storage::disk('public')->delete($event->attachment_path);
            }
            $data['attachment_path'] = $request->file('attachment')->store('events', 'public');
        }

        unset($data['attachment']);
        $event->update($data);

        return response()->json($event->load(['creator', 'partyBranch']));
    }

    /**
     * Admin: delete an event.
     */
    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        $this->ensureCanAccessEvent(request(), $event);

        if ($event->attachment_path) {
            Storage::disk('public')->delete($event->attachment_path);
        }

        $event->delete();

        return response()->json(['message' => 'Deleted']);
    }

    /**
     * Admin: list registrations for an event.
     */
    public function registrations($id)
    {
        $event = Event::findOrFail($id);
        $this->ensureCanAccessEvent(request(), $event);

        $registrations = EventRegistration::where('event_id', $id)
            ->with(['user', 'user.partyBranch'])
            ->get();

        return response()->json($registrations);
    }

    /**
     * Member: register to an event (must be in audience).
     */
    public function register(Request $request, $id)
    {
        $user  = $request->user()->load('role');
        $event = Event::findOrFail($id);
        $role  = optional($user->role)->name;

        // Check user's role is in the event audience
        $audience = $event->audience ?? ['public'];
        if (!in_array('public', $audience) && !in_array($role, $audience)) {
            return response()->json(['message' => 'You are not allowed to register for this event'], 403);
        }

        // Check capacity
        if ($event->max_attendees && $event->registrations()->count() >= $event->max_attendees) {
            return response()->json(['message' => 'Event is full'], 400);
        }

        EventRegistration::firstOrCreate([
            'event_id' => $event->id,
            'user_id'  => $user->id,
        ]);

        $this->notifications->notifyAdmins([
            'category' => 'registration',
            'title' => 'Nouvelle inscription à une activité',
            'body' => "{$user->name} s’est inscrit à {$event->title}.",
            'action_url' => '/admin/events',
            'action_label' => 'Voir les inscriptions',
            'source_type' => 'event_registration',
            'source_id' => $event->id,
        ]);

        return response()->json(['message' => 'Registered']);
    }

    /**
     * Member: get own registrations.
     */
    public function myRegistrations(Request $request)
    {
        $registrations = EventRegistration::where('user_id', $request->user()->id)
            ->with('event')
            ->get();

        return response()->json($registrations);
    }

    private function ensureCanAccessEvent(Request $request, Event $event): void
    {
        $branchIds = $this->branchIdsVisibleTo($request->user());

        if ($branchIds !== null && !in_array((int) $event->party_branch_id, $branchIds, true)) {
            abort(403, 'You are not allowed to manage this event.');
        }
    }
}
