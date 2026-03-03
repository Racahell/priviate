<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. System Health Monitor
        // In real app, check server load. Here we just mock or check DB connection
        $dbStatus = DB::connection()->getPdo() ? 'Connected' : 'Error';
        
        // 2. Audit Trail Live Feed
        $recentActivities = AuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('superadmin.dashboard', compact('dbStatus', 'recentActivities'));
    }
}
