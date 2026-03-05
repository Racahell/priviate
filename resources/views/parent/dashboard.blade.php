@extends('layouts.master')

@section('title', 'Dashboard Orang Tua')

@section('content')
<div class="grid grid-3">
    <div class="card">
        <h3 class="card-title">Anak Terhubung</h3>
        <p class="stat-value">{{ $children->count() }}</p>
    </div>
    <div class="card">
        <h3 class="card-title">Sesi Selesai</h3>
        <p class="stat-value">{{ $completedSessions }}</p>
    </div>
    <div class="card">
        <h3 class="card-title">Invoice Belum Bayar</h3>
        <p class="stat-value">{{ $unpaidInvoices }}</p>
    </div>
    <div class="card">
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
@endsection
