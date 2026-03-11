@extends('layouts.master')

@section('title', 'Master Sesi')

@section('content')
<div class="card">
    @php($detail = $detail ?? null)
    <div class="split-header">
        <div>
            <h3 class="card-title">Master Slot Booking</h3>
            <p class="card-meta">Slot adalah template jam yang bisa dipilih saat booking. Booking aktual tercatat di tutoring session.</p>
        </div>
        @if(($isSuperadmin ?? false) === true)
            <div class="split-actions">
                <a href="{{ route('admin.sessions', ['tab' => 'active']) }}" class="btn {{ ($tab ?? 'active') === 'active' ? 'btn-primary' : 'btn-outline' }}">Active</a>
                <a href="{{ route('admin.sessions', ['tab' => 'deleted']) }}" class="btn {{ ($tab ?? 'active') === 'deleted' ? 'btn-primary' : 'btn-outline' }}">Deleted</a>
            </div>
        @endif
    </div>

    @if(($tab ?? 'active') === 'active')
    <form method="POST" action="{{ route('admin.sessions.store') }}" class="form-inline section">
        @csrf
        <div class="form-group">
            <label>Nama Sesi</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Contoh: Sesi Pagi A" required>
        </div>
        <div class="form-group">
            <label>Mulai</label>
            <input type="time" name="start_at" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Selesai</label>
            <input type="time" name="end_at" class="form-control" required>
        </div>
        <button class="btn btn-primary" type="submit">Tambah Sesi</button>
    </form>

    <form method="POST" action="{{ route('admin.sessions.bulkDelete') }}" id="session-bulk-delete-form" class="form-inline section">
        @csrf
        <button class="btn btn-warning btn-sm" type="submit" onclick="return confirm('Hapus semua sesi yang dipilih?');">Delete Selected</button>
    </form>
    @endif
    @include('components.pagination-controls', ['paginator' => $slots, 'showPerPage' => true, 'showPager' => false, 'position' => 'top'])
<div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    @if(($tab ?? 'active') === 'active')
                        <th><input type="checkbox" id="session-check-all"></th>
                    @endif
                    <th>No</th>
                    <th>Sesi</th>
                    <th>Jam</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($slots as $index => $slot)
                    <tr>
                        @if(($tab ?? 'active') === 'active')
                            <td><input type="checkbox" class="session-row-check" value="{{ $slot->id }}"></td>
                        @endif
                        @php($sessionNumber = ($slots->currentPage() - 1) * $slots->perPage() + $index + 1)
                        <td>{{ $sessionNumber }}</td>
                        <td>{{ $slot->name ?: ('Sesi ' . $sessionNumber) }}</td>
                        <td>{{ optional($slot->start_at)->format('H:i') }} - {{ optional($slot->end_at)->format('H:i') }}</td>
                        <td>
                            @if(($tab ?? 'active') === 'active')
                                <div class="split-actions">
                                    <a href="{{ route('admin.sessions', ['tab' => 'active', 'detail' => $slot->id]) }}" class="btn btn-outline btn-xs">Detail</a>
                                </div>
                            @elseif(($isSuperadmin ?? false) === true)
                                <div class="action-stack">
                                    <form method="POST" action="{{ route('admin.sessions.restore', $slot->id) }}">
                                        @csrf
                                        <button class="btn btn-success btn-xs" type="submit">Restore</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.sessions.forceDelete', $slot->id) }}" class="form-inline">
                                        @csrf
                                        @method('DELETE')
                                        <input type="text" class="form-control input-sm" name="reason" placeholder="Alasan hard delete" required>
                                        <button class="btn btn-danger btn-xs" type="submit">Hard Delete</button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ ($tab ?? 'active') === 'active' ? 5 : 4 }}">Belum ada master slot.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($detail && ($tab ?? 'active') === 'active')
<div class="modal-overlay is-open">
    <div class="modal-card">
        <div class="split-header">
            <h3 class="card-title">Detail Sesi</h3>
            <a href="{{ route('admin.sessions') }}" class="btn btn-outline btn-xs">Tutup</a>
        </div>
        <form method="POST" action="{{ route('admin.sessions.update', $detail->id) }}" class="section modal-form">
            @csrf
            @method('PUT')
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Nama Sesi</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $detail->name) }}" required>
                </div>
                <div class="form-group">
                    <label>Mulai</label>
                    <input type="time" name="start_at" class="form-control" value="{{ old('start_at', optional($detail->start_at)->format('H:i')) }}" required>
                </div>
                <div class="form-group">
                    <label>Selesai</label>
                    <input type="time" name="end_at" class="form-control" value="{{ old('end_at', optional($detail->end_at)->format('H:i')) }}" required>
                </div>
            </div>
            <p class="card-meta">Slot yang sudah dipakai booking boleh diubah jamnya, tetapi tidak boleh dihapus.</p>
            <div class="split-actions">
                <button class="btn btn-primary btn-sm" type="submit">Update</button>
                <button class="btn btn-warning btn-sm" type="submit" form="delete-session-{{ $detail->id }}" onclick="return confirm('Hapus sesi ini?');">Hapus</button>
            </div>
        </form>
        <form id="delete-session-{{ $detail->id }}" method="POST" action="{{ route('admin.sessions.delete', $detail->id) }}">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>
@endif

    @include('components.pagination-controls', ['paginator' => $slots, 'showPerPage' => false, 'showPager' => true, 'position' => 'bottom'])
@endsection

@push('scripts')
<script>
    (function () {
        function closeOpenModal() {
            var overlay = document.querySelector('.modal-overlay.is-open');
            if (!overlay) return;
            overlay.classList.remove('is-open');
            overlay.style.display = 'none';
            overlay.setAttribute('aria-hidden', 'true');
        }

        var updateForm = document.querySelector('.modal-overlay.is-open .modal-form');
        if (updateForm) {
            updateForm.addEventListener('submit', function () {
                closeOpenModal();
            });
        }

        var deleteForm = document.querySelector('form[id^="delete-session-"]');
        if (deleteForm) {
            deleteForm.addEventListener('submit', function () {
                closeOpenModal();
            });
        }

        var bulkForm = document.getElementById('session-bulk-delete-form');
        var checkAll = document.getElementById('session-check-all');
        if (!bulkForm || !checkAll) return;

        checkAll.addEventListener('change', function () {
            document.querySelectorAll('.session-row-check').forEach(function (node) {
                node.checked = checkAll.checked;
            });
        });

        bulkForm.addEventListener('submit', function (event) {
            bulkForm.querySelectorAll('input[name="ids[]"]').forEach(function (node) {
                node.remove();
            });

            var selected = document.querySelectorAll('.session-row-check:checked');
            if (!selected.length) {
                event.preventDefault();
                alert('Pilih minimal satu sesi.');
                return;
            }

            selected.forEach(function (node) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = node.value;
                bulkForm.appendChild(input);
            });
        });
    })();
</script>
@endpush
