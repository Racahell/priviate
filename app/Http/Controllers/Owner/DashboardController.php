<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Models\TutoringSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        // Enforce RBAC
        // Note: In Laravel 11/12, middleware can be defined in routes, but constructor is also fine if registered
        // Or use $this->middleware('permission:view_financial_reports'); if using Spatie middleware
    }

    public function index()
    {
        // Executive Summary Data
        $totalStudents = User::role('siswa')->count();
        $totalTentors = User::role('tentor')->count();
        $activeSessions = TutoringSession::where('status', 'ongoing')->count();
        
        // Financial Snapshot (Real-time from Ledger)
        // Revenue (Account 401) - Credit Balance
        $revenue = DB::table('journal_items')
            ->join('coas', 'journal_items.coa_id', '=', 'coas.id')
            ->where('coas.code', '401') // Service Revenue
            ->sum('credit');

        // Escrow / Deferred Revenue (Account 201) - Credit Balance
        $deferredRevenue = DB::table('journal_items')
            ->join('coas', 'journal_items.coa_id', '=', 'coas.id')
            ->where('coas.code', '201') // Unearned Revenue
            ->sum('credit') - 
            DB::table('journal_items')
            ->join('coas', 'journal_items.coa_id', '=', 'coas.id')
            ->where('coas.code', '201')
            ->sum('debit');

        return view('owner.dashboard', compact(
            'totalStudents', 
            'totalTentors', 
            'activeSessions', 
            'revenue', 
            'deferredRevenue'
        ));
    }

    public function financials(Request $request)
    {
        $perPage = $this->resolvePerPage($request, 20);
        // P&L Report Logic (Simplified)
        $entries = JournalEntry::with('items.coa')
            ->orderBy('transaction_date', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('owner.financials', compact('entries'));
    }

    private function resolvePerPage(Request $request, int $default = 20): int
    {
        $allowed = [10, 25, 50, 100];
        $requested = (int) $request->query('per_page', $default);
        return in_array($requested, $allowed, true) ? $requested : $default;
    }
}
