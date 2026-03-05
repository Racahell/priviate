<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FraudLog;
use App\Models\TutoringSession;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Verification Queue (KYC)
        $pendingTentors = User::role('tentor')
            ->where('is_active', false) // Assuming inactive means pending approval
            ->get();

        // 2. Dispute Center (Low Rating)
        $disputedSessions = TutoringSession::where('status', 'disputed')
            ->orWhere('rating', '<', 3)
            ->get();

        // 3. Fraud Monitor (Recent Alerts)
        $fraudAlerts = FraudLog::orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // 4. Active Session Map (Live Sessions)
        $liveSessions = TutoringSession::where('status', 'ongoing')->get();

        return view('admin.dashboard', compact(
            'pendingTentors', 
            'disputedSessions', 
            'fraudAlerts', 
            'liveSessions'
        ));
    }
}
