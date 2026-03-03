<?php

namespace App\Http\Controllers\ParentUser;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\RescheduleRequest;
use App\Models\TutoringSession;

class DashboardController extends Controller
{
    public function index()
    {
        // For now parent sees global monitoring summary; child mapping can be added with parent-child table.
        $completedSessions = TutoringSession::where('status', 'completed')->count();
        $unpaidInvoices = Invoice::where('status', 'unpaid')->count();
        $pendingReschedule = RescheduleRequest::where('status', 'PENDING')->count();

        return view('parent.dashboard', compact('completedSessions', 'unpaidInvoices', 'pendingReschedule'));
    }
}
