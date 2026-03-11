<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\TutoringSession;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 1. Earnings Overview
        $heldBalance = $user->wallet ? $user->wallet->held_balance : 0;
        $releasedBalance = $user->wallet ? $user->wallet->balance : 0;

        // 2. Today's Schedule
        $todaySessions = TutoringSession::where('tentor_id', $user->id)
            ->whereDate('scheduled_at', today())
            ->whereIn('status', ['booked', 'confirmed'])
            ->orderBy('scheduled_at', 'asc')
            ->get();

        // 3. Performance Metrics
        $attendanceRate = 95; // Placeholder or calculate
        $avgRating = $user->average_rating ?? 0;

        return view('tutor.dashboard', compact(
            'heldBalance', 
            'releasedBalance', 
            'todaySessions', 
            'attendanceRate', 
            'avgRating'
        ));
    }
}
