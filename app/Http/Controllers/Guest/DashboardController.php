<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\TutoringSession;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Stats Widget
        $totalSessions = TutoringSession::where('status', 'completed')->count();
        $totalTentors = User::role('tentor')->where('is_active', true)->count();
        $avgRating = TutoringSession::avg('rating') ?? 0;

        // Tutor Discovery (Lite)
        $tutors = User::role('tentor')
            ->where('is_active', true)
            ->inRandomOrder()
            ->take(6)
            ->get();

        return view('guest.welcome', compact('totalSessions', 'totalTentors', 'avgRating', 'tutors'));
    }
}
