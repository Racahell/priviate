@extends('layouts.master')

@section('title', $title)

@php
    $isSuper = $isSuperadmin ?? false;
    $routePrefix = $isSuper ? 'superadmin' : 'admin';
    $mode = $mode ?? '';
    $detail = $detail ?? null;
    $isCreateMode = $tab === 'active' && $mode === 'create';
    $isDetailMode = !empty($detail);
    $moduleRoutes = [
        'packages' => $routePrefix . '.modules.packages',
        'subjects' => $routePrefix . '.modules.subjects',
        'items' => $routePrefix . '.modules.items',
        'users' => $routePrefix . '.modules.users',
    ];
    $activeRoute = $moduleRoutes[$module];
@endphp

@section('content')
<div class="card">
    <div class="split-header">
        <h3 class="card-title">{{ $title }}</h3>
        <div class="split-actions">
            @if($tab === 'active')
                <a href="{{ route($activeRoute, ['tab' => 'active', 'mode' => 'create']) }}" class="btn btn-primary">Tambah {{ $title }}</a>
                @if($isSuper)
                    <a href="{{ route($activeRoute, ['tab' => 'deleted']) }}" class="btn btn-outline">Lihat Deleted</a>
                @endif
                @if($isDetailMode)
                    <a href="{{ route($activeRoute, ['tab' => 'active']) }}" class="btn btn-outline">Tutup Detail</a>
                @endif
            @elseif($isSuper)
                <a href="{{ route($activeRoute, ['tab' => 'active']) }}" class="btn {{ $tab === 'active' ? 'btn-primary' : 'btn-outline' }}">Active</a>
                <a href="{{ route($activeRoute, ['tab' => 'deleted']) }}" class="btn {{ $tab === 'deleted' ? 'btn-primary' : 'btn-outline' }}">Deleted</a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul style="margin:0; padding-left:18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="GET" action="{{ route($activeRoute) }}" class="form-inline section">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="text" name="q" class="form-control input-sm" placeholder="Search..." value="{{ $q ?? '' }}">
        <select name="active" class="form-control input-sm">
            <option value="">Semua Status</option>
            <option value="1" {{ ($activeFilter ?? '') === '1' ? 'selected' : '' }}>Aktif</option>
            <option value="0" {{ ($activeFilter ?? '') === '0' ? 'selected' : '' }}>Nonaktif</option>
        </select>
        <button class="btn btn-outline btn-sm" type="submit">Filter</button>
        <a class="btn btn-link btn-sm" href="{{ route($activeRoute, ['tab' => $tab]) }}">Reset</a>
    </form>

    @if($isCreateMode)
        <div class="modal-overlay is-open">
            <div class="modal-card">
                <div class="split-header">
                    <h3 class="card-title">Tambah {{ $title }}</h3>
                    <a class="btn btn-outline btn-xs" href="{{ route($activeRoute, ['tab' => 'active']) }}">Tutup</a>
                </div>
            <form method="POST" action="{{ route($routePrefix . '.modules.store', ['module' => $module]) }}" class="section modal-form">
                @csrf

                @if($module === 'packages')
                    <div class="grid grid-3">
                        <div class="form-group"><input class="form-control" name="name" placeholder="Nama Paket" value="{{ old('name') }}" required></div>
                        <div class="form-group"><input type="number" class="form-control" name="price" placeholder="Harga" value="{{ old('price') }}" required></div>
                        <div class="form-group"><input type="number" class="form-control" name="quota" placeholder="Kuota" value="{{ old('quota') }}"></div>
                        <div class="form-group"><input type="number" class="form-control" name="trial_limit" placeholder="Trial Limit" value="{{ old('trial_limit', 0) }}"></div>
                        <div class="form-group checkbox"><label><input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}> Aktif</label></div>
                        <div class="form-group checkbox"><label><input type="checkbox" name="trial_enabled" value="1" {{ old('trial_enabled', false) ? 'checked' : '' }}> Trial</label></div>
                    </div>
                    <div class="form-group"><textarea class="form-control" name="description" placeholder="Deskripsi">{{ old('description') }}</textarea></div>
                @elseif($module === 'subjects')
                    <div class="grid grid-3">
                        <div class="form-group"><input class="form-control" name="name" placeholder="Nama Mapel" value="{{ old('name') }}" required></div>
                        <div class="form-group"><input class="form-control" name="level" placeholder="Level" value="{{ old('level') }}" required></div>
                        <div class="form-group"><textarea class="form-control" name="description" placeholder="Deskripsi">{{ old('description') }}</textarea></div>
                    </div>
                    <div class="form-group checkbox"><label><input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}> Aktif</label></div>
                @elseif($module === 'items')
                    <div class="grid grid-3">
                        <div class="form-group"><input class="form-control" name="sku" placeholder="SKU" value="{{ old('sku') }}" required></div>
                        <div class="form-group"><input class="form-control" name="name" placeholder="Nama Item" value="{{ old('name') }}" required></div>
                        <div class="form-group"><input type="number" class="form-control" name="price" placeholder="Harga" value="{{ old('price', 0) }}" required></div>
                        <div class="form-group"><input type="number" class="form-control" name="stock" placeholder="Stock" value="{{ old('stock', 0) }}" required></div>
                        <div class="form-group"><textarea class="form-control" name="description" placeholder="Deskripsi">{{ old('description') }}</textarea></div>
                    </div>
                    <div class="form-group checkbox"><label><input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}> Aktif</label></div>
                @elseif($module === 'users')
                    <div class="grid grid-3">
                        <div class="form-group"><input class="form-control" name="name" placeholder="Nama" value="{{ old('name') }}" required></div>
                        <div class="form-group"><input type="email" class="form-control" name="email" placeholder="Email" value="{{ old('email') }}" required></div>
                        <div class="form-group"><input class="form-control" name="phone" placeholder="Phone" value="{{ old('phone') }}"></div>
                        <div class="form-group">
                            <select class="form-control" name="role" required>
                                <option value="">Pilih Role</option>
                                @foreach($formConfig['role_options'] ?? [] as $roleOpt)
                                    <option value="{{ $roleOpt }}" {{ old('role') === $roleOpt ? 'selected' : '' }}>{{ strtoupper($roleOpt) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group checkbox"><label><input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}> Aktif</label></div>
                        <div class="form-group"><input type="password" class="form-control" name="password" placeholder="Password" required></div>
                        <div class="form-group"><input type="password" class="form-control" name="password_confirmation" placeholder="Konfirmasi Password" required></div>
                    </div>
                @endif

                <div class="split-actions">
                    <button class="btn btn-primary btn-sm" type="submit">Simpan</button>
                    <a class="btn btn-outline btn-sm" href="{{ route($activeRoute, ['tab' => 'active']) }}">Batal</a>
                </div>
            </form>
            </div>
        </div>
    @endif

    @if($isDetailMode)
        <div class="modal-overlay is-open">
            <div class="modal-card">
            <div class="split-header">
                <h3 class="card-title">Detail {{ $title }} #{{ $detail->id }}</h3>
                <a class="btn btn-outline btn-xs" href="{{ route($activeRoute, ['tab' => $tab]) }}">Tutup</a>
            </div>
            @if($tab === 'active')
                <form method="POST" action="{{ route($routePrefix . '.modules.update', ['module' => $module, 'id' => $detail->id]) }}" class="section modal-form">
                    @csrf
                    @method('PUT')

                    @if($module === 'packages')
                        <div class="grid grid-3">
                            <div class="form-group"><input class="form-control" name="name" value="{{ old('name', $detail->name ?? '') }}" required></div>
                            <div class="form-group"><input type="number" class="form-control" name="price" value="{{ old('price', $detail?->id ? \App\Models\PackagePrice::where('package_id', $detail->id)->where('is_active', true)->value('price') : '') }}" required></div>
                            <div class="form-group"><input type="number" class="form-control" name="quota" value="{{ old('quota', $detail?->id ? \App\Models\PackageQuota::where('package_id', $detail->id)->where('is_active', true)->value('quota') : '') }}"></div>
                            <div class="form-group"><input type="number" class="form-control" name="trial_limit" value="{{ old('trial_limit', $detail->trial_limit ?? 0) }}"></div>
                            <div class="form-group checkbox"><label><input type="checkbox" name="is_active" value="1" {{ old('is_active', $detail->is_active ?? true) ? 'checked' : '' }}> Aktif</label></div>
                            <div class="form-group checkbox"><label><input type="checkbox" name="trial_enabled" value="1" {{ old('trial_enabled', $detail->trial_enabled ?? false) ? 'checked' : '' }}> Trial</label></div>
                        </div>
                        <div class="form-group"><textarea class="form-control" name="description">{{ old('description', $detail->description ?? '') }}</textarea></div>
                    @elseif($module === 'subjects')
                        <div class="grid grid-3">
                            <div class="form-group"><input class="form-control" name="name" value="{{ old('name', $detail->name ?? '') }}" required></div>
                            <div class="form-group"><input class="form-control" name="level" value="{{ old('level', $detail->level ?? '') }}" required></div>
                            <div class="form-group"><textarea class="form-control" name="description">{{ old('description', $detail->description ?? '') }}</textarea></div>
                        </div>
                        <div class="form-group checkbox"><label><input type="checkbox" name="is_active" value="1" {{ old('is_active', $detail->is_active ?? true) ? 'checked' : '' }}> Aktif</label></div>
                    @elseif($module === 'items')
                        <div class="grid grid-3">
                            <div class="form-group"><input class="form-control" name="sku" value="{{ old('sku', $detail->sku ?? '') }}" required></div>
                            <div class="form-group"><input class="form-control" name="name" value="{{ old('name', $detail->name ?? '') }}" required></div>
                            <div class="form-group"><input type="number" class="form-control" name="price" value="{{ old('price', $detail->price ?? 0) }}" required></div>
                            <div class="form-group"><input type="number" class="form-control" name="stock" value="{{ old('stock', $detail->stock ?? 0) }}" required></div>
                            <div class="form-group"><textarea class="form-control" name="description">{{ old('description', $detail->description ?? '') }}</textarea></div>
                        </div>
                        <div class="form-group checkbox"><label><input type="checkbox" name="is_active" value="1" {{ old('is_active', $detail->is_active ?? true) ? 'checked' : '' }}> Aktif</label></div>
                    @elseif($module === 'users')
                        <div class="grid grid-3">
                            <div class="form-group"><input class="form-control" name="name" value="{{ old('name', $detail->name ?? '') }}" required></div>
                            <div class="form-group"><input type="email" class="form-control" name="email" value="{{ old('email', $detail->email ?? '') }}" required></div>
                            <div class="form-group"><input class="form-control" name="phone" value="{{ old('phone', $detail->phone ?? '') }}"></div>
                            <div class="form-group">
                                <select class="form-control" name="role" required>
                                    <option value="">Pilih Role</option>
                                    @foreach($formConfig['role_options'] ?? [] as $roleOpt)
                                        <option value="{{ $roleOpt }}" {{ old('role', $detail?->getRoleNames()->first()) === $roleOpt ? 'selected' : '' }}>{{ strtoupper($roleOpt) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group checkbox"><label><input type="checkbox" name="is_active" value="1" {{ old('is_active', $detail->is_active ?? true) ? 'checked' : '' }}> Aktif</label></div>
                            <div class="form-group"><input type="password" class="form-control" name="password" placeholder="Password (opsional)"></div>
                            <div class="form-group"><input type="password" class="form-control" name="password_confirmation" placeholder="Konfirmasi Password (opsional)"></div>
                        </div>
                    @endif

                    <div class="split-actions">
                        <button class="btn btn-primary btn-sm" type="submit">Update</button>
                    </div>
                </form>

                <form method="POST" action="{{ route($routePrefix . '.modules.softdelete', ['module' => $module, 'id' => $detail->id]) }}" class="section">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-warning btn-sm" type="submit">Soft Delete</button>
                </form>
            @elseif($isSuper)
                <div class="split-actions">
                    <form method="POST" action="{{ route('superadmin.modules.restore', ['module' => $module, 'id' => $detail->id]) }}">
                        @csrf
                        <button class="btn btn-success btn-sm" type="submit">Restore</button>
                    </form>
                    <form method="POST" action="{{ route('superadmin.modules.forceDelete', ['module' => $module, 'id' => $detail->id]) }}" class="form-inline">
                        @csrf
                        @method('DELETE')
                        <input type="text" name="reason" placeholder="Alasan hard delete" class="form-control input-sm" required>
                        <button class="btn btn-danger btn-sm" type="submit">Hard Delete</button>
                    </form>
                </div>
            @endif
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route($routePrefix . '.modules.bulk', ['module' => $module]) }}" id="bulk-form" class="section">
        @csrf
        <div class="form-inline">
            <select name="action" class="form-control input-sm">
                <option value="">Bulk Action</option>
                <option value="soft_delete">Soft Delete</option>
                @if($isSuper && $tab === 'deleted')
                    <option value="restore">Restore</option>
                    <option value="force_delete">Hard Delete</option>
                @endif
            </select>
            @if($isSuper && $tab === 'deleted')
                <input type="text" name="reason" class="form-control input-sm" placeholder="Alasan hard delete bulk">
            @endif
            <button type="submit" class="btn btn-warning btn-sm">Apply</button>
        </div>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="check-all"></th>
                    @foreach($columns as $column)
                        <th>{{ strtoupper(str_replace('_', ' ', $column)) }}</th>
                    @endforeach
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td><input type="checkbox" value="{{ $row->id }}" class="row-check"></td>
                        @foreach($columns as $column)
                            <td>
                                @if($column === 'role')
                                    {{ method_exists($row, 'getRoleNames') ? $row->getRoleNames()->implode(', ') : '-' }}
                                @elseif($column === 'is_active')
                                    {{ (int) $row->{$column} === 1 ? 'Aktif' : 'Nonaktif' }}
                                @else
                                    {{ $row->{$column} }}
                                @endif
                            </td>
                        @endforeach
                        <td>
                            @if($tab === 'active')
                                <a class="btn btn-outline btn-xs" href="{{ route($activeRoute, ['tab' => 'active', 'detail' => $row->id]) }}">Detail</a>
                            @elseif($isSuper)
                                <a class="btn btn-outline btn-xs" href="{{ route($activeRoute, ['tab' => 'deleted', 'detail' => $row->id]) }}">Detail</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) + 2 }}">Data kosong.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($isSuper && $tab === 'deleted')
        <div class="alert alert-info section">Hard delete tersedia untuk aksi individual maupun bulk (wajib alasan).</div>
    @endif
    <div class="section">{{ $rows->links() }}</div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        function prettifyLabel(raw) {
            if (!raw) return '';
            return String(raw)
                .replace(/_/g, ' ')
                .replace(/\s+/g, ' ')
                .trim()
                .replace(/\b\w/g, function (m) { return m.toUpperCase(); });
        }

        function injectFieldLabels() {
            document.querySelectorAll('.modal-form .form-group').forEach(function (group) {
                var field = group.querySelector('input:not([type="checkbox"]):not([type="radio"]), select, textarea');
                if (!field) return;
                if (group.querySelector('label')) return;

                var labelText = field.getAttribute('data-label')
                    || field.getAttribute('placeholder')
                    || prettifyLabel(field.getAttribute('name') || '');
                if (!labelText) return;

                labelText = labelText.replace(/\s*\(opsional\)\s*/i, '').replace(/\.\.\./g, '').trim();
                if (!labelText) return;

                var label = document.createElement('label');
                label.textContent = labelText;
                group.insertBefore(label, field);
            });
        }

        injectFieldLabels();

        var checkAll = document.getElementById('check-all');
        if (!checkAll) return;
        checkAll.addEventListener('change', function () {
            document.querySelectorAll('.row-check').forEach(function (node) {
                node.checked = checkAll.checked;
            });
        });

        var bulkForm = document.getElementById('bulk-form');
        if (!bulkForm) return;
        bulkForm.addEventListener('submit', function () {
            bulkForm.querySelectorAll('input[name="ids[]"]').forEach(function (node) {
                node.remove();
            });
            document.querySelectorAll('.row-check:checked').forEach(function (node) {
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
