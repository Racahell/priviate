@extends('layouts.master')

@section('title', 'Jadwal Anak')

@section('content')
<div class="card section">
    <h3 class="card-title">Pilih Anak</h3>
    <p class="card-meta">Pilih anak terlebih dahulu untuk melihat jadwal belajarnya.</p>

    @if($children->isEmpty())
        <p class="card-meta">Belum ada anak terhubung. Hubungkan anak dulu.</p>
        <a href="{{ route('parent.children') }}" class="btn btn-outline">Hubungkan Anak</a>
    @else
        <form method="GET" action="{{ route('parent.schedule') }}" class="form-inline section">
            <select name="child_id" class="form-control input-sm" required>
                <option value="">Pilih anak</option>
                @foreach($children as $child)
                    <option value="{{ $child->id }}" {{ (int) ($selectedChild?->id ?? 0) === (int) $child->id ? 'selected' : '' }}>
                        {{ $child->name }}{{ !empty($child->code) ? ' ('.$child->code.')' : '' }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Lihat Jadwal</button>
        </form>
    @endif
</div>

@if(!empty($selectedChild))
<div class="card section">
    <h3 class="card-title">Jadwal {{ $selectedChild->name }}</h3>
    <p class="card-meta">Daftar sesi aktif anak yang dipilih.</p>
    @include('components.pagination-controls', ['paginator' => $scheduleRows, 'showPerPage' => true, 'showPager' => false, 'position' => 'top'])
<div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Mapel</th>
                    <th>Guru</th>
                    <th>Jadwal</th>
                    <th>Mode</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($scheduleRows as $index => $session)
                    <tr>
                        <td>{{ ($scheduleRows->currentPage() - 1) * $scheduleRows->perPage() + $index + 1 }}</td>
                        <td>{{ $session->subject?->name }}{{ !empty($session->subject?->level) ? ' - '.$session->subject->level : '' }}</td>
                        <td>{{ $session->tentor?->name ?? 'Belum ditentukan' }}</td>
                        <td>{{ optional($session->scheduled_at)->translatedFormat('d M Y, H:i') ?? '-' }}</td>
                        <td>{{ strtoupper((string) ($session->delivery_mode ?? 'online')) }}</td>
                        <td><span class="badge badge-info">{{ strtoupper((string) $session->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada jadwal aktif untuk anak ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

    @include('components.pagination-controls', ['paginator' => $scheduleRows, 'showPerPage' => false, 'showPager' => true, 'position' => 'bottom'])
@endsection

