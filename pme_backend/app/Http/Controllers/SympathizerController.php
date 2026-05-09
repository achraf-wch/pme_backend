<?php
namespace App\Http\Controllers;

use App\Models\Sympathizer;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class SympathizerController extends Controller
{
    public function __construct(private NotificationService $notifications)
    {
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|unique:sympathizers,email',
            'phone'   => 'nullable|string|max:30',
            'city'    => 'nullable|string|max:100',
            'message' => 'nullable|string',
        ]);
        $sympathizer = Sympathizer::create($data);
        $this->notifications->notifyAdmins([
            'category' => 'request',
            'title' => 'Nouvelle demande sympathisant',
            'body' => "{$sympathizer->name} souhaite rejoindre les sympathisants.",
            'action_url' => '/admin/sympathizers',
            'action_label' => 'Voir les demandes',
            'source_type' => 'sympathizer',
            'source_id' => $sympathizer->id,
        ]);
        return response()->json(['message' => 'Request submitted'], 201);
    }

    public function index()
    {
        return response()->json(Sympathizer::latest()->get());
    }

    public function destroy($id)
    {
        Sympathizer::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
