<?php
namespace App\Http\Controllers;

use App\Models\Donation;
use Illuminate\Http\Request;

class DonationController extends Controller
{
    public function index()
    {
        return response()->json(Donation::with('user')->latest()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => 'required|email',
            'amount' => 'required|numeric|min:1',
            'note'   => 'nullable|string',
        ]);

        $data['status'] = 'pending';

        if ($request->user()) {
            $data['user_id'] = $request->user()->id;
        }

        $donation = Donation::create($data);
        return response()->json($donation, 201);
    }

    public function update(Request $request, $id)
    {
        $donation = Donation::findOrFail($id);
        $request->validate([
            'status' => 'required|in:pending,confirmed,failed',
        ]);
        $donation->update(['status' => $request->status]);
        return response()->json($donation);
    }

    public function destroy($id)
    {
        Donation::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function myDonations(Request $request)
    {
        return response()->json(
            Donation::where('user_id', $request->user()->id)->latest()->get()
        );
    }
}