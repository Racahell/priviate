@extends('layouts.master')

@section('title', 'Katalog Paket')

@section('content')
@php
    $cards = $packages->map(function ($pkg) use ($priceMap, $quotaMap) {
        $source = strtolower(trim(($pkg->name ?? '') . ' ' . ($pkg->description ?? '')));
        preg_match('/(\d+)\s*hari/', $source, $match);
        $days = isset($match[1]) ? (int) $match[1] : 0;

        return [
            'id' => $pkg->id,
            'name' => $pkg->name,
            'description' => $pkg->description ?: 'Keterangan belajar',
            'days' => $days,
            'price' => (float) ($priceMap[$pkg->id] ?? 0),
            'quota' => (int) ($quotaMap[$pkg->id] ?? 0),
        ];
    })->values();

    $tabDays = collect([1, 3, 7, 30])->filter(fn ($d) => $cards->contains(fn ($c) => $c['days'] === $d))->values();
    if ($tabDays->isEmpty()) {
        $tabDays = $cards->pluck('days')->filter(fn ($d) => $d > 0)->unique()->sort()->values();
    }
@endphp

<div class="card booking-showcase">
    <div class="booking-shell">
        <div class="booking-intro">
            <p class="booking-tag">PrivTuition Paket Belajar</p>
            <h3>Paket Kuota Belajar</h3>
            <p>Pilih paket sesuai kebutuhan belajar anak. Durasi, kuota sesi, dan harga transparan.</p>
            <a class="btn booking-cta" href="#booking-grid">Lihat semua paket</a>
        </div>

        <div class="booking-content">
            <div class="booking-tabs" id="booking-tabs">
                @foreach($tabDays as $idx => $day)
                    <button type="button" class="booking-tab {{ $idx === 0 ? 'is-active' : '' }}" data-days="{{ $day }}">
                        Paket {{ $day }} Hari
                    </button>
                @endforeach
            </div>

            <div class="booking-grid" id="booking-grid">
                @forelse($cards as $card)
                    <article class="booking-card" data-days="{{ $card['days'] }}">
                        <div class="booking-card-head">
                            <p>Keterangan Belajar</p>
                            <h4>{{ number_format(max(1, $card['quota'])) }} Sesi | {{ $card['days'] > 0 ? $card['days'].' hari' : 'fleksibel' }}</h4>
                        </div>
                        <div class="booking-card-body">
                            <p class="booking-card-name">{{ $card['name'] }}</p>
                            <p class="booking-card-desc">{{ $card['description'] }}</p>
                        </div>
                        <div class="booking-card-foot">
                            <p>Rp {{ number_format($card['price'], 0, ',', '.') }}</p>
                            <form method="POST" action="{{ route('ops.package.select') }}">
                                @csrf
                                <input type="hidden" name="package_id" value="{{ $card['id'] }}">
                                <button class="btn btn-primary btn-sm" type="submit">Pilih Paket</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="card">
                        <p class="card-meta">Belum ada paket aktif.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="section">{{ $packages->links() }}</div>
</div>

<div class="card section">
    <h3 class="card-title">Booking Sesi</h3>
    @if(!$hasPaidInvoice)
        <p class="card-meta">Silakan selesaikan pembayaran paket terlebih dahulu, lalu pilih mapel dan jam sesi.</p>
    @else
        <p class="card-meta">Pilih mapel dan jam yang tersedia. Tentor akan ditentukan otomatis sesuai kompetensi mapel.</p>
        <form method="POST" action="{{ route('ops.slot.book') }}" class="section">
            @csrf
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Mapel</label>
                    <select name="subject_id" class="form-control" required>
                        <option value="">Pilih mapel</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}{{ $subject->level ? ' - '.$subject->level : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Jam Sesi</label>
                    <select name="slot_id" class="form-control" required>
                        <option value="">Pilih jam sesi</option>
                        @foreach($openSlots as $slot)
                            <option value="{{ $slot->id }}">
                                {{ optional($slot->start_at)->format('D, d M Y H:i') }} - {{ optional($slot->end_at)->format('H:i') }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button class="btn btn-primary" type="submit">Booking Sesi</button>
        </form>
    @endif
</div>

@push('scripts')
<script>
(function () {
    var tabs = document.querySelectorAll('#booking-tabs .booking-tab');
    var cards = document.querySelectorAll('#booking-grid .booking-card');
    if (!tabs.length || !cards.length) return;

    function apply(days) {
        cards.forEach(function (card) {
            card.style.display = card.getAttribute('data-days') === String(days) ? '' : 'none';
        });
        tabs.forEach(function (tab) {
            tab.classList.toggle('is-active', tab.getAttribute('data-days') === String(days));
        });
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            apply(tab.getAttribute('data-days'));
        });
    });

    var prefer = Array.from(tabs).some(function (t) { return t.getAttribute('data-days') === '7'; }) ? '7' : tabs[0].getAttribute('data-days');
    apply(prefer);
})();
</script>
@endpush
@endsection
