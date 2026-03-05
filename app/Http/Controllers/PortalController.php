<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Dispute;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\ScheduleSlot;
use App\Models\Subject;
use App\Models\TeacherPayout;
use App\Models\TutoringSession;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function studentBooking(Request $request)
    {
        $user = $request->user();
        $packages = Package::query()->where('is_active', true)->latest('id')->paginate(10);
        $packageIds = $packages->pluck('id')->all();

        $priceMap = DB::table('package_prices')
            ->selectRaw('package_id, MAX(price) as price')
            ->whereIn('package_id', $packageIds)
            ->where('is_active', true)
            ->groupBy('package_id')
            ->pluck('price', 'package_id');

        $quotaMap = DB::table('package_quotas')
            ->selectRaw('package_id, MAX(quota) as quota')
            ->whereIn('package_id', $packageIds)
            ->where('is_active', true)
            ->groupBy('package_id')
            ->pluck('quota', 'package_id');

        $hasPaidInvoice = false;
        $subjects = collect();
        $openSlots = collect();

        if ($user) {
            $hasPaidInvoice = Invoice::query()
                ->where('user_id', $user->id)
                ->where('status', 'paid')
                ->exists();

            $subjects = Subject::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'level']);

            $openSlots = ScheduleSlot::query()
                ->where('status', 'OPEN')
                ->where('start_at', '>', now())
                ->orderBy('start_at')
                ->take(30)
                ->get(['id', 'start_at', 'end_at']);
        }

        return view('portal.student-booking', [
            'packages' => $packages,
            'priceMap' => $priceMap,
            'quotaMap' => $quotaMap,
            'hasPaidInvoice' => $hasPaidInvoice,
            'subjects' => $subjects,
            'openSlots' => $openSlots,
        ]);
    }

    public function studentInvoices(Request $request)
    {
        $invoices = Invoice::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->paginate(12);

        return view('portal.student-invoices', [
            'invoices' => $invoices,
        ]);
    }

    public function tutorSchedule(Request $request)
    {
        $sessions = TutoringSession::query()
            ->with(['student:id,name', 'subject:id,name'])
            ->where('tentor_id', $request->user()->id)
            ->latest('scheduled_at')
            ->paginate(12);

        return view('portal.tutor-schedule', [
            'sessions' => $sessions,
        ]);
    }

    public function tutorWallet(Request $request)
    {
        $wallet = Wallet::query()->where('user_id', $request->user()->id)->first();
        $payoutCount = TeacherPayout::query()
            ->where('teacher_id', $request->user()->id)
            ->count();
        $completedSessionsCount = TutoringSession::query()
            ->where('tentor_id', $request->user()->id)
            ->where('status', 'completed')
            ->count();
        $payouts = TeacherPayout::query()
            ->where('teacher_id', $request->user()->id)
            ->latest('id')
            ->take(10)
            ->get();

        return view('portal.tutor-wallet', [
            'wallet' => $wallet,
            'payoutCount' => $payoutCount,
            'completedSessionsCount' => $completedSessionsCount,
            'payouts' => $payouts,
        ]);
    }

    public function adminKyc()
    {
        $tentors = User::role('tentor')
            ->where('is_active', false)
            ->latest('id')
            ->paginate(12);

        return view('portal.admin-kyc', [
            'tentors' => $tentors,
        ]);
    }

    public function adminDisputes()
    {
        $disputes = Dispute::query()->latest('id')->paginate(12);

        return view('portal.admin-disputes', [
            'disputes' => $disputes,
        ]);
    }

    public function adminMonitor()
    {
        $todaySessions = TutoringSession::query()
            ->whereDate('scheduled_at', today())
            ->latest('id')
            ->paginate(12);

        return view('portal.admin-monitor', [
            'todaySessions' => $todaySessions,
        ]);
    }

    public function adminSessions()
    {
        $slots = ScheduleSlot::query()
            ->latest('start_at')
            ->paginate(20);

        return view('portal.admin-sessions', [
            'slots' => $slots,
        ]);
    }

    public function adminSessionsStore(Request $request)
    {
        $validated = $request->validate([
            'start_at' => 'required|date|after_or_equal:today',
            'end_at' => 'required|date|after:start_at',
        ]);

        ScheduleSlot::query()->create([
            'start_at' => $validated['start_at'],
            'end_at' => $validated['end_at'],
            'status' => 'OPEN',
            'created_by' => auth()->id(),
        ]);

        return back()->with('status', 'Slot sesi berhasil ditambahkan.');
    }

    public function superadminRbac()
    {
        return redirect()->route('superadmin.menu.access');
    }

    public function superadminAudit()
    {
        $logs = AuditLog::query()->latest('id')->paginate(20);

        return view('portal.superadmin-audit', [
            'logs' => $logs,
        ]);
    }
}
