@php
    $paginator = $paginator ?? null;
    $perPageKey = $perPageKey ?? 'per_page';
    $position = $position ?? 'bottom';
    $showPerPage = $showPerPage ?? true;
    $showPager = $showPager ?? true;
    $isPaginator = is_object($paginator)
        && method_exists($paginator, 'currentPage')
        && method_exists($paginator, 'perPage')
        && method_exists($paginator, 'onFirstPage');
@endphp

@if($isPaginator)
    <div class="split-actions section" style="justify-content:space-between; gap:10px; align-items:center; flex-wrap:nowrap; {{ $position === 'top' ? 'margin-top:0;' : '' }}">
        <div>
            @if($showPerPage)
                <form method="GET" action="{{ request()->url() }}" class="form-inline" style="margin:0; flex-wrap:nowrap;">
                    @foreach(request()->except(['page', 'audit_page', 'login_page', $perPageKey]) as $key => $value)
                        @if(is_array($value))
                            @foreach($value as $item)
                                <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <label for="per_page_{{ $paginator->getPageName() }}">Tampilkan</label>
                    <select id="per_page_{{ $paginator->getPageName() }}" name="{{ $perPageKey }}" class="form-control input-sm" onchange="this.form.submit()">
                        @foreach([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" {{ (int) request($perPageKey, $paginator->perPage()) === $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                    <span>data</span>
                </form>
            @endif
        </div>
        <div class="form-inline" style="gap:6px; align-items:center; flex-wrap:nowrap; white-space:nowrap; overflow-x:auto;">
            @if($showPager)
            @if($paginator->onFirstPage())
                <span class="btn btn-outline btn-xs" style="opacity:.6; pointer-events:none;">Previous</span>
            @else
                <a class="btn btn-outline btn-xs" href="{{ $paginator->previousPageUrl() }}">Previous</a>
            @endif

            @php
                $current = $paginator->currentPage();
                $last = $paginator->lastPage();
                $start = max(1, $current - 2);
                $end = min($last, $current + 2);
            @endphp

            @if($start > 1)
                <a class="btn btn-outline btn-xs" href="{{ $paginator->url(1) }}">1</a>
                @if($start > 2)
                    <span class="btn btn-outline btn-xs" style="opacity:.7; pointer-events:none;">...</span>
                @endif
            @endif

            @for($page = $start; $page <= $end; $page++)
                @if($page === $current)
                    <span class="btn btn-primary btn-xs">{{ $page }}</span>
                @else
                    <a class="btn btn-outline btn-xs" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                @endif
            @endfor

            @if($end < $last)
                @if($end < $last - 1)
                    <span class="btn btn-outline btn-xs" style="opacity:.7; pointer-events:none;">...</span>
                @endif
                <a class="btn btn-outline btn-xs" href="{{ $paginator->url($last) }}">{{ $last }}</a>
            @endif

            @if($paginator->hasMorePages())
                <a class="btn btn-outline btn-xs" href="{{ $paginator->nextPageUrl() }}">Next</a>
            @else
                <span class="btn btn-outline btn-xs" style="opacity:.6; pointer-events:none;">Next</span>
            @endif
            @endif
        </div>
    </div>
@endif
