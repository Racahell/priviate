<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\OperationalCostEntry;
use App\Models\TeacherPayout;
use App\Services\AuditService;
use App\Services\DiscordAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly DiscordAlertService $discordAlertService
    ) {
    }

    public function index(Request $request)
    {
        $period = $request->query('period', 'monthly'); // weekly, monthly, yearly
        $chartType = $request->query('chart', 'bar'); // bar, line, pie
        $from = $request->query('from');
        $to = $request->query('to');
        $dataset = $this->buildDataset($period, $from, $to);

        return view('owner.reports.index', [
            'period' => $period,
            'chartType' => $chartType,
            'from' => $from,
            'to' => $to,
            'labels' => $dataset['labels'],
            'revenueSeries' => $dataset['revenueSeries'],
            'payoutSeries' => $dataset['payoutSeries'],
            'costSeries' => $dataset['costSeries'],
        ]);
    }

    public function data(Request $request)
    {
        $period = $request->query('period', 'monthly');
        $from = $request->query('from');
        $to = $request->query('to');

        return response()->json($this->buildDataset($period, $from, $to));
    }

    public function storeOperationalCost(Request $request)
    {
        $validated = $request->validate([
            'cost_date' => 'required|date',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $entry = OperationalCostEntry::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        $this->auditService->log('OPERATIONAL_COST_UPDATED', $entry);

        if ((float) $entry->amount >= 10000000) {
            $this->discordAlertService->send('Operational Cost Abnormal', [
                'amount' => $entry->amount,
                'category' => $entry->category,
                'actor_id' => auth()->id(),
            ], 'warning');
        }

        return back()->with('success', 'Operational cost tersimpan.');
    }

    public function export(Request $request)
    {
        $period = $request->query('period', 'monthly');
        $filename = "report_{$period}_" . now()->format('Ymd_His') . '.csv';

        $rows = Invoice::query()
            ->select('invoice_number', 'issue_date', 'total_amount', 'status')
            ->orderBy('issue_date', 'desc')
            ->limit(5000)
            ->get();

        $this->auditService->log('REPORT_EXPORTED', null, [], ['period' => $period, 'rows' => $rows->count()]);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Invoice Number', 'Issue Date', 'Total Amount', 'Status']);
            foreach ($rows as $row) {
                fputcsv($handle, [$row->invoice_number, $row->issue_date, $row->total_amount, $row->status]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function buildDataset(string $period, ?string $from, ?string $to): array
    {
        $groupExpr = match ($period) {
            'weekly' => "strftime('%Y-W%W', issue_date)",
            'yearly' => "strftime('%Y', issue_date)",
            default => "strftime('%Y-%m', issue_date)",
        };

        $invoiceQuery = Invoice::query()->where('status', 'paid');
        if ($from) {
            $invoiceQuery->whereDate('issue_date', '>=', $from);
        }
        if ($to) {
            $invoiceQuery->whereDate('issue_date', '<=', $to);
        }

        $revenueData = $invoiceQuery
            ->selectRaw("{$groupExpr} as bucket, sum(total_amount) as total")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        $payoutData = TeacherPayout::query()
            ->where('status', 'PAID')
            ->selectRaw("strftime('%Y-%m', paid_at) as bucket, sum(net_amount) as total")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        $costData = DB::table('operational_cost_entries')
            ->selectRaw("strftime('%Y-%m', cost_date) as bucket, sum(amount) as total")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return [
            'labels' => $revenueData->pluck('bucket')->values(),
            'revenueSeries' => $revenueData->pluck('total')->map(fn ($v) => (float) $v)->values(),
            'payoutSeries' => $payoutData->pluck('total')->map(fn ($v) => (float) $v)->values(),
            'costSeries' => $costData->pluck('total')->map(fn ($v) => (float) $v)->values(),
        ];
    }
}
