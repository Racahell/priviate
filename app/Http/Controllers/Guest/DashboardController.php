<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\TutoringSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index()
    {
        $totalSessions = 0;
        $totalTentors = 0;
        $avgRating = 0;
        $tutors = collect();
        $liveSessions = collect();
        $liveSessionCount = 0;

        if (Schema::hasTable('tutoring_sessions')) {
            $totalSessions = TutoringSession::where('status', 'completed')->count();
            $avgRating = TutoringSession::avg('rating') ?? 0;
            $liveSessionCount = TutoringSession::where('status', 'ongoing')->count();
            $liveSessions = TutoringSession::query()
                ->with('subject:id,name,level')
                ->where('status', 'ongoing')
                ->orderBy('scheduled_at')
                ->take(3)
                ->get();
        }

        if (
            Schema::hasTable('users')
            && Schema::hasTable('model_has_roles')
            && Schema::hasTable('roles')
            && Role::query()->where('name', 'tentor')->where('guard_name', 'web')->exists()
        ) {
            $totalTentors = User::role('tentor')->where('is_active', true)->count();
            $tutors = User::role('tentor')
                ->where('is_active', true)
                ->inRandomOrder()
                ->take(6)
                ->get();
        }

        return view('guest.welcome', compact('totalSessions', 'totalTentors', 'avgRating', 'tutors', 'liveSessions', 'liveSessionCount'));
    }
}
