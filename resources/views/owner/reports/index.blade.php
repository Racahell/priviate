@extends('layouts.master')

@section('title', 'Laporan Dashboard')

@section('content')
<div class="panel panel-default">
    <div class="panel-heading">Filter Laporan</div>
    <div class="panel-body">
        <form method="GET" class="form-inline">
            <label>Periode</label>
            <select name="period" class="form-control">
                <option value="weekly" {{ $period === 'weekly' ? 'selected' : '' }}>Mingguan</option>
                <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Bulanan</option>
                <option value="yearly" {{ $period === 'yearly' ? 'selected' : '' }}>Tahunan</option>
            </select>
            <label>Diagram</label>
            <select name="chart" class="form-control">
                <option value="bar" {{ $chartType === 'bar' ? 'selected' : '' }}>Batang</option>
                <option value="line" {{ $chartType === 'line' ? 'selected' : '' }}>Line</option>
                <option value="pie" {{ $chartType === 'pie' ? 'selected' : '' }}>Pie</option>
            </select>
            <label>Dari</label>
            <input type="date" name="from" value="{{ $from }}" class="form-control">
            <label>Sampai</label>
            <input type="date" name="to" value="{{ $to }}" class="form-control">
            <button class="btn btn-primary" type="submit">Terapkan</button>
            <a href="{{ route('owner.reports.export', ['period' => $period]) }}" class="btn btn-success">Export CSV</a>
        </form>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">Input Operational Cost</div>
    <div class="panel-body">
        <form method="POST" action="{{ route('owner.reports.cost.store') }}" class="form-inline">
            @csrf
            <input type="date" name="cost_date" class="form-control" required>
            <input type="text" name="category" class="form-control" placeholder="Kategori" required>
            <input type="number" step="0.01" name="amount" class="form-control" placeholder="Nominal" required>
            <input type="text" name="description" class="form-control" placeholder="Deskripsi">
            <button class="btn btn-warning" type="submit">Simpan Cost</button>
        </form>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">Grafik Revenue</div>
    <div class="panel-body">
        <canvas id="reportChart" width="900" height="360" style="max-width:100%; border:1px solid #ddd;"></canvas>
    </div>
</div>

<script>
let labels = @json($labels);
let data = @json($revenueSeries);
const chartType = @json($chartType);

const canvas = document.getElementById('reportChart');
const ctx = canvas.getContext('2d');
const padding = 40;

function drawChart() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    const width = canvas.width - padding * 2;
    const height = canvas.height - padding * 2;
    const maxValue = Math.max(...data, 1);

    ctx.font = "12px Arial";
    ctx.fillStyle = "#333";
    ctx.strokeStyle = "#999";
    ctx.beginPath();
    ctx.moveTo(padding, padding);
    ctx.lineTo(padding, canvas.height - padding);
    ctx.lineTo(canvas.width - padding, canvas.height - padding);
    ctx.stroke();

    if (chartType === 'pie') {
        const total = data.reduce((a, b) => a + b, 0) || 1;
        let start = 0;
        const colors = ['#2ecc71', '#3498db', '#f39c12', '#e74c3c', '#9b59b6', '#1abc9c'];
        data.forEach((value, idx) => {
            const slice = (value / total) * Math.PI * 2;
            ctx.beginPath();
            ctx.moveTo(canvas.width / 2, canvas.height / 2);
            ctx.fillStyle = colors[idx % colors.length];
            ctx.arc(canvas.width / 2, canvas.height / 2, 120, start, start + slice);
            ctx.closePath();
            ctx.fill();
            start += slice;
        });
    } else if (chartType === 'line') {
        ctx.strokeStyle = '#2c7be5';
        ctx.beginPath();
        data.forEach((value, idx) => {
            const x = padding + (idx * (width / Math.max(data.length - 1, 1)));
            const y = canvas.height - padding - (value / maxValue) * height;
            if (idx === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
            ctx.fillText(labels[idx] || '', x - 10, canvas.height - 10);
        });
        ctx.stroke();
    } else {
        const barWidth = width / Math.max(data.length, 1) * 0.7;
        data.forEach((value, idx) => {
            const x = padding + idx * (width / Math.max(data.length, 1)) + 8;
            const barHeight = (value / maxValue) * height;
            const y = canvas.height - padding - barHeight;
            ctx.fillStyle = '#2ecc71';
            ctx.fillRect(x, y, barWidth, barHeight);
            ctx.fillStyle = '#333';
            ctx.fillText(labels[idx] || '', x, canvas.height - 10);
        });
    }
}
drawChart();

// Optional async refresh from JSON endpoint for frontend apps.
fetch("{{ route('owner.reports.data', ['period' => $period, 'from' => $from, 'to' => $to]) }}")
    .then(resp => resp.json())
    .then(json => {
        labels = json.labels || labels;
        data = json.revenueSeries || data;
        drawChart();
    })
    .catch(() => {});
</script>
@endsection
