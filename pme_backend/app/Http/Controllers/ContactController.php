<?php
namespace App\Http\Controllers;
use App\Models\Contact;
use Illuminate\Http\Request;
class ContactController extends Controller {
    public function index() { return response()->json(Contact::latest()->get()); }
    public function store(Request $request) {
        $data = $request->validate(['name'=>'required','email'=>'required|email','message'=>'required']);
        $contact = Contact::create($data);
        return response()->json($contact, 201);
    }
}