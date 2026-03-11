@extends('layouts.master')

@section('title', 'Activity Log')

@section('content')
<div class="card">
    <div class="split-header">
        <h3 class="card-title">Activity Log</h3>
    </div>
    <p class="card-meta">Pantau aktivitas user: login, aksi, input, IP, koordinat, dan waktu.</p>

    <form method="GET" action="{{ route('admin.activity.logs') }}" class="form-inline section">
        <input type="text" name="q" class="form-control input-sm" placeholder="Cari event/ip/url/browser..." value="{{ $q ?? '' }}">
        <select name="action" class="form-control input-sm">
            <option value="">Semua Aksi</option>
            @foreach($actionOptions as $opt)
                <option value="{{ $opt }}" {{ ($action ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
            @endforeach
        </select>
        <select name="role" class="form-control input-sm">
            <option value="">Semua Role</option>
            @foreach($roleOptions as $opt)
                <option value="{{ $opt }}" {{ ($role ?? '') === $opt ? 'selected' : '' }}>{{ strtoupper($opt) }}</option>
            @endforeach
        </select>
        <button class="btn btn-outline btn-sm" type="submit">Filter</button>
        <a class="btn btn-link btn-sm" href="{{ route('admin.activity.logs') }}">Reset</a>
    </form>
</div>

<div class="card section">
    <h3 class="card-title">Log Aktivitas</h3>
    @include('components.pagination-controls', ['paginator' => $auditLogs, 'perPageKey' => 'per_page', 'showPerPage' => true, 'showPager' => false, 'position' => 'top'])
    <div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Aksi</th>
                    <th>Input / Output</th>
                    <th>IP</th>
                    <th>Koordinat</th>
                    <th>Map</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                @forelse($auditLogs as $index => $log)
                    @php
                        $lat = $log->latitude;
                        $lng = $log->longitude;
                        $mapsUrl = (!is_null($lat) && !is_null($lng))
                            ? ('https://www.google.com/maps?q=' . $lat . ',' . $lng)
                            : null;
                        $newValues = is_array($log->new_values) ? $log->new_values : [];
                        $oldValues = is_array($log->old_values) ? $log->old_values : [];
                        $friendlyParts = [];
                        $actor = $log->user?->name ?? ('User #' . ($log->user_id ?: '-'));
                        $target = class_basename((string) ($log->auditable_type ?? 'data'));
                        $actionText = strtoupper((string) ($log->action ?? 'AKSI'));

                        if ($actionText === 'UPDATE' && !empty($newValues)) {
                            foreach ($newValues as $k => $v) {
                                $old = $oldValues[$k] ?? null;
                                if (is_array($v) || is_array($old)) {
                                    continue;
                                }
                                if ((string) $old === (string) $v) {
                                    continue;
                                }
                                $oldText = $old === null || $old === '' ? '-' : (string) $old;
                                $newText = $v === null || $v === '' ? '-' : (string) $v;
                                $friendlyParts[] = str_replace('_', ' ', (string) $k) . ' dari "' . $oldText . '" menjadi "' . $newText . '"';
                                if (count($friendlyParts) >= 2) {
                                    break;
                                }
                            }
                            $inputText = count($friendlyParts) > 0
                                ? $actor . ' mengubah ' . $target . ': ' . implode(', ', $friendlyParts) . '.'
                                : $actor . ' mengubah ' . $target . '.';
                        } elseif ($actionText === 'USER_INPUT') {
                            $routeName = (string) ($newValues['route'] ?? '-');
                            $method = (string) ($newValues['method'] ?? '-');
                            $inputText = $actor . ' mengirim input ke ' . $routeName . ' (' . $method . ').';
                        } elseif (!empty($newValues)) {
                            foreach ($newValues as $k => $v) {
                                if (is_array($v)) continue;
                                $friendlyParts[] = str_replace('_', ' ', (string) $k) . ' "' . (string) $v . '"';
                                if (count($friendlyParts) >= 2) break;
                            }
                            $inputText = count($friendlyParts) > 0
                                ? $actor . ' melakukan ' . $actionText . ': ' . implode(', ', $friendlyParts) . '.'
                                : $actor . ' melakukan ' . $actionText . '.';
                        } else {
                            $inputText = $actor . ' melakukan ' . $actionText . '.';
                        }
                    @endphp
                    <tr>
                        <td>{{ ($auditLogs->currentPage() - 1) * $auditLogs->perPage() + $index + 1 }}</td>
                        <td>{{ $log->user?->name ?? ('User #' . ($log->user_id ?: '-')) }}</td>
                        <td>{{ strtoupper($log->role ?? '-') }}</td>
                        <td>{{ strtoupper($log->action ?? $log->event ?? '-') }}</td>
                        <td>
                            <div style="max-width:320px;">
                                <div><strong>URL:</strong> {{ $log->url ?? '-' }}</div>
                                <div><strong>Input:</strong> <span style="word-break:break-word;">{{ $inputText }}</span></div>
                            </div>
                        </td>
                        <td>{{ $log->ip_address ?? '-' }}</td>
                        <td>{{ !is_null($lat) && !is_null($lng) ? ($lat . ', ' . $lng) : '-' }}</td>
                        <td>
                            @if($mapsUrl)
                                <a class="btn btn-outline btn-xs" target="_blank" rel="noopener" href="{{ $mapsUrl }}">View Map</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ optional($log->created_at)->format('d M Y H:i:s') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9">Belum ada log aktivitas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('components.pagination-controls', ['paginator' => $auditLogs, 'perPageKey' => 'per_page', 'showPerPage' => false, 'showPager' => true, 'position' => 'bottom'])
</div>
@endsection
