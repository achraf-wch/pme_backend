<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RecordsAuditLogs;
use App\Models\Donation;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DonationController extends Controller
{
    use RecordsAuditLogs;

    public function __construct(private NotificationService $notifications)
    {
    }

    public function index()
    {
        return response()->json(Donation::with('user')->latest()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'donor_name' => 'nullable|string|max:255',
            'donor_email' => 'nullable|email',
            'amount' => 'required|numeric|min:1',
            'rib' => 'required|string|max:64',
            'note' => 'nullable|string|max:1000',
            'frequency' => 'nullable|in:once,monthly',
        ]);

        $data['name'] = $data['name'] ?? $data['donor_name'] ?? null;
        $data['email'] = $data['email'] ?? $data['donor_email'] ?? null;
        unset($data['donor_name'], $data['donor_email']);

        if (!$data['name'] || !$data['email']) {
            return response()->json(['message' => 'Name and email are required.'], 422);
        }

        $data['status'] = 'pending';
        $data['frequency'] = $data['frequency'] ?? 'once';
        $data['payment_reference'] = 'DON-' . now()->format('Ymd') . '-' . Str::upper(Str::random(8));

        if ($request->user()) {
            $data['user_id'] = $request->user()->id;
        }

        $donation = Donation::create($data);
        $this->audit($request, 'donation.created', $donation, [
            'amount' => $donation->amount,
            'status' => $donation->status,
        ]);

        $this->notifications->notifyAdmins([
            'category' => 'donation',
            'title' => 'Nouvelle contribution',
            'body' => "{$donation->name} a proposé une contribution de {$donation->amount}.",
            'action_url' => '/admin/donations',
            'action_label' => 'Voir les dons',
            'source_type' => 'donation',
            'source_id' => $donation->id,
        ], $request->user()?->id);

        return response()->json($donation, 201);
    }

    public function update(Request $request, $id)
    {
        $donation = Donation::findOrFail($id);
        $request->validate([
            'status' => 'required|in:pending,completed,confirmed,failed',
        ]);
        $status = $request->status === 'confirmed' ? 'completed' : $request->status;
        $donation->update(['status' => $status]);
        $this->audit($request, 'donation.status_updated', $donation, ['status' => $status]);
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
