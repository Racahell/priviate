@extends('layouts.master')

@section('title', 'Kritik Anak')

@section('content')
<div class="card section">
    <h3 class="card-title">Buat Kritik</h3>
    <p class="card-meta">Sampaikan kritik terkait sesi pembelajaran anak.</p>

    @if($children->isEmpty())
        <p class="card-meta">Belum ada anak terhubung. Hubungkan anak dulu sebelum kirim kritik.</p>
        <a href="{{ route('parent.children') }}" class="btn btn-outline">Hubungkan Anak</a>
    @else
        <form method="POST" action="{{ route('ops.dispute.create') }}" class="section">
            @csrf
            <div class="form-group">
                <label>Sesi Anak</label>
                <select name="tutoring_session_id" class="form-control" required>
                    <option value="">Pilih sesi</option>
                    @foreach($sessionOptions as $session)
                        <option value="{{ $session->id }}">
                            {{ $session->student?->name }} - {{ $session->subject?->name }} - {{ optional($session->scheduled_at)->format('d M Y H:i') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Kategori Kritik</label>
                <select name="reason" class="form-control" required>
                    <option value="">Pilih kategori</option>
                    <option value="MATERI_TIDAK_SESUAI">Materi Tidak Sesuai</option>
                    <option value="TUTOR_TERLAMBAT">Tutor Terlambat</option>
                    <option value="PERILAKU_TUTOR">Perilaku Tutor</option>
                    <option value="KENDALA_TEKNIS">Kendala Teknis</option>
                    <option value="LAINNYA">Lainnya</option>
                </select>
            </div>
            <div class="form-group">
                <label>Deskripsi Kritik</label>
                <textarea name="description" class="form-control" rows="4" placeholder="Tuliskan detail kritik Anda..." required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Kirim Kritik</button>
        </form>
    @endif
</div>

<div class="card section">
    <h3 class="card-title">Riwayat Kritik</h3>
    <div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    <th>Anak</th>
                    <th>Mapel</th>
                    <th>Alasan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($disputeHistory as $row)
                    <tr>
                        <td>{{ $row->session?->student?->name ?? '-' }}</td>
                        <td>{{ $row->session?->subject?->name ?? '-' }}</td>
                        <td>{{ $row->reason }}</td>
                        <td><span class="badge badge-info">{{ strtoupper((string) $row->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4">Belum ada kritik yang diajukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
