<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\RescheduleRequest;

class DashboardController extends Controller
{
    public function index()
    {
        $openDisputes = Dispute::where('status', 'DISPUTE_OPEN')->count();
        $pendingReschedule = RescheduleRequest::where('status', 'PENDING')->count();

        return view('manager.dashboard', compact('openDisputes', 'pendingReschedule'));
    }
}
