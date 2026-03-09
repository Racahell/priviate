<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Dispute;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\PackagePrice;
use App\Models\ScheduleSlot;
use App\Models\Subject;
use App\Models\TeacherPayout;
use App\Models\TutoringSession;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function studentPackages(Request $request)
    {
        $perPage = $this->resolvePerPage($request, 10);
        $packages = Package::query()->where('is_active', true)->latest('id')->paginate($perPage)->withQueryString();
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

        return view('portal.student-packages', [
            'packages' => $packages,
            'priceMap' => $priceMap,
            'quotaMap' => $quotaMap,
        ]);
    }

    public function studentBooking(Request $request)
    {
        $user = $request->user();
        $perPage = $this->resolvePerPage($request, 12);
        $invoices = Invoice::query()
            ->with('items')
            ->where('user_id', $user->id)
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $invoiceIds = $invoices->getCollection()->pluck('id')->all();
        $bookedInvoiceIds = TutoringSession::query()
            ->where('student_id', $user->id)
            ->whereIn('invoice_id', $invoiceIds)
            ->pluck('invoice_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $bookedInvoiceMap = array_fill_keys($bookedInvoiceIds, true);

        $invoices->setCollection(
            $invoices->getCollection()->map(function ($invoice) use ($bookedInvoiceMap) {
                $meta = $this->resolvePackageMetaForInvoice($invoice);
                $isPaid = strtolower((string) $invoice->status) === 'paid';
                $alreadyBooked = !empty($bookedInvoiceMap[(int) $invoice->id]);

                return (object) [
                    'invoice_id' => (int) $invoice->id,
                    'invoice_number' => (string) ($invoice->invoice_number ?: ('INV-' . $invoice->id)),
                    'package_label' => (string) $meta['name'],
                    'weekly_quota' => (int) $meta['quota'],
                    'booking_weeks' => (int) $meta['weeks'],
                    'is_trial' => (bool) $meta['is_trial'],
                    'payment_status' => strtoupper((string) $invoice->status),
                    'booking_status' => !$isPaid ? 'Belum Bayar' : ($alreadyBooked ? 'Booked' : 'Booking Dulu'),
                    'can_book' => $isPaid && !$alreadyBooked,
                ];
            })
        );

        $subjects = collect();
        $openSlots = collect();
        $subjects = Subject::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'level']);

        $openSlots = ScheduleSlot::query()
            ->where('status', 'OPEN')
            ->orderBy('start_at')
            ->take(30)
            ->get(['id', 'start_at', 'end_at']);

        $bookedSessions = TutoringSession::query()
            ->with(['subject:id,name,level', 'tentor:id,name'])
            ->where('student_id', $user->id)
            ->whereIn('status', ['booked', 'ongoing', 'completed'])
            ->latest('scheduled_at')
            ->paginate($perPage)
            ->withQueryString();

        $bookedByInvoice = TutoringSession::query()
            ->with(['subject:id,name,level', 'tentor:id,name'])
            ->where('student_id', $user->id)
            ->whereIn('invoice_id', $invoiceIds)
            ->whereIn('status', ['booked', 'ongoing', 'completed'])
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy('invoice_id')
            ->map(function ($rows) {
                return $rows->map(function ($session) {
                    return [
                        'subject' => trim((string) (($session->subject?->name ?? '-') . (!empty($session->subject?->level) ? ' - ' . $session->subject->level : ''))),
                        'tentor' => (string) ($session->tentor?->name ?? 'Tentor belum ditentukan'),
                        'schedule' => optional($session->scheduled_at)->format('d M Y H:i') ?? '-',
                        'mode' => strtoupper((string) ($session->delivery_mode ?? 'online')),
                        'status' => strtoupper((string) $session->status),
                    ];
                })->values();
            });

        return view('portal.student-booking', [
            'bookingInvoices' => $invoices,
            'subjects' => $subjects,
            'openSlots' => $openSlots,
            'bookedSessions' => $bookedSessions,
            'bookedByInvoice' => $bookedByInvoice,
        ]);
    }

    private function resolvePackageMetaForInvoice(Invoice $invoice): array
    {
        $invoice->loadMissing('items');

        $packageId = null;
        $packageName = null;
        $notes = (string) ($invoice->notes ?? '');
        if (preg_match('/package\s*#\s*(\d+)/i', $notes, $m)) {
            $packageId = (int) $m[1];
        }

        if (!$packageId) {
            foreach ($invoice->items as $item) {
                $desc = (string) ($item->description ?? '');
                if (preg_match('/Paket\s*#\s*(\d+)\s*:/i', $desc, $m)) {
                    $packageId = (int) $m[1];
                    $packageName = trim((string) preg_replace('/^Paket\s*#\s*\d+\s*:\s*/i', '', (string) explode('|', $desc)[0]));
                    break;
                }
                if (preg_match('/Paket:\s*([^|]+)/i', $desc, $m)) {
                    $pkgName = trim((string) $m[1]);
                    $packageName = $pkgName;
                    $packageId = (int) (Package::query()->where('name', $pkgName)->value('id') ?? 0);
                    if ($packageId > 0) {
                        break;
                    }
                }
            }
        }

        if (!$packageId) {
            $price = (float) $invoice->total_amount;
            $packageId = (int) (PackagePrice::query()
                ->where('is_active', true)
                ->where('price', $price)
                ->orderByDesc('id')
                ->value('package_id') ?? 0);
        }

        if (!$packageName && $packageId) {
            $packageName = (string) (Package::query()->where('id', $packageId)->value('name') ?? '');
        }

        $package = $packageId ? Package::query()->find($packageId) : null;
        $isTrial = (bool) ($package?->trial_enabled ?? false);

        $quota = (int) (DB::table('package_quotas')
            ->where('package_id', $packageId)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->value('quota') ?? 1);

        return [
            'id' => (int) $packageId,
            'name' => $packageName ?: ($packageId ? ("Paket #{$packageId}") : 'Paket'),
            'quota' => $isTrial ? 1 : max(1, $quota),
            'weeks' => $isTrial ? 1 : 4,
            'is_trial' => $isTrial,
        ];
    }

    public function studentInvoices(Request $request)
    {
        $perPage = $this->resolvePerPage($request, 12);
        $invoices = Invoice::query()
            ->with('payments')
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('portal.student-invoices', [
            'invoices' => $invoices,
        ]);
    }

    public function adminInvoices(Request $request)
    {
        $isSuperadmin = $request->user()?->hasRole('superadmin');
        $perPage = $this->resolvePerPage($request, 20);
        $tab = (string) $request->query('tab', 'active');
        if (!$isSuperadmin) {
            $tab = 'active';
        }

        $query = Invoice::query()
            ->with(['user:id,name,email', 'payments'])
            ->latest('id');
        if ($tab === 'deleted' && $isSuperadmin) {
            $query->onlyTrashed();
        }

        $invoices = $query->paginate($perPage)->withQueryString();

        return view('portal.admin-invoices', [
            'invoices' => $invoices,
            'tab' => $tab,
            'isSuperadmin' => $isSuperadmin,
        ]);
    }

    public function adminInvoicesSoftDelete(Request $request, int $id)
    {
        $invoice = Invoice::query()->findOrFail($id);
        $invoice->delete();

        return back()->with('status', 'Invoice berhasil dihapus (soft delete).');
    }

    public function adminInvoicesBulkDelete(Request $request)
    {
        $ids = collect($request->input('ids', []))
            ->filter(fn ($v) => is_numeric($v))
            ->map(fn ($v) => (int) $v)
            ->values();

        if ($ids->isEmpty()) {
            return back()->withErrors(['ids' => 'Pilih minimal satu invoice.']);
        }

        Invoice::query()->whereIn('id', $ids)->get()->each->delete();

        return back()->with('status', 'Invoice terpilih berhasil dihapus.');
    }

    public function superadminInvoiceRestore(Request $request, int $id)
    {
        abort_unless($request->user()?->hasRole('superadmin'), 403);
        $invoice = Invoice::query()->onlyTrashed()->findOrFail($id);
        $invoice->restore();

        return back()->with('status', 'Invoice berhasil direstore.');
    }

    public function superadminInvoiceForceDelete(Request $request, int $id)
    {
        abort_unless($request->user()?->hasRole('superadmin'), 403);
        $request->validate(['reason' => 'required|string|max:500']);
        $invoice = Invoice::query()->withTrashed()->findOrFail($id);
        $invoice->forceDelete();

        return back()->with('status', 'Invoice berhasil dihapus permanen.');
    }

    public function tutorSchedule(Request $request)
    {
        $user = $request->user();
        $perPage = $this->resolvePerPage($request, 12);
        $isAdminViewer = $user?->hasAnyRole(['admin', 'superadmin']);
        if ($isAdminViewer) {
            $sessionGroups = TutoringSession::query()
                ->with(['student:id,name', 'tentor:id,name', 'subject:id,name', 'invoice:id,invoice_number,notes'])
                ->latest('scheduled_at')
                ->get()
                ->groupBy(function (TutoringSession $session) {
                    return implode(':', [
                        (int) $session->student_id,
                        (int) $session->tentor_id,
                        (int) ($session->invoice_id ?? 0),
                    ]);
                })
                ->map(function ($group) {
                    $first = $group->first();
                    $invoice = $first?->invoice;
                    $packageMeta = $invoice ? $this->resolvePackageMetaForInvoice($invoice) : ['name' => 'Paket belum diketahui'];
                    $rows = $group
                        ->sortBy('scheduled_at')
                        ->values()
                        ->map(function (TutoringSession $session) {
                            return [
                                'subject' => (string) ($session->subject?->name ?: $session->subject_id ?: '-'),
                                'schedule' => optional($session->scheduled_at)->format('d M Y H:i') ?: '-',
                                'status' => strtoupper((string) $session->status),
                            ];
                        });

                    return (object) [
                        'student_name' => (string) ($first?->student?->name ?: $first?->student_id ?: '-'),
                        'tentor_name' => (string) ($first?->tentor?->name ?: $first?->tentor_id ?: '-'),
                        'package_label' => (string) ($packageMeta['name'] ?? 'Paket'),
                        'session_count' => $rows->count(),
                        'detail_rows' => $rows->all(),
                    ];
                })
                ->values();

            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $sessions = new LengthAwarePaginator(
                $sessionGroups->forPage($currentPage, $perPage)->values(),
                $sessionGroups->count(),
                $perPage,
                $currentPage,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
        } else {
            $studentColumns = ['id', 'name', 'address', 'city', 'province', 'postal_code', 'latitude', 'longitude'];
            if (Schema::hasColumn('users', 'location_notes')) {
                $studentColumns[] = 'location_notes';
            }

            $sessions = TutoringSession::query()
                ->with([
                    'student:' . implode(',', $studentColumns),
                    'tentor:id,name',
                    'subject:id,name',
                ])
                ->where('tentor_id', $user->id)
                ->latest('scheduled_at')
                ->paginate($perPage)
                ->withQueryString();
        }

        return view('portal.tutor-schedule', [
            'sessions' => $sessions,
            'isAdminViewer' => $isAdminViewer,
        ]);
    }

    public function tutorWallet(Request $request)
    {
        $wallet = Wallet::query()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['balance' => 0, 'held_balance' => 0, 'is_active' => true]
        );
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
        $withdrawals = Withdrawal::query()
            ->where('wallet_id', $wallet->id)
            ->latest('id')
            ->take(10)
            ->get();

        return view('portal.tutor-wallet', [
            'wallet' => $wallet,
            'payoutCount' => $payoutCount,
            'completedSessionsCount' => $completedSessionsCount,
            'payouts' => $payouts,
            'withdrawals' => $withdrawals,
        ]);
    }

    public function tutorRequestWithdrawal(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->hasRole('tentor'), 403);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:10000',
            'bank_name' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_holder' => 'required|string|max:120',
        ]);

        $wallet = Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'held_balance' => 0, 'is_active' => true]
        );
        $amount = (float) $validated['amount'];

        if ((float) $wallet->balance < $amount) {
            return back()->withErrors(['amount' => 'Saldo tidak mencukupi untuk payout.'])->withInput();
        }

        DB::transaction(function () use ($wallet, $validated, $amount) {
            $before = (float) $wallet->balance;
            $wallet->balance = $before - $amount;
            $wallet->save();

            $withdrawal = Withdrawal::query()->create([
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'bank_name' => trim((string) $validated['bank_name']),
                'account_number' => trim((string) $validated['account_number']),
                'account_holder' => trim((string) $validated['account_holder']),
                'status' => 'requested',
            ]);

            WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'type' => 'withdrawal',
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => (float) $wallet->balance,
                'status' => 'pending',
                'description' => 'Request payout ke rekening bank lain',
                'reference_type' => Withdrawal::class,
                'reference_id' => $withdrawal->id,
            ]);
        });

        return back()->with('status', 'Permintaan payout berhasil dibuat dan menunggu proses admin.');
    }

    public function adminKyc(Request $request)
    {
        $perPage = $this->resolvePerPage($request, 12);
        $tentors = User::role('tentor')
            ->where('is_active', false)
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('portal.admin-kyc', [
            'tentors' => $tentors,
        ]);
    }

    public function adminDisputes(Request $request)
    {
        $perPage = $this->resolvePerPage($request, 12);
        $disputes = Dispute::query()->latest('id')->paginate($perPage)->withQueryString();

        return view('portal.admin-disputes', [
            'disputes' => $disputes,
        ]);
    }

    public function adminMonitor(Request $request)
    {
        $perPage = $this->resolvePerPage($request, 12);
        $todaySessions = TutoringSession::query()
            ->with(['student:id,name', 'tentor:id,name', 'subject:id,name'])
            ->whereDate('scheduled_at', today())
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('portal.admin-monitor', [
            'todaySessions' => $todaySessions,
        ]);
    }

    public function adminSessions(Request $request)
    {
        $isSuperadmin = $request->user()?->hasRole('superadmin');
        $perPage = $this->resolvePerPage($request, 20);
        $tab = (string) $request->query('tab', 'active');
        if (!$isSuperadmin) {
            $tab = 'active';
        }

        $detailId = $request->query('detail');
        $slotsQuery = ScheduleSlot::query()->latest('start_at');
        if ($tab === 'deleted' && $isSuperadmin) {
            $slotsQuery->onlyTrashed();
        }
        $slots = $slotsQuery->paginate($perPage)->withQueryString();
        $detail = null;
        if (!empty($detailId)) {
            $detailQuery = ScheduleSlot::query();
            if ($tab === 'deleted' && $isSuperadmin) {
                $detailQuery->onlyTrashed();
            }
            $detail = $detailQuery->find($detailId);
        }

        return view('portal.admin-sessions', [
            'slots' => $slots,
            'detail' => $detail,
            'tab' => $tab,
            'isSuperadmin' => $isSuperadmin,
        ]);
    }

    public function adminSessionsStore(Request $request)
    {
        $validated = $request->validate([
            'start_at' => 'required|date_format:H:i',
            'end_at' => 'required|date_format:H:i',
        ]);

        $baseDate = now()->startOfDay();
        $startAt = Carbon::parse($baseDate->format('Y-m-d') . ' ' . $validated['start_at']);
        $endAt = Carbon::parse($baseDate->format('Y-m-d') . ' ' . $validated['end_at']);
        if ($endAt->lessThanOrEqualTo($startAt)) {
            $endAt->addDay();
        }

        ScheduleSlot::query()->create([
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => 'OPEN',
            'created_by' => auth()->id(),
        ]);

        return back()->with('status', 'Slot sesi berhasil ditambahkan.');
    }

    public function adminSessionsUpdate(Request $request, int $id)
    {
        $validated = $request->validate([
            'start_at' => 'required|date_format:H:i',
            'end_at' => 'required|date_format:H:i',
            'status' => 'required|in:OPEN,LOCKED,BOOKED,CLOSED',
        ]);

        $slot = ScheduleSlot::query()->withTrashed()->findOrFail($id);
        $slotDate = ($slot->start_at ? Carbon::parse($slot->start_at) : now())->startOfDay();
        $startAt = Carbon::parse($slotDate->format('Y-m-d') . ' ' . $validated['start_at']);
        $endAt = Carbon::parse($slotDate->format('Y-m-d') . ' ' . $validated['end_at']);
        if ($endAt->lessThanOrEqualTo($startAt)) {
            $endAt->addDay();
        }

        $slot->update([
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => $validated['status'],
        ]);

        return redirect()->route('admin.sessions')->with('status', 'Slot sesi berhasil diperbarui.');
    }

    public function adminSessionsDelete(int $id)
    {
        $slot = ScheduleSlot::query()->findOrFail($id);
        $slot->delete();

        return redirect()->route('admin.sessions')->with('status', 'Slot sesi berhasil dihapus.');
    }

    public function adminSessionsBulkDelete(Request $request)
    {
        $ids = collect($request->input('ids', []))
            ->filter(fn ($v) => is_numeric($v))
            ->map(fn ($v) => (int) $v)
            ->values();

        if ($ids->isEmpty()) {
            return redirect()->route('admin.sessions')->withErrors(['ids' => 'Pilih minimal satu sesi.']);
        }

        ScheduleSlot::query()->whereIn('id', $ids)->get()->each->delete();

        return redirect()->route('admin.sessions')->with('status', 'Sesi terpilih berhasil dihapus.');
    }

    public function adminSessionsRestore(Request $request, int $id)
    {
        abort_unless($request->user()?->hasRole('superadmin'), 403);
        $slot = ScheduleSlot::query()->onlyTrashed()->findOrFail($id);
        $slot->restore();

        return back()->with('status', 'Slot sesi berhasil direstore.');
    }

    public function adminSessionsForceDelete(Request $request, int $id)
    {
        abort_unless($request->user()?->hasRole('superadmin'), 403);
        $request->validate(['reason' => 'required|string|max:500']);
        $slot = ScheduleSlot::query()->withTrashed()->findOrFail($id);
        $slot->forceDelete();

        return back()->with('status', 'Slot sesi berhasil dihapus permanen.');
    }

    public function superadminRbac()
    {
        return redirect()->route('superadmin.menu.access');
    }

    public function superadminAudit(Request $request)
    {
        $perPage = $this->resolvePerPage($request, 20);
        $logs = AuditLog::query()->latest('id')->paginate($perPage)->withQueryString();

        return view('portal.superadmin-audit', [
            'logs' => $logs,
        ]);
    }

    public function activityLogs(Request $request)
    {
        abort_unless($request->user()?->hasAnyRole(['admin', 'superadmin']), 403);

        $q = trim((string) $request->query('q', ''));
        $action = trim((string) $request->query('action', ''));
        $role = trim((string) $request->query('role', ''));

        $auditQuery = AuditLog::query()->with('user:id,name,email');
        if ($q !== '') {
            $auditQuery->where(function ($inner) use ($q) {
                $like = '%' . $q . '%';
                $inner->where('event', 'like', $like)
                    ->orWhere('action', 'like', $like)
                    ->orWhere('ip_address', 'like', $like)
                    ->orWhere('url', 'like', $like)
                    ->orWhere('user_agent', 'like', $like)
                    ->orWhere('device_fingerprint', 'like', $like);
            });
        }
        if ($action !== '') {
            $auditQuery->where('action', $action);
        }
        if ($role !== '') {
            $auditQuery->where('role', $role);
        }

        $perPage = $this->resolvePerPage($request, 25);
        $auditLogs = $auditQuery->latest('created_at')->paginate($perPage, ['*'], 'audit_page')->withQueryString();

        $actionOptions = AuditLog::query()->select('action')->whereNotNull('action')->distinct()->orderBy('action')->pluck('action');
        $roleOptions = AuditLog::query()->select('role')->whereNotNull('role')->distinct()->orderBy('role')->pluck('role');

        return view('portal.activity-logs', [
            'auditLogs' => $auditLogs,
            'actionOptions' => $actionOptions,
            'roleOptions' => $roleOptions,
            'q' => $q,
            'action' => $action,
            'role' => $role,
        ]);
    }

    private function resolvePerPage(Request $request, int $default = 15): int
    {
        $allowed = [10, 25, 50, 100];
        $requested = (int) $request->query('per_page', $default);
        return in_array($requested, $allowed, true) ? $requested : $default;
    }
}
