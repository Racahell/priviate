@extends('layouts.master')

@section('title', 'Dashboard')

@php
    $showAnalytics = in_array($role, ['superadmin', 'admin', 'owner'], true) && !empty($analytics);
@endphp

@section('content')
@if($showAnalytics)
<div class="analytics-toolbar card">
    <div class="form-inline">
        <select class="form-control input-sm" aria-label="periode">
            <option>Last 6 Month</option>
        </select>
        <select class="form-control input-sm" aria-label="lokasi">
            <option>Select store location</option>
        </select>
    </div>
</div>

<div class="analytics-kpi-grid section">
    <div class="analytics-kpi">
        <p class="stat-label">SALES</p>
        <p class="stat-value">Rp {{ number_format($analytics['kpi']['today']['amount'], 0, ',', '.') }}</p>
        <p class="card-meta">+{{ number_format(($analytics['kpi']['today']['amount'] - $analytics['kpi']['yesterday']['amount']) / 1000, 1) }}K vs yesterday</p>
        <div class="mini-meter"><span style="width: 68%"></span></div>
    </div>
    <div class="analytics-kpi">
        <p class="stat-label">PROFIT</p>
        <p class="stat-value">Rp {{ number_format($analytics['kpi']['this_month']['amount'] * 0.35, 0, ',', '.') }}</p>
        <p class="card-meta">Margin estimasi bulan ini</p>
        <div class="mini-meter"><span style="width: 52%"></span></div>
    </div>
    <div class="analytics-kpi">
        <p class="stat-label">TOTAL SALES COST</p>
        <p class="stat-value">Rp {{ number_format($analytics['kpi']['last_month']['amount'], 0, ',', '.') }}</p>
        <p class="card-meta">{{ $analytics['kpi']['last_month']['count'] }} transaksi bulan lalu</p>
        <div class="mini-meter"><span style="width: 41%"></span></div>
    </div>
</div>

<div class="analytics-main-grid">
    <div class="card analytics-chart-card">
        <h3 class="card-title">Product Sale Mix</h3>
        <div class="chart-shell"><canvas id="productMixChart" height="250"></canvas></div>
    </div>
    <div class="card analytics-chart-card">
        <h3 class="card-title">Profit and Loss</h3>
        <div class="chart-shell">
            <canvas id="periodChart" height="250"></canvas>
        </div>
    </div>
    <div class="card analytics-side-card">
        <h3 class="card-title">Quick Access</h3>
        <div class="stack">
            @forelse($quickActions as $action)
                @if(Route::has($action['route']))
                    <a class="btn btn-outline" href="{{ route($action['route']) }}">{{ $action['label'] }}</a>
                @endif
            @empty
                <p class="card-meta">Tidak ada quick access.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="analytics-bottom-grid section">
    <div class="card analytics-chart-card">
        <div class="split-header">
            <h3 class="card-title">Sales vs Target Over Time</h3>
            <div class="split-actions">
                <a class="btn {{ $analytics['selected_period'] === 'weekly' ? 'btn-primary' : 'btn-outline' }}" href="{{ route('dashboard', ['period' => 'weekly']) }}">Mingguan</a>
                <a class="btn {{ $analytics['selected_period'] === 'monthly' ? 'btn-primary' : 'btn-outline' }}" href="{{ route('dashboard', ['period' => 'monthly']) }}">Bulanan</a>
                <a class="btn {{ $analytics['selected_period'] === 'yearly' ? 'btn-primary' : 'btn-outline' }}" href="{{ route('dashboard', ['period' => 'yearly']) }}">Tahunan</a>
            </div>
        </div>
        <div class="chart-shell">
            <canvas id="salesTargetChart" height="300"></canvas>
        </div>
    </div>
    <div class="card analytics-side-card">
        <h3 class="card-title">Sales by Store</h3>
        <div class="chart-shell">
            <canvas id="hourlyIncomeChart" height="300"></canvas>
        </div>
    </div>
</div>
@endif

<div class="section">
@switch($role)
    @case('superadmin')
        @include('dashboard.partials.superadmin')
        @break
    @case('admin')
        @include('dashboard.partials.admin')
        @break
    @case('owner')
        @include('dashboard.partials.owner')
        @break
    @case('siswa')
        @include('dashboard.partials.siswa')
        @break
    @case('tentor')
        @include('dashboard.partials.tentor')
        @break
    @case('manager')
        @include('dashboard.partials.manager')
        @break
    @case('orang_tua')
        @include('dashboard.partials.orang_tua')
        @break
@endswitch
</div>
@endsection

@push('scripts')
@if($showAnalytics)
<script src="{{ asset('assets/chart-master/Chart.js') }}"></script>
<script>
    (function () {
        var hourlyCtx = document.getElementById('hourlyIncomeChart');
        var periodCtx = document.getElementById('periodChart');
        var mixCtx = document.getElementById('productMixChart');
        var lineCtx = document.getElementById('salesTargetChart');
        if (!hourlyCtx || !periodCtx || !mixCtx || !lineCtx || typeof Chart === 'undefined') return;

        var periodData = @json($analytics['period']['data']);
        var targetData = periodData.map(function (v) { return Math.round(v * 0.8); });

        new Chart(hourlyCtx.getContext('2d')).Bar({
            labels: @json($analytics['hourly']['labels']),
            datasets: [{
                fillColor: 'rgba(47,95,184,0.74)',
                strokeColor: 'rgba(47,95,184,1)',
                data: @json($analytics['hourly']['data'])
            }]
        }, { responsive: true, scaleBeginAtZero: true });

        new Chart(periodCtx.getContext('2d')).Bar({
            labels: @json($analytics['period']['labels']),
            datasets: [
                {
                    fillColor: 'rgba(47,95,184,0.74)',
                    strokeColor: 'rgba(47,95,184,1)',
                    data: periodData
                },
                {
                    fillColor: 'rgba(120,149,211,0.72)',
                    strokeColor: 'rgba(120,149,211,1)',
                    data: targetData
                }
            ]
        }, { responsive: true, scaleBeginAtZero: true });

        new Chart(mixCtx.getContext('2d')).Doughnut([
            { value: {{ (int) $analytics['kpi']['today']['count'] }}, color: '#2f5fb8' },
            { value: {{ (int) $analytics['kpi']['this_month']['count'] }}, color: '#5f84d0' },
            { value: {{ (int) max(1, $analytics['kpi']['last_month']['count']) }}, color: '#95aedf' }
        ], { responsive: true, animationSteps: 50, percentageInnerCutout: 50 });

        new Chart(lineCtx.getContext('2d')).Line({
            labels: @json($analytics['period']['labels']),
            datasets: [
                {
                    fillColor: 'rgba(47,95,184,0.14)',
                    strokeColor: 'rgba(47,95,184,1)',
                    pointColor: 'rgba(47,95,184,1)',
                    pointStrokeColor: '#fff',
                    data: periodData
                },
                {
                    fillColor: 'rgba(120,149,211,0.12)',
                    strokeColor: 'rgba(120,149,211,1)',
                    pointColor: 'rgba(120,149,211,1)',
                    pointStrokeColor: '#fff',
                    data: targetData
                }
            ]
        }, { responsive: true, bezierCurve: true, scaleBeginAtZero: true });
    })();
</script>
@endif
@endpush
