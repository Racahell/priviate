@extends('layouts.master')

@section('title', 'Kritik Center')

@section('content')
<div class="card">
    <h3 class="card-title">Daftar Kritik</h3>
    <p class="card-meta">Update status dan selesaikan kritik operasional.</p>
    @include('components.pagination-controls', ['paginator' => $disputes, 'showPerPage' => true, 'showPager' => false, 'position' => 'top'])
<div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Sesi</th>
                    <th>Role Sumber</th>
                    <th>Alasan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($disputes as $index => $d)
                    <tr>
                        <td>{{ ($disputes->currentPage() - 1) * $disputes->perPage() + $index + 1 }}</td>
                        <td>{{ $d->tutoring_session_id }}</td>
                        <td>{{ strtoupper($d->source_role ?? '-') }}</td>
                        <td>{{ $d->reason }}</td>
                        <td><span class="badge badge-warning">{{ strtoupper($d->status) }}</span></td>
                        <td>
                            <div class="action-stack">
                                <form method="POST" action="{{ route('ops.dispute.update', $d->id) }}" class="material-inline">
                                    @csrf
                                    @method('PUT')
                                    <select name="status" class="form-control" required>
                                        <option value="IN_REVIEW_L1">IN_REVIEW_L1</option>
                                        <option value="IN_REVIEW_ADMIN">IN_REVIEW_ADMIN</option>
                                        <option value="RESOLVED">RESOLVED</option>
                                    </select>
                                    <input type="text" name="notes" class="form-control" placeholder="Catatan">
                                    <button class="btn btn-outline" type="submit">Update</button>
                                </form>
                                <form method="POST" action="{{ route('ops.dispute.resolve', $d->id) }}" class="material-inline">
                                    @csrf
                                    <input type="text" name="notes" class="form-control" placeholder="Catatan penyelesaian">
                                    <button class="btn btn-success" type="submit">Resolve</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada kritik.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

    @include('components.pagination-controls', ['paginator' => $disputes, 'showPerPage' => false, 'showPager' => true, 'position' => 'bottom'])
@endsection
