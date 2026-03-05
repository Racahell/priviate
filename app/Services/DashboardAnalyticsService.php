<?php

namespace App\Services;

use App\Models\Payment;
use Carbon\Carbon;

class DashboardAnalyticsService
{
    public function build(string $period = 'monthly'): array
    {
        $period = in_array($period, ['weekly', 'monthly', 'yearly'], true) ? $period : 'monthly';

        return [
            'hourly' => $this->hourlyToday(),
            'kpi' => $this->kpiSummary(),
            'period' => $this->periodSeries($period),
            'selected_period' => $period,
        ];
    }

    private function hourlyToday(): array
    {
        $start = now()->startOfDay();
        $end = now()->endOfDay();

        $rows = Payment::query()
            ->where('status', 'success')
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw('HOUR(paid_at) as h, SUM(amount) as total')
            ->groupBy('h')
            ->pluck('total', 'h');

        $labels = [];
        $data = [];
        for ($h = 0; $h < 24; $h++) {
            $labels[] = str_pad((string) $h, 2, '0', STR_PAD_LEFT) . ':00';
            $data[] = (float) ($rows[$h] ?? 0);
        }

        return compact('labels', 'data');
    }

    private function kpiSummary(): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $yesterdayStart = now()->subDay()->startOfDay();
        $yesterdayEnd = now()->subDay()->endOfDay();
        $thisMonthStart = now()->startOfMonth();
        $thisMonthEnd = now()->endOfMonth();
        $lastMonthStart = now()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = now()->subMonthNoOverflow()->endOfMonth();

        return [
            'today' => $this->sumAndCount($todayStart, $todayEnd),
            'yesterday' => $this->sumAndCount($yesterdayStart, $yesterdayEnd),
            'this_month' => $this->sumAndCount($thisMonthStart, $thisMonthEnd),
            'last_month' => $this->sumAndCount($lastMonthStart, $lastMonthEnd),
        ];
    }

    private function periodSeries(string $period): array
    {
        return match ($period) {
            'weekly' => $this->weeklySeries(),
            'yearly' => $this->yearlySeries(),
            default => $this->monthlySeries(),
        };
    }

    private function weeklySeries(): array
    {
        $end = Carbon::today();
        $start = $end->copy()->subDays(6)->startOfDay();

        $rows = Payment::query()
            ->where('status', 'success')
            ->whereBetween('paid_at', [$start, $end->copy()->endOfDay()])
            ->selectRaw('DATE(paid_at) as d, SUM(amount) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $labels = [];
        $data = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $start->copy()->addDays($i)->toDateString();
            $labels[] = Carbon::parse($day)->format('d M');
            $data[] = (float) ($rows[$day] ?? 0);
        }

        return compact('labels', 'data');
    }

    private function monthlySeries(): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $days = (int) $end->format('d');

        $rows = Payment::query()
            ->where('status', 'success')
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw('DAY(paid_at) as d, SUM(amount) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $labels = [];
        $data = [];
        for ($d = 1; $d <= $days; $d++) {
            $labels[] = (string) $d;
            $data[] = (float) ($rows[$d] ?? 0);
        }

        return compact('labels', 'data');
    }

    private function yearlySeries(): array
    {
        $start = now()->startOfYear();
        $end = now()->endOfYear();

        $rows = Payment::query()
            ->where('status', 'success')
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw('MONTH(paid_at) as m, SUM(amount) as total')
            ->groupBy('m')
            ->pluck('total', 'm');

        $labels = [];
        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $labels[] = Carbon::create(now()->year, $m, 1)->format('M');
            $data[] = (float) ($rows[$m] ?? 0);
        }

        return compact('labels', 'data');
    }

    private function sumAndCount(Carbon $start, Carbon $end): array
    {
        $query = Payment::query()
            ->where('status', 'success')
            ->whereBetween('paid_at', [$start, $end]);

        return [
            'amount' => (float) (clone $query)->sum('amount'),
            'count' => (int) (clone $query)->count(),
        ];
    }
}

