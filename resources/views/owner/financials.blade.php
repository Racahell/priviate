@extends('layouts.master')

@section('title', 'Financial Ledger')

@section('content')
<div class="card">
    <h3 class="card-title">Jurnal Keuangan</h3>
    @include('components.pagination-controls', ['paginator' => $entries, 'showPerPage' => true, 'showPager' => false, 'position' => 'top'])
<div class="table-wrap section">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Deskripsi</th>
                    <th>Referensi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td>{{ $entry->transaction_date }}</td>
                        <td>{{ $entry->description }}</td>
                        <td>{{ $entry->reference_type }}#{{ $entry->reference_id }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Belum ada data jurnal.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

    @include('components.pagination-controls', ['paginator' => $entries, 'showPerPage' => false, 'showPager' => true, 'position' => 'bottom'])
@endsection

