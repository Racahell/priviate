<?php

namespace App\Http\Controllers;

use App\Models\Dispute;
use App\Models\Invoice;
use App\Models\RescheduleRequest;
use App\Models\TutoringSession;
use App\Models\User;
use App\Services\DashboardAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardAnalyticsService $analyticsService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $role = $user?->getRoleNames()->first();
        $period = (string) $request->query('period', 'monthly');

        if ($role === 'orang_tua') {
            return redirect()->route('parent.dashboard');
        }

        $analytics = null;
        if (in_array($role, ['superadmin', 'admin', 'owner'], true)) {
            $analytics = $this->analyticsService->build($period);
        }

        $payload = [
            'role' => $role,
            'analytics' => $analytics,
            'quickActions' => $this->quickActions($role),
            'summary' => $this->summaryByRole($role, $user?->id),
        ];

        return view('dashboard.index', $payload);
    }

    private function quickActions(?string $role): array
    {
        return match ($role) {
            'superadmin' => [
                ['label' => 'Setting Web', 'route' => 'superadmin.settings'],
                ['label' => 'Hak Akses Menu', 'route' => 'superadmin.menu.access'],
                ['label' => 'Import Data', 'route' => 'superadmin.import.center'],
                ['label' => 'Backup', 'route' => 'superadmin.backup.center'],
            ],
            'admin' => [
                ['label' => 'Setting Web', 'route' => 'admin.settings'],
                ['label' => 'Laporan Keuangan', 'route' => 'admin.reports'],
                ['label' => 'Sesi', 'route' => 'admin.sessions'],
                ['label' => 'Kritik', 'route' => 'admin.disputes'],
                ['label' => 'Monitor', 'route' => 'admin.monitor'],
            ],
            'owner' => [
                ['label' => 'Laporan Keuangan', 'route' => 'owner.reports'],
            ],
            'siswa' => [
                ['label' => 'Paket', 'route' => 'student.packages'],
                ['label' => 'Booking', 'route' => 'student.booking'],
                ['label' => 'Invoices', 'route' => 'student.invoices'],
                ['label' => 'Profil', 'route' => 'profile.edit'],
            ],
            'tentor' => [
                ['label' => 'Jadwal', 'route' => 'tutor.schedule'],
                ['label' => 'Wallet', 'route' => 'tutor.wallet'],
            ],
            'orang_tua' => [
                ['label' => 'Dashboard', 'route' => 'parent.dashboard'],
                ['label' => 'Hubungkan Anak', 'route' => 'parent.children'],
                ['label' => 'Jadwal Anak', 'route' => 'parent.schedule'],
                ['label' => 'Reschedule', 'route' => 'parent.reschedule'],
                ['label' => 'Kritik', 'route' => 'parent.disputes'],
            ],
            default => [],
        };
    }

    private function summaryByRole(?string $role, ?int $userId): array
    {
        return match ($role) {
            'siswa' => [
                'today_sessions' => TutoringSession::query()
                    ->with(['tentor:id,name', 'subject:id,name', 'attendanceRecord'])
                    ->where('student_id', $userId)
                    ->whereDate('scheduled_at', today())
                    ->whereIn('status', ['booked', 'ongoing', 'completed'])
                    ->orderBy('scheduled_at')
                    ->get(),
                'weekly_chart' => $this->studentWeeklyChart($userId),
                'upcoming' => TutoringSession::where('student_id', $userId)
                    ->whereIn('status', ['booked', 'confirmed'])
                    ->where('scheduled_at', '>', now())
                    ->orderBy('scheduled_at')
                    ->first(),
                'unpaid_invoices' => Invoice::where('user_id', $userId)->where('status', 'unpaid')->count(),
                'total_sessions' => TutoringSession::where('student_id', $userId)->count(),
                'completed_sessions' => TutoringSession::where('student_id', $userId)->where('status', 'completed')->count(),
                'weekly_sessions' => TutoringSession::where('student_id', $userId)
                    ->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])
                    ->count(),
            ],
            'tentor' => [
                'today_schedule' => TutoringSession::query()
                    ->with(['student:id,name', 'subject:id,name', 'attendanceRecord'])
                    ->where('tentor_id', $userId)
                    ->whereDate('scheduled_at', today())
                    ->whereIn('status', ['booked', 'ongoing', 'completed'])
                    ->orderBy('scheduled_at')
                    ->get(),
                'today_sessions' => TutoringSession::where('tentor_id', $userId)
                    ->whereDate('scheduled_at', today())
                    ->whereIn('status', [
                        TutoringSession::STATUS_BOOKED,
                        TutoringSession::STATUS_CONFIRMED,
                        TutoringSession::STATUS_ONGOING,
                    ])
                    ->count(),
                'pending_disputes' => Dispute::where('status', Dispute::STATUS_OPEN)->count(),
            ],
            'orang_tua' => [
                'children_count' => User::where('parent_id', $userId)
                    ->whereHas('roles', fn ($query) => $query->where('name', 'siswa'))
                    ->count(),
                'completed_sessions' => TutoringSession::whereHas('student', function ($query) use ($userId) {
                    $query->where('parent_id', $userId);
                })->where('status', 'completed')->count(),
                'pending_reschedule' => RescheduleRequest::where('status', RescheduleRequest::STATUS_PENDING)->whereHas('session.student', function ($query) use ($userId) {
                    $query->where('parent_id', $userId);
                })->count(),
            ],
            'admin' => [
                'pending_tentors' => User::role('tentor')->where('is_active', false)->count(),
                'disputed_sessions' => TutoringSession::where('status', 'disputed')
                    ->orWhere('rating', '<', 3)
                    ->count(),
            ],
            'owner' => [
                'students' => User::role('siswa')->count(),
                'tentors' => User::role('tentor')->count(),
            ],
            'superadmin' => [
                'active_users' => User::where('is_active', true)->count(),
                'open_disputes' => Dispute::where('status', Dispute::STATUS_OPEN)->count(),
            ],
            default => [],
        };
    }

    private function studentWeeklyChart(?int $userId): array
    {
        if (!$userId) {
            return [];
        }

        $days = collect(range(0, 6))->map(function (int $offset) use ($userId) {
            $date = Carbon::now()->startOfWeek()->addDays($offset);
            return [
                'day' => $date->format('D'),
                'count' => TutoringSession::where('student_id', $userId)
                    ->whereDate('scheduled_at', $date->toDateString())
                    ->count(),
            ];
        });

        $max = max(1, (int) $days->max('count'));

        return $days->map(fn (array $day) => [
            ...$day,
            'height' => max(8, (int) round(($day['count'] / $max) * 100)),
        ])->all();
    }
}
