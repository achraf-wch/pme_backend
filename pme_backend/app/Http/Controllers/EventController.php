<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRecap;
use App\Models\EventRegistration;
use App\Http\Controllers\Concerns\ScopesByPartyBranch;
use App\Http\Requests\StoreEventRecapRequest;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventRecapResource;
use App\Http\Resources\EventRegistrationResource;
use App\Http\Resources\EventResource;
use App\Services\NotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

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
        $query = Event::with(['creator', 'partyBranch'])->withCount('recaps')->latest();

        if ($user = request()->user()) {
            $this->applyManagedBranchScope($query, $user);
        }

        return EventResource::collection($query->get());
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
            ->visibleTo($role, $user)
            ->latest('start_time')
            ->get()
            ->map(fn (Event $event) => $this->attachRegistrationState($event, $user));

        return EventResource::collection($events);
    }

    public function show($id)
    {
        $user = request()->user('sanctum') ?: request()->user();
        $role = optional($user?->loadMissing('role')->role)->name;

        $event = Event::with(['creator', 'partyBranch', 'recaps.creator'])
            ->visibleTo($role, $user)
            ->findOrFail($id);

        return new EventResource($this->attachRegistrationState($event, $user));
    }

    /**
     * Admin: create an event.
     */
    public function store(StoreEventRequest $request)
    {
        $data = $request->validated();

        $user = $request->user();
        $this->ensureAudienceAllowedForWrite($user, $data['audience']);
        $this->ensureCanManageBranch($user, $data['party_branch_id'] ?? null);
        $data['created_by'] = $user->id;
        $data['party_branch_id'] = $this->branchIdForWrite($user, $data['party_branch_id'] ?? null);

        $attachmentPath = null;

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('events', 'public');
            $data['attachment_path'] = $attachmentPath;
        }

        unset($data['attachment']);

        try {
            $event = DB::transaction(function () use ($data, $user) {
                $event = Event::create($data);

                $this->notifications->notifyAudience($event->audience ?? ['public'], [
                    'category' => 'event',
                    'title' => 'Nouvelle activité',
                    'body' => "{$event->title} - {$event->location}",
                    'action_url' => '/events',
                    'action_label' => 'Voir l’activité',
                    'source_type' => 'event',
                    'source_id' => $event->id,
                ], $user->id, $event->party_branch_id);

                return $event;
            });
        } catch (Throwable $exception) {
            if ($attachmentPath) {
                Storage::disk('public')->delete($attachmentPath);
            }

            throw $exception;
        }

        return (new EventResource($event->load(['creator', 'partyBranch'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Admin: update an event.
     */
    public function update(UpdateEventRequest $request, $id)
    {
        $event = Event::findOrFail($id);
        $this->ensureCanAccessEvent($request, $event);

        $data = $request->validated();

        if (array_key_exists('audience', $data)) {
            $this->ensureAudienceAllowedForWrite($request->user(), $data['audience']);
        }
        if (array_key_exists('party_branch_id', $data)) {
            $this->ensureCanManageBranch($request->user(), $data['party_branch_id']);
        }
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

        return new EventResource($event->load(['creator', 'partyBranch']));
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

        return EventRegistrationResource::collection($registrations);
    }

    public function storeRecap(StoreEventRecapRequest $request, $id)
    {
        $event = Event::findOrFail($id);
        $this->ensureCanAccessEvent($request, $event);

        if ($event->end_time && $event->end_time->isFuture()) {
            return response()->json(['message' => 'Recaps can be added after the event has finished.'], 422);
        }

        $data = $request->validated();

        $photos = [];
        foreach ($request->file('photos', []) as $photo) {
            $photos[] = $photo->store("events/{$event->id}/recaps", 'public');
        }

        $recap = EventRecap::create([
            'event_id' => $event->id,
            'created_by' => $request->user()->id,
            'title' => $data['title'],
            'content' => $data['content'] ?? null,
            'photos' => $photos,
        ]);

        $this->notifications->notifyAudience($event->audience ?? ['public'], [
            'category' => 'event',
            'title' => 'Nouveau récap d’activité',
            'body' => $event->title,
            'action_url' => "/events/{$event->id}",
            'action_label' => 'Voir le récap',
            'source_type' => 'event_recap',
            'source_id' => $recap->id,
        ], $request->user()->id, $event->party_branch_id);

        return (new EventRecapResource($recap->load('creator')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Member: register to an event (must be in audience).
     */
    public function register(Request $request, $id)
    {
        try {
            $result = DB::transaction(function () use ($request, $id) {
                $user  = $request->user()->load('role');
                $event = Event::whereKey($id)->lockForUpdate()->firstOrFail();
                $role  = optional($user->role)->name;

                // Check user's role is in the event audience
                $audience = $event->audience ?? ['public'];
                if (!$this->audienceAllowsRole($audience, $role)) {
                    throw new HttpResponseException(response()->json(['message' => 'You are not allowed to register for this event'], 403));
                }

                $branchIds = $this->branchIdsVisibleTo($user);
                if ($event->party_branch_id && $branchIds !== null && !in_array((int) $event->party_branch_id, $branchIds, true)) {
                    throw new HttpResponseException(response()->json(['message' => 'This event is not open for your branch.'], 403));
                }

                $existingRegistration = EventRegistration::where('event_id', $event->id)
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if ($existingRegistration) {
                    return ['event' => $event, 'user' => $user, 'created' => false];
                }

                // Check capacity while holding the event row lock.
                if ($event->max_attendees && $event->registrations()->count() >= $event->max_attendees) {
                    throw new HttpResponseException(response()->json(['message' => 'Event is full'], 400));
                }

                EventRegistration::create([
                    'event_id' => $event->id,
                    'user_id'  => $user->id,
                ]);

                return ['event' => $event, 'user' => $user, 'created' => true];
            });
        } catch (QueryException $exception) {
            if (in_array($exception->getCode(), ['23000', '23505'], true)) {
                return response()->json(['message' => 'Déjà réservé.']);
            }

            throw $exception;
        }

        if ($result['created']) {
            $event = $result['event'];
            $user = $result['user'];

            $this->notifications->notifyAdmins([
                'category' => 'registration',
                'title' => 'Nouvelle inscription à une activité',
                'body' => "{$user->name} s’est inscrit à {$event->title}.",
                'action_url' => '/admin/events',
                'action_label' => 'Voir les inscriptions',
                'source_type' => 'event_registration',
                'source_id' => $event->id,
            ]);
        }

        return response()->json(['message' => $result['created'] ? 'Réservation confirmée.' : 'Déjà réservé.']);
    }

    private function attachRegistrationState(Event $event, $user): Event
    {
        $event->registrations_count = $event->registrations()->count();
        $event->has_registered = $user
            ? EventRegistration::where('event_id', $event->id)->where('user_id', $user->id)->exists()
            : false;
        $event->can_register = (bool) $user
            && !$event->has_registered
            && (!$event->end_time || $event->end_time->isFuture());

        return $event;
    }

    /**
     * Member: get own registrations.
     */
    public function myRegistrations(Request $request)
    {
        $registrations = EventRegistration::where('user_id', $request->user()->id)
            ->with('event')
            ->get();

        return EventRegistrationResource::collection($registrations);
    }

    private function ensureCanAccessEvent(Request $request, Event $event): void
    {
        $actor = $request->user();
        $branchIds = $this->managedBranchIdsVisibleTo($actor);

        if ($branchIds !== null && !in_array((int) $event->party_branch_id, $branchIds, true)) {
            abort(403, 'You are not allowed to manage this event.');
        }
    }

    private function audienceAllowsRole(array $audience, ?string $role): bool
    {
        if (in_array('public', $audience, true)) {
            return true;
        }

        if (!$role) {
            return false;
        }

        if (in_array($role, $audience, true)) {
            return true;
        }

        return in_array('member', $audience, true)
            && in_array($role, ['local_official', 'regional_official', 'central_admin', 'super_admin'], true);
    }
}
