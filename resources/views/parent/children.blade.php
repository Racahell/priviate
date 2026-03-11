@extends('layouts.master')

@section('title', 'Hubungkan Anak')

@section('content')
<div class="card">
    <h3 class="card-title">Masukkan Kode Siswa</h3>
    <p class="card-meta">Input kode dari profil siswa untuk menghubungkan akun orang tua dan memantau progress belajar anak.</p>

    <form method="POST" action="{{ route('parent.children.link') }}" class="section" style="max-width: 580px;">
        @csrf
        <div class="form-group">
            <label>Kode Siswa</label>
            <input type="text" name="student_code" class="form-control" placeholder="Contoh: SIS-AB12CD34" value="{{ old('student_code') }}" required>
        </div>
        <button type="submit" class="btn btn-primary">Hubungkan Anak</button>
    </form>
</div>

<div class="card section">
    <h3 class="card-title">Daftar Anak Terhubung</h3>
    @if($children->isEmpty())
        <p class="card-meta">Belum ada anak yang terhubung.</p>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Kode</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($children as $child)
                        <tr>
                            <td>{{ $child->name }}</td>
                            <td>{{ $child->email }}</td>
                            <td>{{ $child->code ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
