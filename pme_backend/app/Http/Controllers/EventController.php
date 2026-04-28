<?php
namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    public function index()
    {
        return response()->json(Event::with('creator')->latest()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'location'      => 'required|string|max:255',
            'start_time'    => 'required|date',
            'end_time'      => 'required|date|after:start_time',
            'max_attendees' => 'nullable|integer|min:1',
            'attachment'    => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx|max:10240', // 10MB max
        ]);

        $data['created_by'] = auth()->id();

        if ($request->hasFile('attachment')) {
            $data['attachment_path'] = $request->file('attachment')->store('events', 'public');
        }

        unset($data['attachment']);
        $event = Event::create($data);

        return response()->json($event->load('creator'), 201);
    }

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

    public function destroy($id)
    {
        $event = Event::findOrFail($id);

        if ($event->attachment_path) {
            Storage::disk('public')->delete($event->attachment_path);
        }

        $event->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function registrations($id)
    {
        $registrations = EventRegistration::where('event_id', $id)->with('user')->get();
        return response()->json($registrations);
    }

    public function register($id)
    {
        $user = auth()->user();
        $event = Event::findOrFail($id);

        if ($event->max_attendees && $event->registrations()->count() >= $event->max_attendees) {
            return response()->json(['message' => 'Event is full'], 400);
        }

        EventRegistration::firstOrCreate(['event_id' => $event->id, 'user_id' => $user->id]);
        return response()->json(['message' => 'Registered']);
    }

    public function myRegistrations(Request $request)
    {
        $registrations = EventRegistration::where('user_id', $request->user()->id)
            ->with('event')->get();
        return response()->json($registrations);
    }
}