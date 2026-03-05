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
            'expenseSeries' => $dataset['expenseSeries'],
            'profitSeries' => $dataset['profitSeries'],
            'gainSeries' => $dataset['gainSeries'],
            'lossSeries' => $dataset['lossSeries'],
            'totalIncome' => $dataset['totalIncome'],
            'totalExpense' => $dataset['totalExpense'],
            'totalProfit' => $dataset['totalProfit'],
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
        $period = in_array($period, ['weekly', 'monthly', 'yearly'], true) ? $period : 'monthly';

        $invoiceQuery = Invoice::query()->where('status', 'paid');
        if ($from) {
            $invoiceQuery->whereDate('issue_date', '>=', $from);
        }
        if ($to) {
            $invoiceQuery->whereDate('issue_date', '<=', $to);
        }

        $revenueData = $invoiceQuery
            ->selectRaw($this->bucketExpr('issue_date', $period) . " as bucket, sum(total_amount) as total")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        $payoutQuery = TeacherPayout::query()
            ->where('status', 'PAID')
            ->whereNotNull('paid_at');
        if ($from) {
            $payoutQuery->whereDate('paid_at', '>=', $from);
        }
        if ($to) {
            $payoutQuery->whereDate('paid_at', '<=', $to);
        }

        $payoutData = $payoutQuery
            ->selectRaw($this->bucketExpr('paid_at', $period) . " as bucket, sum(net_amount) as total")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        $costQuery = DB::table('operational_cost_entries');
        if ($from) {
            $costQuery->whereDate('cost_date', '>=', $from);
        }
        if ($to) {
            $costQuery->whereDate('cost_date', '<=', $to);
        }

        $costData = $costQuery
            ->selectRaw($this->bucketExpr('cost_date', $period) . " as bucket, sum(amount) as total")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        $revenueMap = $revenueData->pluck('total', 'bucket')->map(fn ($v) => (float) $v);
        $payoutMap = $payoutData->pluck('total', 'bucket')->map(fn ($v) => (float) $v);
        $costMap = $costData->pluck('total', 'bucket')->map(fn ($v) => (float) $v);

        $labels = collect()
            ->merge($revenueMap->keys())
            ->merge($payoutMap->keys())
            ->merge($costMap->keys())
            ->unique()
            ->sort()
            ->values();

        $revenueSeries = $labels->map(fn ($label) => (float) ($revenueMap[$label] ?? 0))->values();
        $payoutSeries = $labels->map(fn ($label) => (float) ($payoutMap[$label] ?? 0))->values();
        $costSeries = $labels->map(fn ($label) => (float) ($costMap[$label] ?? 0))->values();

        $expenseSeries = $labels->map(function ($label) use ($payoutMap, $costMap) {
            return (float) (($payoutMap[$label] ?? 0) + ($costMap[$label] ?? 0));
        })->values();

        $profitSeries = $labels->map(function ($label) use ($revenueMap, $payoutMap, $costMap) {
            return (float) (($revenueMap[$label] ?? 0) - (($payoutMap[$label] ?? 0) + ($costMap[$label] ?? 0)));
        })->values();

        $gainSeries = $profitSeries->map(fn ($v) => (float) max(0, $v))->values();
        $lossSeries = $profitSeries->map(fn ($v) => (float) max(0, -$v))->values();

        return [
            'labels' => $labels,
            'revenueSeries' => $revenueSeries,
            'payoutSeries' => $payoutSeries,
            'costSeries' => $costSeries,
            'expenseSeries' => $expenseSeries,
            'profitSeries' => $profitSeries,
            'gainSeries' => $gainSeries,
            'lossSeries' => $lossSeries,
            'totalIncome' => (float) $revenueSeries->sum(),
            'totalExpense' => (float) $expenseSeries->sum(),
            'totalProfit' => (float) $profitSeries->sum(),
        ];
    }

    private function bucketExpr(string $column, string $period): string
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            return match ($period) {
                'weekly' => "DATE_FORMAT({$column}, '%x-W%v')",
                'yearly' => "DATE_FORMAT({$column}, '%Y')",
                default => "DATE_FORMAT({$column}, '%Y-%m')",
            };
        }

        if ($driver === 'pgsql') {
            return match ($period) {
                'weekly' => "to_char({$column}, 'IYYY-\"W\"IW')",
                'yearly' => "to_char({$column}, 'YYYY')",
                default => "to_char({$column}, 'YYYY-MM')",
            };
        }

        if ($driver === 'sqlsrv') {
            return match ($period) {
                'weekly' => "CONCAT(DATEPART(YEAR, {$column}), '-W', RIGHT('0' + CAST(DATEPART(WEEK, {$column}) AS VARCHAR(2)), 2))",
                'yearly' => "CAST(DATEPART(YEAR, {$column}) AS VARCHAR(4))",
                default => "CONVERT(varchar(7), {$column}, 126)",
            };
        }

        return match ($period) {
            'weekly' => "strftime('%Y-W%W', {$column})",
            'yearly' => "strftime('%Y', {$column})",
            default => "strftime('%Y-%m', {$column})",
        };
    }
}
