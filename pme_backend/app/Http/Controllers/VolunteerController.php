<?php
namespace App\Http\Controllers;

use App\Models\Volunteer;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class VolunteerController extends Controller
{
    public function __construct(private NotificationService $notifications)
    {
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:volunteers,email',
            'phone'      => 'nullable|string|max:30',
            'city'       => 'nullable|string|max:100',
            'skills'     => 'nullable|string',
            'motivation' => 'nullable|string',
        ]);
        $volunteer = Volunteer::create($data);
        $this->notifications->notifyAdmins([
            'category' => 'request',
            'title' => 'Nouvelle demande bénévole',
            'body' => "{$volunteer->name} souhaite participer comme bénévole.",
            'action_url' => '/admin/volunteers',
            'action_label' => 'Voir les demandes',
            'source_type' => 'volunteer',
            'source_id' => $volunteer->id,
        ]);
        return response()->json(['message' => 'Volunteer request submitted'], 201);
    }

    public function index()
    {
        return response()->json(Volunteer::latest()->get());
    }

    public function destroy($id)
    {
        Volunteer::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
