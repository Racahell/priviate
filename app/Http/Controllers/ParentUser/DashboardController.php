<?php

namespace App\Http\Controllers\ParentUser;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\RescheduleRequest;
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
            : RescheduleRequest::whereIn('status', ['PENDING', 'pending'])->whereHas('session', function ($query) use ($childIds) {
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

        return view('parent.dashboard', compact('completedSessions', 'unpaidInvoices', 'pendingReschedule', 'children', 'childStatus'));
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
}
