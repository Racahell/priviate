@extends('layouts.master')

@section('title', 'Reschedule Anak')

@section('content')
<div class="card section">
    <h3 class="card-title">Ajukan Pergantian Hari</h3>
    <p class="card-meta">Pilih sesi anak (paket yang sudah dibooking), lalu pilih hari dan jam sesi sesuai master sesi admin.</p>

    @if($children->isEmpty())
        <p class="card-meta">Belum ada anak terhubung. Hubungkan anak dulu sebelum ajukan reschedule.</p>
        <a href="{{ route('parent.children') }}" class="btn btn-outline">Hubungkan Anak</a>
    @else
        <form method="POST" action="{{ route('ops.reschedule.request') }}" class="section">
            @csrf
            <div class="form-group">
                <label>Sesi Anak</label>
                <select name="tutoring_session_id" id="rescheduleSessionSelect" class="form-control" required>
                    <option value="">Pilih sesi</option>
                    @foreach($sessionOptions as $session)
                        <option value="{{ $session->id }}">
                            {{ $session->student?->name }} - {{ $session->subject?->name }}
                            @if(!empty($session->invoice?->invoice_number))
                                - Paket/Invoice: {{ $session->invoice->invoice_number }}
                            @endif
                            - {{ optional($session->scheduled_at)->format('d M Y H:i') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Hari Baru</label>
                    <select name="booking_day" id="rescheduleDaySelect" class="form-control" required>
                        <option value="">Pilih hari</option>
                        <option value="1" {{ old('booking_day') == '1' ? 'selected' : '' }}>Senin</option>
                        <option value="2" {{ old('booking_day') == '2' ? 'selected' : '' }}>Selasa</option>
                        <option value="3" {{ old('booking_day') == '3' ? 'selected' : '' }}>Rabu</option>
                        <option value="4" {{ old('booking_day') == '4' ? 'selected' : '' }}>Kamis</option>
                        <option value="5" {{ old('booking_day') == '5' ? 'selected' : '' }}>Jumat</option>
                        <option value="6" {{ old('booking_day') == '6' ? 'selected' : '' }}>Sabtu</option>
                        <option value="0" {{ old('booking_day') == '0' ? 'selected' : '' }}>Minggu</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jam Sesi Baru</label>
                    <select name="schedule_slot_id" id="rescheduleSlotSelect" class="form-control" required>
                        <option value="">Pilih jam sesi</option>
                        @foreach(($openSlots ?? collect()) as $slot)
                            <option value="{{ $slot->id }}" {{ old('schedule_slot_id') == (string) $slot->id ? 'selected' : '' }}>
                                {{ optional($slot->start_at)->format('H:i') }} - {{ optional($slot->end_at)->format('H:i') }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <p class="card-meta">Tanggal otomatis mengikuti hari terdekat dari minggu berjalan, sama seperti alur booking.</p>
            <div class="form-group">
                <label>Alasan</label>
                <textarea name="reason" class="form-control" rows="3" placeholder="Contoh: Anak ada ujian sekolah di jadwal lama"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Kirim Pengajuan Reschedule</button>
        </form>
    @endif
</div>

<div class="card section">
    <h3 class="card-title">Riwayat Reschedule</h3>
    <div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    <th>Anak</th>
                    <th>Mapel</th>
                    <th>Jadwal Baru</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rescheduleHistory as $row)
                    <tr>
                        <td>{{ $row->session?->student?->name ?? '-' }}</td>
                        <td>{{ $row->session?->subject?->name ?? '-' }}</td>
                        <td>{{ optional($row->requested_start_at)->format('d M Y H:i') }} - {{ optional($row->requested_end_at)->format('H:i') }}</td>
                        <td><span class="badge badge-warning">{{ strtoupper((string) $row->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4">Belum ada pengajuan reschedule.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
