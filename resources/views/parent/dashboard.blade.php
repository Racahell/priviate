@extends('layouts.master')

@section('title', 'Dashboard Orang Tua')

@section('content')
<div class="grid grid-3 parent-kpi-grid">
    <div class="card parent-kpi-card">
        <h3 class="card-title">Anak Terhubung</h3>
        <p class="stat-value">{{ $children->count() }}</p>
    </div>
    <div class="card parent-kpi-card">
        <h3 class="card-title">Sesi Selesai</h3>
        <p class="stat-value">{{ $completedSessions }}</p>
    </div>
    <div class="card parent-kpi-card">
        <h3 class="card-title">Reschedule Pending</h3>
        <p class="stat-value">{{ $pendingReschedule }}</p>
    </div>
</div>

<div class="card section">
    <div class="split-header">
        <h3 class="card-title">Status Anak</h3>
        <a href="{{ route('parent.children') }}" class="btn btn-outline btn-sm">Hubungkan Kode Anak</a>
    </div>
    @if($childStatus->isEmpty())
        <p class="card-meta">Belum ada anak yang terhubung. Masukkan kode siswa di menu Hubungkan Anak.</p>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nama Anak</th>
                        <th>Kode</th>
                        <th>Sesi Selesai</th>
                        <th>Invoice Unpaid</th>
                        <th>Sesi Berikutnya</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($childStatus as $item)
                        <tr>
                            <td>{{ $item['child']->name }}</td>
                            <td>{{ $item['child']->code ?? '-' }}</td>
                            <td>{{ $item['completed_sessions'] }}</td>
                            <td>{{ $item['unpaid_invoices'] }}</td>
                            <td>
                                @if($item['upcoming'])
                                    {{ \Carbon\Carbon::parse($item['upcoming']->scheduled_at)->translatedFormat('d M Y, H:i') }}
                                @else
                                    Tidak ada jadwal
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@if($children->isNotEmpty())
<div class="grid grid-3 parent-menu-grid">
    <div class="card section">
        <h3 class="card-title">Menu Jadwal Anak</h3>
        <p class="card-meta">Pilih anak terlebih dahulu, lalu lihat jadwal belajar anak tersebut.</p>
        <a class="btn btn-primary" href="{{ route('parent.schedule') }}">Buka Jadwal Anak</a>
    </div>
    <div class="card section">
        <h3 class="card-title">Menu Reschedule</h3>
        <p class="card-meta">Ajukan pergantian hari/jam belajar anak dan pantau status pengajuannya.</p>
        <a class="btn btn-primary" href="{{ route('parent.reschedule') }}">Buka Menu Reschedule</a>
    </div>
    <div class="card section">
        <h3 class="card-title">Menu Kritik</h3>
        <p class="card-meta">Kirim kritik terkait sesi anak dan lihat progres penanganannya.</p>
        <a class="btn btn-primary" href="{{ route('parent.disputes') }}">Buka Menu Kritik</a>
    </div>
</div>

<div class="card section">
    <h3 class="card-title">Analytics Anak</h3>
    <p class="card-meta">Ringkasan progres belajar, sesi mendatang, dan performa tiap anak.</p>
    <div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    <th>Nama Anak</th>
                    <th>Total Sesi</th>
                    <th>Sesi Selesai</th>
                    <th>Sesi Akan Datang</th>
                    <th>Progress</th>
                    <th>Rata-rata Rating</th>
                </tr>
            </thead>
            <tbody>
                @foreach($childAnalytics as $row)
                    <tr>
                        <td>{{ $row['child']->name }}</td>
                        <td>{{ $row['total_sessions'] }}</td>
                        <td>{{ $row['completed_sessions'] }}</td>
                        <td>{{ $row['upcoming_sessions'] }}</td>
                        <td>{{ $row['completion_rate'] }}%</td>
                        <td>{{ number_format((float) $row['avg_rating'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
