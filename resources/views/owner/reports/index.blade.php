@extends('layouts.master')

@section('title', 'Laporan Keuangan')

@section('content')
@php($reportRoutePrefix = $reportRoutePrefix ?? 'owner')
@php($displayNetProfit = (float) max(0, (float) $totalProfit))
@php($displayLoss = (float) max(0, -1 * (float) $totalProfit))
<div class="grid grid-4">
    <div class="card">
        <h3 class="card-title">Pendapatan</h3>
        <p class="stat-value">Rp {{ number_format((float) $totalIncome, 0, ',', '.') }}</p>
    </div>
    <div class="card">
        <h3 class="card-title">Beban</h3>
        <p class="stat-value">Rp {{ number_format((float) $totalExpense, 0, ',', '.') }}</p>
    </div>
    <div class="card">
        <h3 class="card-title">Laba Bersih</h3>
        <p class="stat-value">Rp {{ number_format($displayNetProfit, 0, ',', '.') }}</p>
    </div>
    <div class="card">
        <h3 class="card-title">Rugi</h3>
        <p class="stat-value">Rp {{ number_format($displayLoss, 0, ',', '.') }}</p>
    </div>
</div>

<div class="card section">
    <h3 class="card-title">Filter Laporan Keuangan</h3>
    <form method="GET" class="section">
        <div class="report-filter-grid">
            <div class="form-group">
                <label>Periode</label>
                <select name="period" class="form-control">
                    <option value="weekly" {{ $period === 'weekly' ? 'selected' : '' }}>Mingguan</option>
                    <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Bulanan</option>
                    <option value="yearly" {{ $period === 'yearly' ? 'selected' : '' }}>Tahunan</option>
                </select>
            </div>
            <div class="form-group">
                <label>Diagram</label>
                <select name="chart" class="form-control">
                    <option value="bar" {{ $chartType === 'bar' ? 'selected' : '' }}>Profit vs Loss</option>
                    <option value="line" {{ $chartType === 'line' ? 'selected' : '' }}>Income vs Expense</option>
                    <option value="pie" {{ $chartType === 'pie' ? 'selected' : '' }}>Komposisi</option>
                </select>
            </div>
            <div class="form-group">
                <label>Dari</label>
                <input type="date" name="from" value="{{ $from }}" class="form-control">
            </div>
            <div class="form-group">
                <label>Sampai</label>
                <input type="date" name="to" value="{{ $to }}" class="form-control">
            </div>
        </div>
        <div class="split-actions report-filter-actions">
            <button class="btn btn-primary" type="submit">Terapkan</button>
            <a href="{{ route($reportRoutePrefix . '.reports.export', ['period' => $period, 'from' => $from, 'to' => $to, 'format' => 'print']) }}" class="btn btn-outline" target="_blank">Print</a>
            <a href="{{ route($reportRoutePrefix . '.reports.export', ['period' => $period, 'from' => $from, 'to' => $to, 'format' => 'excel']) }}" class="btn btn-success">Export Excel</a>
            <a href="{{ route($reportRoutePrefix . '.reports.export', ['period' => $period, 'from' => $from, 'to' => $to, 'format' => 'pdf']) }}" class="btn btn-default" target="_blank">Export PDF</a>
        </div>
    </form>
</div>

<div class="card section">
    <h3 class="card-title">Laporan Laba Rugi</h3>
    <p class="card-meta">Periode {{ strtoupper($period) }} | Generated {{ $generatedAt->format('d M Y H:i') }}</p>
    <div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    <th>Pos</th>
                    <th style="text-align:right;">Nilai</th>
                </tr>
            </thead>
            <tbody>
                @foreach($incomeStatement as $line)
                    <tr>
                        <td>{{ $line['label'] }}</td>
                        <td style="text-align:right;">Rp {{ number_format((float) $line['amount'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card section">
    <h3 class="card-title">Laporan Arus Kas</h3>
    <div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    <th>Pos</th>
                    <th style="text-align:right;">Nilai</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cashFlowStatement as $line)
                    <tr>
                        <td>{{ $line['label'] }}</td>
                        <td style="text-align:right;">Rp {{ number_format((float) $line['amount'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card section">
    <h3 class="card-title">Rincian Beban Operasional</h3>
    <div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kategori</th>
                    <th style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenseBreakdown as $idx => $cost)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>{{ $cost['category'] }}</td>
                        <td style="text-align:right;">Rp {{ number_format((float) $cost['total'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Belum ada data beban operasional.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(($canInputOperationalCost ?? false) === true)
    <div class="card section">
        <h3 class="card-title">Input Beban Operasional</h3>
        <form method="POST" action="{{ route($reportRoutePrefix . '.reports.cost.store') }}" class="form-inline section">
            @csrf
            <input type="date" name="cost_date" class="form-control" required>
            <input type="text" name="category" class="form-control" placeholder="Kategori" required>
            <input type="number" step="0.01" name="amount" class="form-control" placeholder="Nominal" required>
            <input type="text" name="description" class="form-control" placeholder="Deskripsi">
            <button class="btn btn-outline" type="submit">Simpan Cost</button>
        </form>
    </div>
@endif

@endsection
