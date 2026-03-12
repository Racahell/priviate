<?php

namespace App\Http\Controllers\ParentUser;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\Invoice;
use App\Models\RescheduleRequest;
use App\Models\ScheduleSlot;
use App\Models\TutoringSession;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $parentId = (int) $request->user()->id;
        $children = $this->childrenForParent($parentId);
        $childIds = $children->pluck('id')->all();

        $completedSessions = empty($childIds)
            ? 0
            : TutoringSession::whereIn('student_id', $childIds)->where('status', 'completed')->count();
        $unpaidInvoices = empty($childIds)
            ? 0
            : Invoice::whereIn('user_id', $childIds)->where('status', 'unpaid')->count();
        $pendingReschedule = empty($childIds)
            ? 0
            : RescheduleRequest::where('status', RescheduleRequest::STATUS_PENDING)->whereHas('session', function ($query) use ($childIds) {
                $query->whereIn('student_id', $childIds);
            })->count();

        $childStatus = $children->map(function (User $child) {
            $upcoming = TutoringSession::where('student_id', $child->id)
                ->whereIn('status', ['booked', 'confirmed', 'ongoing'])
                ->where('scheduled_at', '>=', now())
                ->orderBy('scheduled_at')
                ->first();

            return [
                'child' => $child,
                'completed_sessions' => TutoringSession::where('student_id', $child->id)->where('status', 'completed')->count(),
                'unpaid_invoices' => Invoice::where('user_id', $child->id)->where('status', 'unpaid')->count(),
                'upcoming' => $upcoming,
            ];
        });

        $scheduleRows = collect();
        $childAnalytics = collect();

        if (!empty($childIds)) {
            $scheduleRows = TutoringSession::query()
                ->with(['student:id,name', 'subject:id,name,level', 'tentor:id,name'])
                ->whereIn('student_id', $childIds)
                ->whereIn('status', ['booked', 'confirmed', 'ongoing'])
                ->where('scheduled_at', '>=', now()->subHours(3))
                ->orderBy('scheduled_at')
                ->take(30)
                ->get();

            $childAnalytics = $children->map(function (User $child) {
                $total = TutoringSession::where('student_id', $child->id)->count();
                $completed = TutoringSession::where('student_id', $child->id)->where('status', 'completed')->count();
                $upcoming = TutoringSession::where('student_id', $child->id)
                    ->whereIn('status', ['booked', 'confirmed', 'ongoing'])
                    ->where('scheduled_at', '>=', now())
                    ->count();
                $avgRating = (float) (TutoringSession::where('student_id', $child->id)->whereNotNull('rating')->avg('rating') ?? 0);

                return [
                    'child' => $child,
                    'total_sessions' => $total,
                    'completed_sessions' => $completed,
                    'upcoming_sessions' => $upcoming,
                    'completion_rate' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
                    'avg_rating' => $avgRating,
                ];
            });
        }

        return view('parent.dashboard', compact(
            'completedSessions',
            'unpaidInvoices',
            'pendingReschedule',
            'children',
            'childStatus',
            'scheduleRows',
            'childAnalytics'
        ));
    }

    public function reschedule(Request $request)
    {
        $parentId = (int) $request->user()->id;
        $children = $this->childrenForParent($parentId);
        $childIds = $children->pluck('id')->all();

        $sessionOptions = $this->sessionOptionsForParent($childIds);
        $openSlots = ScheduleSlot::query()
            ->orderBy('id')
            ->get(['id', 'name', 'start_at', 'end_at']);
        $rescheduleHistory = $this->rescheduleHistoryForParent($childIds);

        return view('parent.reschedule', compact('children', 'sessionOptions', 'openSlots', 'rescheduleHistory'));
    }

    public function schedule(Request $request)
    {
        $parentId = (int) $request->user()->id;
        $children = $this->childrenForParent($parentId);
        $selectedChildId = (int) $request->query('child_id', 0);

        $selectedChild = $children->firstWhere('id', $selectedChildId);
        $scheduleRows = collect();

        if ($selectedChild) {
            $perPage = $this->resolvePerPage($request, 20);
            $scheduleRows = TutoringSession::query()
                ->with(['student:id,name', 'subject:id,name,level', 'tentor:id,name'])
                ->where('student_id', $selectedChild->id)
                ->whereIn('status', ['booked', 'confirmed', 'ongoing'])
                ->where('scheduled_at', '>=', now()->subHours(3))
                ->orderBy('scheduled_at')
                ->paginate($perPage)
                ->appends(['child_id' => $selectedChild->id, 'per_page' => $perPage]);
        }

        return view('parent.schedule', compact('children', 'selectedChild', 'scheduleRows'));
    }

    public function disputes(Request $request)
    {
        $parentId = (int) $request->user()->id;
        $children = $this->childrenForParent($parentId);
        $childIds = $children->pluck('id')->all();

        $sessionOptions = $this->sessionOptionsForParent($childIds);
        $disputeHistory = $this->disputeHistoryForParent($childIds);

        return view('parent.disputes', compact('children', 'sessionOptions', 'disputeHistory'));
    }

    public function children(Request $request)
    {
        $children = $this->childrenForParent((int) $request->user()->id);

        return view('parent.children', [
            'children' => $children,
        ]);
    }

    public function linkChild(Request $request)
    {
        $request->validate([
            'student_code' => 'required|string|max:24',
        ]);

        $parent = $request->user();
        $code = strtoupper(trim((string) $request->input('student_code')));

        $student = User::whereRaw('UPPER(code) = ?', [$code])->first();
        if (!$student || !$student->hasRole('siswa')) {
            return back()->withErrors(['student_code' => 'Kode siswa tidak ditemukan.']);
        }

        if ((int) $student->id === (int) $parent->id) {
            return back()->withErrors(['student_code' => 'Akun ini tidak bisa dihubungkan ke dirinya sendiri.']);
        }

        if (!empty($student->parent_id) && (int) $student->parent_id !== (int) $parent->id) {
            return back()->withErrors(['student_code' => 'Siswa ini sudah terhubung ke orang tua lain.']);
        }

        $student->forceFill(['parent_id' => $parent->id])->save();

        return back()->with('status', "Siswa {$student->name} berhasil dihubungkan.");
    }

    private function childrenForParent(int $parentId)
    {
        return User::where('parent_id', $parentId)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'siswa');
            })
            ->orderBy('name')
            ->get();
    }

    private function sessionOptionsForParent(array $childIds)
    {
        if (empty($childIds)) {
            return collect();
        }

        return TutoringSession::query()
            ->with(['student:id,name', 'subject:id,name,level', 'invoice:id,invoice_number'])
            ->whereIn('student_id', $childIds)
            ->whereIn('status', ['booked', 'confirmed', 'ongoing', 'completed'])
            ->latest('scheduled_at')
            ->take(80)
            ->get();
    }

    private function rescheduleHistoryForParent(array $childIds)
    {
        if (empty($childIds)) {
            return collect();
        }

        return RescheduleRequest::query()
            ->with(['session.student:id,name', 'session.subject:id,name'])
            ->whereHas('session', function ($query) use ($childIds) {
                $query->whereIn('student_id', $childIds);
            })
            ->latest('id')
            ->take(20)
            ->get();
    }

    private function disputeHistoryForParent(array $childIds)
    {
        if (empty($childIds)) {
            return collect();
        }

        return Dispute::query()
            ->with(['session.student:id,name', 'session.subject:id,name'])
            ->whereHas('session', function ($query) use ($childIds) {
                $query->whereIn('student_id', $childIds);
            })
            ->latest('id')
            ->take(20)
            ->get();
    }

    private function resolvePerPage(Request $request, int $default = 20): int
    {
        $allowed = [10, 25, 50, 100];
        $requested = (int) $request->query('per_page', $default);
        return in_array($requested, $allowed, true) ? $requested : $default;
    }
}
