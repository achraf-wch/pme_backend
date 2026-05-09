<?php
namespace App\Http\Controllers;
use App\Models\Contact;
use App\Services\NotificationService;
use Illuminate\Http\Request;
class ContactController extends Controller {
    public function __construct(private NotificationService $notifications) {}

    public function index() { return response()->json(Contact::latest()->get()); }
    public function store(Request $request) {
        $data = $request->validate(['name'=>'required','email'=>'required|email','message'=>'required']);
        $contact = Contact::create($data);
        $this->notifications->notifyAdmins([
            'category' => 'message',
            'title' => 'Nouveau message',
            'body' => "{$contact->name} a envoyé un message.",
            'action_url' => '/admin/contacts',
            'action_label' => 'Lire les messages',
            'source_type' => 'contact',
            'source_id' => $contact->id,
        ]);
        return response()->json($contact, 201);
    }
}
