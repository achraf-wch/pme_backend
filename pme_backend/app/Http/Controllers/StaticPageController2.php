<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Donation;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\MembershipRequest;
use App\Models\News;
use App\Models\Poll;
use App\Models\Vote;
use App\Models\NewsletterSubscriber;
use App\Models\Sympathizer;
use App\Models\Volunteer;

class StatsController extends Controller
{
    public function index()
    {
        return response()->json([
            'users' => [
                'total'        => User::count(),
                'members'      => User::where('role', 'member')->count(),
                'sympathizers' => User::where('role', 'sympathizer')->count(),
                'volunteers'   => User::where('role', 'volunteer')->count(),
                'admins'       => User::where('role', 'admin')->count(),
            ],
            'membership_requests' => [
                'pending'  => MembershipRequest::where('status', 'pending')->count(),
                'approved' => MembershipRequest::where('status', 'approved')->count(),
                'rejected' => MembershipRequest::where('status', 'rejected')->count(),
            ],
            'donations' => [
                'total'  => Donation::count(),
                'amount' => Donation::where('status', 'confirmed')->sum('amount'),
            ],
            'events' => [
                'total'         => Event::count(),
                'registrations' => EventRegistration::count(),
            ],
            'news' => [
                'total'     => News::count(),
                'published' => News::where('is_published', true)->count(),
            ],
            'polls' => [
                'total' => Poll::count(),
                'votes' => Vote::count(),
            ],
            'newsletter' => [
                'subscribers' => NewsletterSubscriber::count(),
            ],
            'sympathizers' => [
                'total' => Sympathizer::count(),
            ],
            'volunteers' => [
                'total' => Volunteer::count(),
            ],
        ]);
    }
}