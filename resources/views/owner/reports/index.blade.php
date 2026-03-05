@extends('layouts.master')

@section('title', 'Laporan Dashboard')

@section('content')
<div class="grid grid-3">
    <div class="card">
        <h3 class="card-title">Total Income</h3>
        <p class="stat-value">Rp {{ number_format((float) $totalIncome, 0, ',', '.') }}</p>
    </div>
    <div class="card">
        <h3 class="card-title">Total Expense</h3>
        <p class="stat-value">Rp {{ number_format((float) $totalExpense, 0, ',', '.') }}</p>
    </div>
    <div class="card">
        <h3 class="card-title">Net Profit/Loss</h3>
        <p class="stat-value">Rp {{ number_format((float) $totalProfit, 0, ',', '.') }}</p>
    </div>
</div>

<div class="card section">
    <h3 class="card-title">Filter Laporan</h3>
    <form method="GET" class="form-inline section report-filter-row">
        <label>Periode</label>
        <select name="period" class="form-control">
            <option value="weekly" {{ $period === 'weekly' ? 'selected' : '' }}>Mingguan</option>
            <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Bulanan</option>
            <option value="yearly" {{ $period === 'yearly' ? 'selected' : '' }}>Tahunan</option>
        </select>
        <label>Diagram</label>
        <select name="chart" class="form-control">
            <option value="bar" {{ $chartType === 'bar' ? 'selected' : '' }}>Profit vs Loss</option>
            <option value="line" {{ $chartType === 'line' ? 'selected' : '' }}>Income vs Expense</option>
            <option value="pie" {{ $chartType === 'pie' ? 'selected' : '' }}>Komposisi</option>
        </select>
        <label>Dari</label>
        <input type="date" name="from" value="{{ $from }}" class="form-control">
        <label>Sampai</label>
        <input type="date" name="to" value="{{ $to }}" class="form-control">
        <button class="btn btn-primary" type="submit">Terapkan</button>
        <a href="{{ route('owner.reports.export', ['period' => $period]) }}" class="btn btn-success">Export CSV</a>
    </form>
</div>

<div class="card section">
    <h3 class="card-title">Input Operational Cost</h3>
    <form method="POST" action="{{ route('owner.reports.cost.store') }}" class="form-inline section">
        @csrf
        <input type="date" name="cost_date" class="form-control" required>
        <input type="text" name="category" class="form-control" placeholder="Kategori" required>
        <input type="number" step="0.01" name="amount" class="form-control" placeholder="Nominal" required>
        <input type="text" name="description" class="form-control" placeholder="Deskripsi">
        <button class="btn btn-outline" type="submit">Simpan Cost</button>
    </form>
</div>

<div class="card section">
    <h3 class="card-title">Grafik Berdasarkan Data Database</h3>
    <div class="chart-shell">
        <canvas id="ownerReportChart" height="320"></canvas>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/chart-master/Chart.js') }}"></script>
<script>
(function () {
    var canvas = document.getElementById('ownerReportChart');
    if (!canvas || typeof Chart === 'undefined') return;

    var labels = @json($labels);
    var chartType = @json($chartType);
    var income = @json($revenueSeries);
    var expense = @json($expenseSeries);
    var gain = @json($gainSeries);
    var loss = @json($lossSeries);
    var totals = {
        income: Number(@json($totalIncome)),
        expense: Number(@json($totalExpense))
    };

    var chart;
    function renderChart() {
        if (chart) { chart.destroy && chart.destroy(); }

        if (chartType === 'pie') {
            chart = new Chart(canvas.getContext('2d')).Doughnut([
                { value: totals.income, color: '#3f78c8', label: 'Income' },
                { value: totals.expense, color: '#a7b8d6', label: 'Expense' }
            ], { responsive: true, animationSteps: 50, percentageInnerCutout: 56 });
            return;
        }

        if (chartType === 'line') {
            chart = new Chart(canvas.getContext('2d')).Line({
                labels: labels,
                datasets: [
                    {
                        label: 'Income',
                        fillColor: 'rgba(63,120,200,0.12)',
                        strokeColor: 'rgba(63,120,200,1)',
                        pointColor: 'rgba(63,120,200,1)',
                        pointStrokeColor: '#fff',
                        data: income
                    },
                    {
                        label: 'Expense',
                        fillColor: 'rgba(167,184,214,0.12)',
                        strokeColor: 'rgba(167,184,214,1)',
                        pointColor: 'rgba(167,184,214,1)',
                        pointStrokeColor: '#fff',
                        data: expense
                    }
                ]
            }, { responsive: true, bezierCurve: true, scaleBeginAtZero: true });
            return;
        }

        chart = new Chart(canvas.getContext('2d')).Bar({
            labels: labels,
            datasets: [
                {
                    label: 'Gain',
                    fillColor: 'rgba(63,120,200,0.78)',
                    strokeColor: 'rgba(63,120,200,1)',
                    data: gain
                },
                {
                    label: 'Loss',
                    fillColor: 'rgba(167,184,214,0.86)',
                    strokeColor: 'rgba(167,184,214,1)',
                    data: loss
                }
            ]
        }, { responsive: true, scaleBeginAtZero: true });
    }

    renderChart();
})();
</script>
@endpush
