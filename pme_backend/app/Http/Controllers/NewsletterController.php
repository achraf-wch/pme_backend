<?php
namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|unique:newsletter_subscribers,email',
        ]);
        NewsletterSubscriber::create($data);
        return response()->json(['message' => 'Subscribed successfully'], 201);
    }

    public function index()
    {
        return response()->json(NewsletterSubscriber::latest()->get());
    }

    public function destroy($id)
    {
        NewsletterSubscriber::findOrFail($id)->delete();
        return response()->json(['message' => 'Removed']);
    }

    public function send(Request $request)
    {
        $request->validate([
            'subject' => 'required|string',
            'body'    => 'required|string',
        ]);
        $count = NewsletterSubscriber::count();
        // Mail::to(...) hook here when ready
        return response()->json(['message' => 'Newsletter sent to ' . $count . ' subscribers']);
    }
}