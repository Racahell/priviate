@extends('layouts.master')

@section('title', 'Dashboard')

@php
    $showAnalytics = in_array($role, ['superadmin', 'admin', 'owner'], true) && !empty($analytics);
@endphp

@section('content')
@if($showAnalytics)
<div class="analytics-kpi-grid section">
    <div class="analytics-kpi">
        <p class="stat-label">SALES</p>
        <p class="stat-value">Rp {{ number_format($analytics['kpi']['today']['amount'], 0, ',', '.') }}</p>
        <p class="card-meta">+{{ number_format(($analytics['kpi']['today']['amount'] - $analytics['kpi']['yesterday']['amount']) / 1000, 1) }}K vs yesterday</p>
        <div class="mini-meter"><span style="width: 68%"></span></div>
    </div>
    <div class="analytics-kpi">
        <p class="stat-label">PROFIT</p>
        <p class="stat-value">Rp {{ number_format((float) data_get($analytics, 'kpi.this_month_profit.amount', 0), 0, ',', '.') }}</p>
        <p class="card-meta">Profit aktual bulan ini</p>
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
    <div class="card analytics-chart-card analytics-chart-card-wide">
        <div class="split-header">
            <h3 class="card-title">Profit and Loss</h3>
            <select id="chartTypePeriod" class="form-control input-sm">
                <option value="bar">Bar</option>
                <option value="line">Line</option>
                <option value="pie">Pie</option>
            </select>
        </div>
        <div class="chart-shell">
            <div id="periodChart" class="custom-chart" style="height:250px;"></div>
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
                <select id="chartTypeSalesTarget" class="form-control input-sm">
                    <option value="line">Line</option>
                    <option value="bar">Bar</option>
                    <option value="pie">Pie</option>
                </select>
                <a class="btn {{ $analytics['selected_period'] === 'weekly' ? 'btn-primary' : 'btn-outline' }}" href="{{ route('dashboard', ['period' => 'weekly']) }}">Mingguan</a>
                <a class="btn {{ $analytics['selected_period'] === 'monthly' ? 'btn-primary' : 'btn-outline' }}" href="{{ route('dashboard', ['period' => 'monthly']) }}">Bulanan</a>
                <a class="btn {{ $analytics['selected_period'] === 'yearly' ? 'btn-primary' : 'btn-outline' }}" href="{{ route('dashboard', ['period' => 'yearly']) }}">Tahunan</a>
            </div>
        </div>
        <div class="chart-shell">
            <div id="salesTargetChart" class="custom-chart" style="height:300px;"></div>
        </div>
    </div>
    <div class="card analytics-side-card">
        <div class="split-header">
            <h3 class="card-title">Sales by Payment Method</h3>
            <select id="chartTypePaymentMethod" class="form-control input-sm">
                <option value="bar">Bar</option>
                <option value="line">Line</option>
                <option value="pie">Pie</option>
            </select>
        </div>
        <div class="chart-shell">
            <div id="hourlyIncomeChart" class="custom-chart" style="height:300px;"></div>
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
    @case('orang_tua')
        @include('dashboard.partials.orang_tua')
        @break
@endswitch
</div>
@endsection

@push('scripts')
@if($showAnalytics)
<script>
    (function () {
        var periodData = @json($analytics['period']['data']);
        var targetData = periodData.map(function (v) { return Math.round(v * 0.8); });
        var periodLabels = @json($analytics['period']['labels']);
        var methodLabels = @json($analytics['method']['labels']);
        var methodData = @json($analytics['method']['data']);

        var colors = ['#2f5fb8', '#5f84d0', '#8eabdf', '#4a74bf', '#6c94df', '#9ab8ed'];

        function createSvg(tag, attrs) {
            var el = document.createElementNS('http://www.w3.org/2000/svg', tag);
            Object.keys(attrs || {}).forEach(function (key) { el.setAttribute(key, attrs[key]); });
            return el;
        }

        function formatCompact(value) {
            return new Intl.NumberFormat('id-ID', { notation: 'compact', compactDisplay: 'short', maximumFractionDigits: 1 }).format(value || 0);
        }

        function buildLineChart(container, labels, sales, target) {
            if (!container) return;
            var w = Math.max(container.clientWidth || 600, 320);
            var h = container.clientHeight || 300;
            var m = { top: 20, right: 20, bottom: 44, left: 50 };
            var innerW = w - m.left - m.right;
            var innerH = h - m.top - m.bottom;
            var maxVal = Math.max(1, Math.max.apply(Math, sales.concat(target)));
            var yTicks = 5;
            container.innerHTML = '';

            var svg = createSvg('svg', { viewBox: '0 0 ' + w + ' ' + h, width: '100%', height: '100%' });
            for (var i = 0; i <= yTicks; i++) {
                var y = m.top + (innerH * i / yTicks);
                svg.appendChild(createSvg('line', { x1: m.left, y1: y, x2: w - m.right, y2: y, stroke: '#e5edf9' }));
                var value = Math.round(maxVal - ((maxVal / yTicks) * i));
                var t = createSvg('text', { x: m.left - 8, y: y + 4, 'text-anchor': 'end', 'font-size': '11', fill: '#6d7a96' });
                t.textContent = formatCompact(value);
                svg.appendChild(t);
            }

            function pxX(i) {
                if (labels.length <= 1) return m.left + (innerW / 2);
                return m.left + (innerW * i / (labels.length - 1));
            }
            function pxY(v) {
                return m.top + innerH - ((v / maxVal) * innerH);
            }
            function linePath(data) {
                return data.map(function (v, i) { return (i === 0 ? 'M' : 'L') + pxX(i) + ' ' + pxY(v); }).join(' ');
            }

            var area = 'M' + pxX(0) + ' ' + (m.top + innerH) + ' ' + linePath(sales).replace('M', 'L') + ' L' + pxX(labels.length - 1) + ' ' + (m.top + innerH) + ' Z';
            svg.appendChild(createSvg('path', { d: area, fill: 'rgba(47,95,184,0.15)' }));
            svg.appendChild(createSvg('path', { d: linePath(target), fill: 'none', stroke: '#8eabdf', 'stroke-width': '2' }));
            svg.appendChild(createSvg('path', { d: linePath(sales), fill: 'none', stroke: '#2f5fb8', 'stroke-width': '3' }));

            labels.forEach(function (label, i) {
                var x = pxX(i);
                svg.appendChild(createSvg('circle', { cx: x, cy: pxY(sales[i]), r: 3, fill: '#2f5fb8' }));
                if (i % Math.ceil(labels.length / 6) === 0 || i === labels.length - 1) {
                    var tx = createSvg('text', { x: x, y: h - 14, 'text-anchor': 'middle', 'font-size': '11', fill: '#6d7a96' });
                    tx.textContent = label;
                    svg.appendChild(tx);
                }
            });

            container.appendChild(svg);
        }

        function buildGroupedBars(container, labels, a, b, colorA, colorB) {
            if (!container) return;
            var w = Math.max(container.clientWidth || 600, 320);
            var h = container.clientHeight || 250;
            var m = { top: 18, right: 16, bottom: 40, left: 28 };
            var innerW = w - m.left - m.right;
            var innerH = h - m.top - m.bottom;
            var secondary = Array.isArray(b) ? b : [];
            var merged = a.concat(secondary);
            var maxVal = Math.max(1, Math.max.apply(Math, merged));
            container.innerHTML = '';
            var svg = createSvg('svg', { viewBox: '0 0 ' + w + ' ' + h, width: '100%', height: '100%' });

            labels.forEach(function (label, i) {
                var groupW = innerW / labels.length;
                var hasSecond = secondary.length > 0;
                var barW = Math.max(6, Math.min(22, groupW * (hasSecond ? 0.34 : 0.62)));
                var x = m.left + (i * groupW) + (groupW * 0.16);
                var h1 = (a[i] / maxVal) * innerH;
                svg.appendChild(createSvg('rect', { x: x, y: m.top + innerH - h1, width: barW, height: h1, rx: 4, fill: colorA || '#2f5fb8' }));
                if (hasSecond) {
                    var h2 = (secondary[i] / maxVal) * innerH;
                    svg.appendChild(createSvg('rect', { x: x + barW + 4, y: m.top + innerH - h2, width: barW, height: h2, rx: 4, fill: colorB || '#9bb4e6' }));
                }
                if (i % Math.ceil(labels.length / 6) === 0 || i === labels.length - 1) {
                    var tx = createSvg('text', { x: x + barW, y: h - 12, 'text-anchor': 'middle', 'font-size': '11', fill: '#6d7a96' });
                    tx.textContent = label;
                    svg.appendChild(tx);
                }
            });

            container.appendChild(svg);
        }

        function buildDonut(container, labels, values) {
            if (!container) return;
            var w = Math.max(container.clientWidth || 340, 240);
            var h = container.clientHeight || 250;
            var cx = w / 2;
            var cy = h / 2;
            var r = Math.min(w, h) * 0.32;
            var stroke = Math.max(20, r * 0.45);
            var total = values.reduce(function (s, v) { return s + v; }, 0) || 1;
            var c = 2 * Math.PI * r;
            var current = 0;
            container.innerHTML = '';
            var wrap = document.createElement('div');
            wrap.className = 'donut-wrap';
            var svg = createSvg('svg', { viewBox: '0 0 ' + w + ' ' + h, width: '100%', height: '100%' });
            svg.appendChild(createSvg('circle', { cx: cx, cy: cy, r: r, fill: 'none', stroke: '#e9eff9', 'stroke-width': stroke }));

            values.forEach(function (value, i) {
                var dash = (value / total) * c;
                var arc = createSvg('circle', {
                    cx: cx, cy: cy, r: r, fill: 'none',
                    stroke: colors[i % colors.length],
                    'stroke-width': stroke,
                    'stroke-dasharray': dash + ' ' + (c - dash),
                    'stroke-dashoffset': -current,
                    transform: 'rotate(-90 ' + cx + ' ' + cy + ')'
                });
                current += dash;
                svg.appendChild(arc);
            });

            var center = createSvg('text', { x: cx, y: cy + 4, 'text-anchor': 'middle', 'font-size': '14', fill: '#2f3b57', 'font-weight': '700' });
            center.textContent = 'Rp ' + formatCompact(total);
            svg.appendChild(center);
            wrap.appendChild(svg);

            var legend = document.createElement('div');
            legend.className = 'chart-legend';
            labels.forEach(function (label, i) {
                var row = document.createElement('div');
                row.className = 'chart-legend-item';
                row.innerHTML = '<span class="dot" style="background:' + colors[i % colors.length] + ';"></span><span class="name">' + label + '</span><span class="val">Rp ' + formatCompact(values[i]) + '</span>';
                legend.appendChild(row);
            });

            container.appendChild(wrap);
            container.appendChild(legend);
        }

        function buildLineSingle(container, labels, values) {
            var zeros = values.map(function () { return 0; });
            buildLineChart(container, labels, values, zeros);
        }

        function renderChartByType(containerId, type, labels, a, b, labelA, labelB) {
            var container = document.getElementById(containerId);
            if (!container) return;

            if (type === 'pie') {
                if (Array.isArray(b) && b.length > 0) {
                    var sumA = a.reduce(function (s, v) { return s + Number(v || 0); }, 0);
                    var sumB = b.reduce(function (s, v) { return s + Number(v || 0); }, 0);
                    buildDonut(container, [labelA || 'Data A', labelB || 'Data B'], [sumA, sumB]);
                } else {
                    buildDonut(container, labels, a);
                }
                return;
            }

            if (type === 'line') {
                if (Array.isArray(b) && b.length > 0) {
                    buildLineChart(container, labels, a, b);
                } else {
                    buildLineSingle(container, labels, a);
                }
                return;
            }

            if (Array.isArray(b) && b.length > 0) {
                buildGroupedBars(container, labels, a, b);
            } else {
                buildGroupedBars(container, labels, a, null, '#2f5fb8');
            }
        }

        var chartTypePeriod = document.getElementById('chartTypePeriod');
        var chartTypeSalesTarget = document.getElementById('chartTypeSalesTarget');
        var chartTypePaymentMethod = document.getElementById('chartTypePaymentMethod');

        function renderAll() {
            renderChartByType('periodChart', chartTypePeriod ? chartTypePeriod.value : 'bar', periodLabels, periodData, targetData, 'Sales', 'Target');
            renderChartByType('salesTargetChart', chartTypeSalesTarget ? chartTypeSalesTarget.value : 'line', periodLabels, periodData, targetData, 'Sales', 'Target');
            renderChartByType('hourlyIncomeChart', chartTypePaymentMethod ? chartTypePaymentMethod.value : 'bar', methodLabels, methodData, null, 'Payment', null);
        }

        [chartTypePeriod, chartTypeSalesTarget, chartTypePaymentMethod].forEach(function (select) {
            if (!select) return;
            select.addEventListener('change', renderAll);
        });

        renderAll();

        window.addEventListener('resize', function () {
            renderAll();
        });
    })();
</script>
@endif
@endpush
