<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\TutoringSession;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 1. Upcoming Lesson (Nearest Future Session)
        $upcomingSession = TutoringSession::where('student_id', $user->id)
            ->whereIn('status', ['booked', 'confirmed'])
            ->where('scheduled_at', '>', now())
            ->orderBy('scheduled_at', 'asc')
            ->first();

        // 2. Financial Summary
        $unpaidInvoicesCount = Invoice::where('user_id', $user->id)
            ->where('status', 'unpaid')
            ->count();
            
        // Assuming Wallet Balance if implemented for students (deposit)
        $walletBalance = $user->wallet ? $user->wallet->balance : 0;

        // 3. Feed Penilaian (Completed but not rated)
        $sessionsToRate = TutoringSession::where('student_id', $user->id)
            ->where('status', 'completed') // or auto_completed
            ->whereNull('rating')
            ->get();

        return view('student.dashboard', compact(
            'upcomingSession', 
            'unpaidInvoicesCount', 
            'walletBalance', 
            'sessionsToRate'
        ));
    }
}
