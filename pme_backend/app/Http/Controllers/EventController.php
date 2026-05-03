<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    /**
     * Admin: all events regardless of audience.
     */
    public function index()
    {
        return response()->json(Event::with('creator')->latest()->get());
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
        $role = optional($request->user()?->role)->name;

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
            'audience.*'    => 'string|in:public,visitor,sympathizer,volunteer,member,admin,local_official,regional_official,central_admin,super_admin',
            'attachment'    => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx|max:10240',
        ]);

        $data['created_by'] = auth()->id();

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('events', 'public');
        }

        unset($data['attachment']);

        $event = Event::create($data);

        return response()->json($event->load('creator'), 201);
    }

    /**
     * Admin: update an event.
     */
    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $data = $request->validate([
            'title'         => 'sometimes|required|string|max:255',
            'description'   => 'nullable|string',
            'location'      => 'sometimes|required|string|max:255',
            'start_time'    => 'sometimes|required|date',
            'end_time'      => 'sometimes|required|date|after:start_time',
            'max_attendees' => 'nullable|integer|min:1',
            'audience'      => 'sometimes|required|array|min:1',
            'audience.*'    => 'string|in:public,visitor,sympathizer,volunteer,member,admin,local_official,regional_official,central_admin,super_admin',
            'attachment'    => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx|max:10240',
        ]);

        if ($request->hasFile('attachment')) {
            if ($event->attachment_path) {
                Storage::disk('public')->delete($event->attachment_path);
            }
            $data['attachment_path'] = $request->file('attachment')->store('events', 'public');
        }

        unset($data['attachment']);
        $event->update($data);

        return response()->json($event->load('creator'));
    }

    /**
     * Admin: delete an event.
     */
    public function destroy($id)
    {
        $event = Event::findOrFail($id);

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
        $registrations = EventRegistration::where('event_id', $id)
            ->with('user')
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
}
