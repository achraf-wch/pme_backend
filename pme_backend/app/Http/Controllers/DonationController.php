<?php
namespace App\Http\Controllers;
use App\Models\Donation;
use Illuminate\Http\Request;
class DonationController extends Controller {
    public function index() { return response()->json(Donation::with('user')->get()); }
    public function update(Request $request, $id) {
        $donation = Donation::findOrFail($id);
        $request->validate(['status' => 'required|in:pending,completed,failed']);
        $donation->update(['status' => $request->status]);
        return response()->json(['message' => 'Donation updated']);
    }
    public function store(Request $request) {
        $request->validate(['donor_name'=>'required','donor_email'=>'required|email','amount'=>'required|numeric']);
        $donation = Donation::create($request->only(['donor_name','donor_email','amount','user_id']) + ['status'=>'pending']);
        return response()->json($donation, 201);
    }
    public function myDonations(Request $request) {
    $donations = Donation::where('user_id', $request->user()->id)->latest()->get();
    return response()->json($donations);
}
}