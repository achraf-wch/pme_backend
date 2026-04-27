<?php
namespace App\Http\Controllers;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\Request;
class EventController extends Controller {
    public function index() { return response()->json(Event::with('creator')->latest()->get()); }
    public function store(Request $request) {
        $data = $request->validate(['title'=>'required','location'=>'required','start_time'=>'required|date','end_time'=>'required|date|after:start_time','max_attendees'=>'nullable|integer']);
        $data['created_by'] = auth()->id();
        $event = Event::create($data);
        return response()->json($event, 201);
    }
    public function update(Request $request, $id) {
        $event = Event::findOrFail($id);
        $event->update($request->only(['title','description','location','start_time','end_time','max_attendees']));
        return response()->json($event);
    }
    public function destroy($id) { Event::findOrFail($id)->delete(); return response()->json(['message'=>'Deleted']); }
    public function registrations($id) {
        $registrations = EventRegistration::where('event_id',$id)->with('user')->get();
        return response()->json($registrations);
    }
    public function register($id) {
    $user = auth()->user();
    $event = Event::findOrFail($id);
    if ($event->max_attendees && $event->registrations()->count() >= $event->max_attendees) {
        return response()->json(['message'=>'Event is full'], 400);
    }
    EventRegistration::firstOrCreate(['event_id'=>$event->id, 'user_id'=>$user->id]);
    return response()->json(['message'=>'Registered']);
}
}