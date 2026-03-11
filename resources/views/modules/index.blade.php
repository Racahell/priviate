@extends('layouts.master')

@section('title', $title)

@php
    $isSuper = $isSuperadmin ?? false;
    $routePrefix = $isSuper ? 'superadmin' : 'admin';
    $mode = $mode ?? '';
    $detail = $detail ?? null;
    $detailMode = request()->query('detail_mode', 'edit');
    $isCreateMode = $tab === 'active' && $mode === 'create';
    $isDetailMode = !empty($detail);
    $subjectOptions = $subjectOptions ?? collect();
    $weekDayOptions = $weekDayOptions ?? [];
    $moduleRoutes = [
        'packages' => $routePrefix . '.modules.packages',
        'disputes' => $routePrefix . '.modules.disputes',
        'subjects' => $routePrefix . '.modules.subjects',
        'items' => $routePrefix . '.modules.items',
        'users' => $routePrefix . '.modules.users',
        'sessions' => $routePrefix . '.modules.sessions',
    ];
    $activeRoute = $moduleRoutes[$module];
    $allowCreate = $module !== 'disputes';
    $detailHasDisputeReply = false;
    if ($module === 'disputes' && !empty($detail)) {
        $detailHasDisputeReply = collect($detail->actions ?? [])->contains(function ($a) {
            return !empty(trim((string) data_get($a, 'notes')));
        });
    }
@endphp

@section('content')
<div class="card">
    <div class="split-header">
        <h3 class="card-title">{{ $title }}</h3>
        <div class="split-actions">
            @if($tab === 'active')
                @if($allowCreate)
                    <a href="{{ route($activeRoute, ['tab' => 'active', 'mode' => 'create']) }}" class="btn btn-primary">Tambah {{ $title }}</a>
                @endif
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

    @if($isCreateMode && $allowCreate)
        <div class="modal-overlay is-open">
            <div class="modal-card">
                <div class="split-header">
                    <h3 class="card-title">Tambah {{ $title }}</h3>
                    <a class="btn btn-outline btn-xs" href="{{ route($activeRoute, ['tab' => 'active']) }}">Tutup</a>
                </div>
            <form method="POST" action="{{ route($routePrefix . '.modules.store', ['module' => $module]) }}" class="section modal-form" enctype="multipart/form-data">
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
                @elseif($module === 'disputes')
                    <div class="grid grid-3">
                        <div class="form-group"><input type="number" class="form-control" name="tutoring_session_id" placeholder="ID Sesi (opsional)" value="{{ old('tutoring_session_id') }}"></div>
                        <div class="form-group"><input class="form-control" name="reason" placeholder="Alasan Kritik" value="{{ old('reason') }}" required></div>
                        <div class="form-group">
                            <select class="form-control" name="status" required>
                                <option value="IN_REVIEW_L1" {{ old('status') === 'IN_REVIEW_L1' ? 'selected' : '' }}>IN_REVIEW_L1</option>
                                <option value="IN_REVIEW_ADMIN" {{ old('status') === 'IN_REVIEW_ADMIN' ? 'selected' : '' }}>IN_REVIEW_ADMIN</option>
                                <option value="RESOLVED" {{ old('status') === 'RESOLVED' ? 'selected' : '' }}>RESOLVED</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <select class="form-control" name="priority">
                                <option value="LOW" {{ old('priority') === 'LOW' ? 'selected' : '' }}>LOW</option>
                                <option value="MEDIUM" {{ old('priority', 'MEDIUM') === 'MEDIUM' ? 'selected' : '' }}>MEDIUM</option>
                                <option value="HIGH" {{ old('priority') === 'HIGH' ? 'selected' : '' }}>HIGH</option>
                                <option value="CRITICAL" {{ old('priority') === 'CRITICAL' ? 'selected' : '' }}>CRITICAL</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group"><textarea class="form-control" name="description" placeholder="Deskripsi kritik">{{ old('description') }}</textarea></div>
                @elseif($module === 'items')
                    <div class="grid grid-3">
                        <div class="form-group"><input class="form-control" name="sku" placeholder="SKU" value="{{ old('sku') }}" required></div>
                        <div class="form-group"><input class="form-control" name="name" placeholder="Nama Item" value="{{ old('name') }}" required></div>
                        <div class="form-group"><input type="number" class="form-control" name="price" placeholder="Harga" value="{{ old('price', 0) }}" required></div>
                        <div class="form-group"><input type="number" class="form-control" name="stock" placeholder="Stock" value="{{ old('stock', 0) }}" required></div>
                        <div class="form-group"><textarea class="form-control" name="description" placeholder="Deskripsi">{{ old('description') }}</textarea></div>
                    </div>
                    <div class="form-group checkbox"><label><input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}> Aktif</label></div>
                @elseif(in_array($module, ['users', 'user'], true) || ($title === 'User' && isset($detail->email)))
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
                    <div class="tutor-config" data-tutor-config>
                        <div class="section">
                            <h4 class="card-title" style="margin-bottom:12px;">Mapel Tutor</h4>
                            <div class="grid grid-3">
                                @foreach($subjectOptions as $subject)
                                    <label class="checkbox">
                                        <input type="checkbox" name="teaching_subject_ids[]" value="{{ $subject->id }}" {{ in_array((int) $subject->id, collect(old('teaching_subject_ids', []))->map(fn($v) => (int) $v)->all(), true) ? 'checked' : '' }}>
                                        {{ $subject->name }} ({{ $subject->level }})
                                    </label>
                                @endforeach
                            </div>
                            <div class="grid grid-3" style="margin-top:12px;">
                                <div class="form-group"><input class="form-control" name="education" placeholder="Pendidikan Terakhir" value="{{ old('education') }}"></div>
                                <div class="form-group"><input type="number" min="0" max="60" class="form-control" name="experience_years" placeholder="Pengalaman (tahun)" value="{{ old('experience_years') }}"></div>
                                <div class="form-group"><input class="form-control" name="domicile" placeholder="Domisili" value="{{ old('domicile') }}"></div>
                                <div class="form-group">
                                    <select class="form-control" name="teaching_mode">
                                        <option value="online" {{ old('teaching_mode') === 'online' ? 'selected' : '' }}>Mode: Online</option>
                                        <option value="offline" {{ old('teaching_mode') === 'offline' ? 'selected' : '' }}>Mode: Offline</option>
                                        <option value="hybrid" {{ old('teaching_mode') === 'hybrid' ? 'selected' : '' }}>Mode: Keduanya</option>
                                    </select>
                                </div>
                                <div class="form-group"><input class="form-control" name="offline_coverage" placeholder="Area Offline" value="{{ old('offline_coverage') }}"></div>
                                <div class="form-group">
                                    <select class="form-control" name="verification_status">
                                        <option value="PENDING_REVIEW" {{ old('verification_status', 'PENDING_REVIEW') === 'PENDING_REVIEW' ? 'selected' : '' }}>Status: PENDING_REVIEW</option>
                                        <option value="APPROVED" {{ old('verification_status') === 'APPROVED' ? 'selected' : '' }}>Status: VERIFIED / APPROVED</option>
                                        <option value="NEEDS_REVISION" {{ old('verification_status') === 'NEEDS_REVISION' ? 'selected' : '' }}>Status: NEEDS_REVISION</option>
                                        <option value="REJECTED" {{ old('verification_status') === 'REJECTED' ? 'selected' : '' }}>Status: REJECTED</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group"><textarea class="form-control" name="tentor_bio" placeholder="Bio / Pengalaman">{{ old('tentor_bio') }}</textarea></div>
                            <div class="form-group"><textarea class="form-control" name="verification_notes" placeholder="Catatan verifikasi admin">{{ old('verification_notes') }}</textarea></div>
                            <div class="grid grid-3">
                                <div class="form-group"><label>CV</label><input type="file" class="form-control" name="cv_file" accept=".pdf,.jpg,.jpeg,.png"></div>
                                <div class="form-group"><label>Ijazah</label><input type="file" class="form-control" name="diploma_file" accept=".pdf,.jpg,.jpeg,.png"></div>
                                <div class="form-group"><label>Sertifikat</label><input type="file" class="form-control" name="certificate_file" accept=".pdf,.jpg,.jpeg,.png"></div>
                                <div class="form-group"><label>KTP</label><input type="file" class="form-control" name="id_card_file" accept=".pdf,.jpg,.jpeg,.png"></div>
                                <div class="form-group"><label>Foto Profil</label><input type="file" class="form-control" name="profile_photo_file" accept=".jpg,.jpeg,.png"></div>
                                <div class="form-group"><input class="form-control" name="intro_video_url" placeholder="Link Video Perkenalan" value="{{ old('intro_video_url') }}"></div>
                            </div>
                        </div>
                    </div>
                @elseif($module === 'sessions')
                    <p class="card-meta">Input jam saja. Setiap tambah sesi akan membuat satu slot sesi.</p>
                    <div class="grid grid-2">
                        <div class="form-group"><input class="form-control" name="name" value="{{ old('name') }}" placeholder="Nama sesi" required></div>
                        <div class="form-group"><input type="time" class="form-control" name="start_at" value="{{ old('start_at') }}" required></div>
                        <div class="form-group"><input type="time" class="form-control" name="end_at" value="{{ old('end_at') }}" required></div>
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
                @if($module === 'sessions' && $detailMode === 'show')
                    <div class="section">
                        <div class="grid grid-2">
                            <div class="form-group">
                                <label>Nama Sesi</label>
                                <input type="text" class="form-control" value="{{ $detail->name ?: ('Sesi #' . $detail->id) }}" readonly>
                            </div>
                            <div class="form-group">
                                <label>Jam Mulai</label>
                                <input type="text" class="form-control" value="{{ optional($detail->start_at)->format('H:i') }}" readonly>
                            </div>
                            <div class="form-group">
                                <label>Jam Selesai</label>
                                <input type="text" class="form-control" value="{{ optional($detail->end_at)->format('H:i') }}" readonly>
                            </div>
                        </div>
                    </div>
                @else
                <form method="POST" action="{{ route($routePrefix . '.modules.update', ['module' => $module, 'id' => $detail->id]) }}" class="section modal-form" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    @if(in_array($module, ['users', 'user'], true) || $title === 'User')
                        @php
                            $detailTentorProfile = method_exists($detail, 'tentorProfile') ? $detail->tentorProfile : null;
                            $detailExistingSkills = collect($detailTentorProfile?->skills ?? [])->pluck('subject_id')->map(fn($v) => (int) $v)->all();
                            $detailOldSkills = collect(old('teaching_subject_ids', []))->map(fn($v) => (int) $v)->all();
                            $selectedRole = old('role', method_exists($detail, 'getRoleNames') ? $detail->getRoleNames()->first() : '');
                        @endphp
                        <div class="grid grid-2 user-form-grid">
                            <div class="form-group">
                                <label>Name</label>
                                <input class="form-control" name="name" value="{{ old('name', data_get($detail, 'name', '')) }}" required>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" class="form-control" name="email" value="{{ old('email', data_get($detail, 'email', '')) }}" required>
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input class="form-control" name="phone" value="{{ old('phone', data_get($detail, 'phone', '')) }}">
                            </div>
                            <div class="form-group">
                                <label>Role</label>
                                <select class="form-control" name="role" required>
                                    <option value="">Pilih Role</option>
                                    @foreach($formConfig['role_options'] ?? [] as $roleOpt)
                                        <option value="{{ $roleOpt }}" {{ $selectedRole === $roleOpt ? 'selected' : '' }}>{{ strtoupper($roleOpt) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" class="form-control" name="password" placeholder="Password (opsional)">
                            </div>
                            <div class="form-group">
                                <label>Konfirmasi Password</label>
                                <input type="password" class="form-control" name="password_confirmation" placeholder="Konfirmasi Password (opsional)">
                            </div>
                        </div>
                        <div class="form-group checkbox user-active-toggle">
                            <label><input type="checkbox" name="is_active" value="1" {{ old('is_active', (bool) data_get($detail, 'is_active', true)) ? 'checked' : '' }}> Aktif</label>
                        </div>
                        <div class="tutor-config" data-tutor-config>
                            <div class="section">
                                <h4 class="card-title" style="margin-bottom:12px;">Mapel Tutor</h4>
                                <div class="grid grid-3">
                                    @foreach($subjectOptions as $subject)
                                        @php
                                            $checked = !empty($detailOldSkills)
                                                ? in_array((int) $subject->id, $detailOldSkills, true)
                                                : in_array((int) $subject->id, $detailExistingSkills, true);
                                        @endphp
                                        <label class="checkbox">
                                            <input type="checkbox" name="teaching_subject_ids[]" value="{{ $subject->id }}" {{ $checked ? 'checked' : '' }}>
                                            {{ $subject->name }} ({{ $subject->level }})
                                        </label>
                                    @endforeach
                                </div>
                                <div class="grid grid-3" style="margin-top:12px;">
                                    <div class="form-group">
                                        <label>Pendidikan</label>
                                        <input class="form-control" name="education" value="{{ old('education', $detailTentorProfile?->education) }}">
                                    </div>
                                    <div class="form-group">
                                        <label>Pengalaman (tahun)</label>
                                        <input type="number" min="0" max="60" class="form-control" name="experience_years" value="{{ old('experience_years', $detailTentorProfile?->experience_years) }}">
                                    </div>
                                    <div class="form-group">
                                        <label>Domisili</label>
                                        <input class="form-control" name="domicile" value="{{ old('domicile', $detailTentorProfile?->domicile) }}">
                                    </div>
                                    <div class="form-group">
                                        <label>Mode Mengajar</label>
                                        @php($modeValue = old('teaching_mode', $detailTentorProfile?->teaching_mode ?? 'online'))
                                        <select class="form-control" name="teaching_mode">
                                            <option value="online" {{ $modeValue === 'online' ? 'selected' : '' }}>Online</option>
                                            <option value="offline" {{ $modeValue === 'offline' ? 'selected' : '' }}>Offline</option>
                                            <option value="hybrid" {{ $modeValue === 'hybrid' ? 'selected' : '' }}>Keduanya</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Area Offline</label>
                                        <input class="form-control" name="offline_coverage" value="{{ old('offline_coverage', $detailTentorProfile?->offline_coverage) }}">
                                    </div>
                                    <div class="form-group">
                                        <label>Status Verifikasi</label>
                                        @php($verifyValue = old('verification_status', $detailTentorProfile?->verification_status ?? 'PENDING_REVIEW'))
                                        <select class="form-control" name="verification_status">
                                            <option value="PENDING_REVIEW" {{ $verifyValue === 'PENDING_REVIEW' ? 'selected' : '' }}>PENDING_REVIEW</option>
                                            <option value="APPROVED" {{ $verifyValue === 'APPROVED' ? 'selected' : '' }}>VERIFIED / APPROVED</option>
                                            <option value="NEEDS_REVISION" {{ $verifyValue === 'NEEDS_REVISION' ? 'selected' : '' }}>NEEDS_REVISION</option>
                                            <option value="REJECTED" {{ $verifyValue === 'REJECTED' ? 'selected' : '' }}>REJECTED</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Bio / Pengalaman</label>
                                    <textarea class="form-control" name="tentor_bio">{{ old('tentor_bio', $detailTentorProfile?->bio) }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label>Catatan Verifikasi</label>
                                    <textarea class="form-control" name="verification_notes">{{ old('verification_notes', $detailTentorProfile?->verification_notes) }}</textarea>
                                </div>
                                <div class="grid grid-2">
                                    <div class="form-group">
                                        <label>Link Video Perkenalan</label>
                                        <input class="form-control" name="intro_video_url" value="{{ old('intro_video_url', $detailTentorProfile?->intro_video_url) }}">
                                    </div>
                                    <div class="form-group">
                                        <label>Dokumen Tersimpan</label>
                                        <div class="card-meta">
                                            CV: {{ $detailTentorProfile?->cv_path ? 'Ada' : 'Belum' }} |
                                            Ijazah: {{ $detailTentorProfile?->diploma_path ? 'Ada' : 'Belum' }} |
                                            KTP: {{ $detailTentorProfile?->id_card_path ? 'Ada' : 'Belum' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-3">
                                    <div class="form-group"><label>Ganti CV</label><input type="file" class="form-control" name="cv_file" accept=".pdf,.jpg,.jpeg,.png"></div>
                                    <div class="form-group"><label>Ganti Ijazah</label><input type="file" class="form-control" name="diploma_file" accept=".pdf,.jpg,.jpeg,.png"></div>
                                    <div class="form-group"><label>Ganti Sertifikat</label><input type="file" class="form-control" name="certificate_file" accept=".pdf,.jpg,.jpeg,.png"></div>
                                    <div class="form-group"><label>Ganti KTP</label><input type="file" class="form-control" name="id_card_file" accept=".pdf,.jpg,.jpeg,.png"></div>
                                    <div class="form-group"><label>Ganti Foto Profil</label><input type="file" class="form-control" name="profile_photo_file" accept=".jpg,.jpeg,.png"></div>
                                </div>
                            </div>
                        </div>
                    @elseif($module === 'packages')
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
                    @elseif($module === 'disputes')
                        @if(!$detailHasDisputeReply)
                            <input type="hidden" name="reply_stage" value="send_reply">
                            <div class="grid grid-3">
                                <div class="form-group">
                                    <label>ID Sesi</label>
                                    <input type="text" class="form-control" value="{{ $detail->tutoring_session_id ?: '-' }}" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Alasan Kritik</label>
                                    <input class="form-control" value="{{ $detail->reason ?? '-' }}" readonly>
                                </div>
                                <div class="form-group">
                                    @php($st = old('status', $detail->status ?? 'IN_REVIEW_ADMIN'))
                                    <label>Status</label>
                                    <select class="form-control" name="status" required>
                                        <option value="IN_REVIEW_L1" {{ $st === 'IN_REVIEW_L1' ? 'selected' : '' }}>IN_REVIEW_L1</option>
                                        <option value="IN_REVIEW_ADMIN" {{ $st === 'IN_REVIEW_ADMIN' ? 'selected' : '' }}>IN_REVIEW_ADMIN</option>
                                        <option value="RESOLVED" {{ $st === 'RESOLVED' ? 'selected' : '' }}>RESOLVED</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Deskripsi Kritik</label>
                                <textarea class="form-control" rows="3" readonly>{{ $detail->description ?? '-' }}</textarea>
                            </div>
                            <div class="form-group">
                                <label>Jawaban Admin</label>
                                <textarea class="form-control" name="reply_notes" rows="4" placeholder="Tulis jawaban untuk pengirim kritik..." required>{{ old('reply_notes') }}</textarea>
                            </div>
                        @else
                            <div class="grid grid-3">
                                <div class="form-group"><input type="number" class="form-control" name="tutoring_session_id" value="{{ old('tutoring_session_id', $detail->tutoring_session_id ?? '') }}" placeholder="ID Sesi (opsional)"></div>
                                <div class="form-group"><input class="form-control" name="reason" value="{{ old('reason', $detail->reason ?? '') }}" required></div>
                                <div class="form-group">
                                    @php($st = old('status', $detail->status ?? 'IN_REVIEW_ADMIN'))
                                    <select class="form-control" name="status" required>
                                        <option value="IN_REVIEW_L1" {{ $st === 'IN_REVIEW_L1' ? 'selected' : '' }}>IN_REVIEW_L1</option>
                                        <option value="IN_REVIEW_ADMIN" {{ $st === 'IN_REVIEW_ADMIN' ? 'selected' : '' }}>IN_REVIEW_ADMIN</option>
                                        <option value="RESOLVED" {{ $st === 'RESOLVED' ? 'selected' : '' }}>RESOLVED</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    @php($pr = old('priority', $detail->priority ?? 'MEDIUM'))
                                    <select class="form-control" name="priority">
                                        <option value="LOW" {{ $pr === 'LOW' ? 'selected' : '' }}>LOW</option>
                                        <option value="MEDIUM" {{ $pr === 'MEDIUM' ? 'selected' : '' }}>MEDIUM</option>
                                        <option value="HIGH" {{ $pr === 'HIGH' ? 'selected' : '' }}>HIGH</option>
                                        <option value="CRITICAL" {{ $pr === 'CRITICAL' ? 'selected' : '' }}>CRITICAL</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group"><textarea class="form-control" name="description">{{ old('description', $detail->description ?? '') }}</textarea></div>
                            <div class="form-group">
                                <label>Kirim Jawaban Tambahan (opsional)</label>
                                <textarea class="form-control" name="reply_notes" rows="3" placeholder="Jika diisi, akan dikirim ke email pengirim kritik.">{{ old('reply_notes') }}</textarea>
                            </div>
                        @endif
                    @elseif($module === 'items')
                        <div class="grid grid-3">
                            <div class="form-group"><input class="form-control" name="sku" value="{{ old('sku', $detail->sku ?? '') }}" required></div>
                            <div class="form-group"><input class="form-control" name="name" value="{{ old('name', $detail->name ?? '') }}" required></div>
                            <div class="form-group"><input type="number" class="form-control" name="price" value="{{ old('price', $detail->price ?? 0) }}" required></div>
                            <div class="form-group"><input type="number" class="form-control" name="stock" value="{{ old('stock', $detail->stock ?? 0) }}" required></div>
                            <div class="form-group"><textarea class="form-control" name="description">{{ old('description', $detail->description ?? '') }}</textarea></div>
                        </div>
                        <div class="form-group checkbox"><label><input type="checkbox" name="is_active" value="1" {{ old('is_active', $detail->is_active ?? true) ? 'checked' : '' }}> Aktif</label></div>
                    @elseif($module === 'sessions')
                        <div class="grid grid-3">
                            <div class="form-group"><input class="form-control" name="name" value="{{ old('name', $detail->name) }}" required></div>
                            <div class="form-group"><input type="time" class="form-control" name="start_at" value="{{ old('start_at', optional($detail->start_at)->format('H:i')) }}" required></div>
                            <div class="form-group"><input type="time" class="form-control" name="end_at" value="{{ old('end_at', optional($detail->end_at)->format('H:i')) }}" required></div>
                            <div class="form-group">
                                <select class="form-control" name="status">
                                    @php($st = old('status', $detail->status ?? 'OPEN'))
                                    <option value="OPEN" {{ $st === 'OPEN' ? 'selected' : '' }}>OPEN</option>
                                    <option value="CLOSED" {{ $st === 'CLOSED' ? 'selected' : '' }}>CLOSED</option>
                                </select>
                            </div>
                        </div>
                    @endif

                    <div class="split-actions">
                        @if($module === 'disputes' && !$detailHasDisputeReply)
                            <button class="btn btn-primary btn-sm" type="submit">Kirim Jawaban</button>
                        @else
                            <button class="btn btn-primary btn-sm" type="submit">Update</button>
                            <button class="btn btn-warning btn-sm" type="submit" form="delete-form-{{ $module }}-{{ $detail->id }}">Delete</button>
                        @endif
                    </div>
                </form>
                @endif

                @if(!($module === 'disputes' && !$detailHasDisputeReply))
                    <form id="delete-form-{{ $module }}-{{ $detail->id }}" method="POST" action="{{ route($routePrefix . '.modules.softdelete', ['module' => $module, 'id' => $detail->id]) }}">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif
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
                <option value="soft_delete">Delete</option>
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
    @include('components.pagination-controls', ['paginator' => $rows, 'showPerPage' => true, 'showPager' => false, 'position' => 'top'])
<div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="check-all"></th>
                    @foreach($columns as $column)
                        <th>{{ $column === 'id' ? 'No' : strtoupper(str_replace('_', ' ', $column)) }}</th>
                    @endforeach
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $rowIndex => $row)
                    <tr>
                        <td><input type="checkbox" value="{{ $row->id }}" class="row-check"></td>
                        @foreach($columns as $column)
                            <td>
                                @if($column === 'id')
                                    {{ ($rows->currentPage() - 1) * $rows->perPage() + $rowIndex + 1 }}
                                @elseif($column === 'role')
                                    {{ method_exists($row, 'getRoleNames') ? $row->getRoleNames()->implode(', ') : '-' }}
                                @elseif($column === 'is_active')
                                    {{ (int) $row->{$column} === 1 ? 'Aktif' : 'Nonaktif' }}
                                @elseif($module === 'sessions' && in_array($column, ['start_at', 'end_at'], true))
                                    {{ !empty($row->{$column}) ? \Illuminate\Support\Carbon::parse($row->{$column})->format('H:i') : '-' }}
                                @else
                                    {{ $row->{$column} }}
                                @endif
                            </td>
                        @endforeach
                        <td>
                            @if($tab === 'active')
                                @if($module === 'sessions')
                                    <a class="btn btn-outline btn-xs" href="{{ route($activeRoute, ['tab' => 'active', 'detail' => $row->id]) }}">Detail</a>
                                @else
                                    <a class="btn btn-outline btn-xs" href="{{ route($activeRoute, ['tab' => 'active', 'detail' => $row->id]) }}">Detail</a>
                                @endif
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
    <div class="section">
</div>
</div>

    @include('components.pagination-controls', ['paginator' => $rows, 'showPerPage' => false, 'showPager' => true, 'position' => 'bottom'])
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

        function syncTutorConfigVisibility(scope) {
            var root = scope || document;
            root.querySelectorAll('form.modal-form').forEach(function (form) {
                var roleSelect = form.querySelector('select[name="role"]');
                var tutorConfig = form.querySelector('[data-tutor-config]');
                if (!roleSelect || !tutorConfig) return;

                function apply() {
                    var roleValue = String(roleSelect.value || '').trim().toLowerCase();
                    var isTutor = roleValue === 'tentor';
                    tutorConfig.style.display = isTutor ? '' : 'none';
                }

                roleSelect.addEventListener('change', apply);
                apply();
            });
        }

        syncTutorConfigVisibility(document);

        function closeOpenModal() {
            var overlay = document.querySelector('.modal-overlay.is-open');
            if (!overlay) return;
            overlay.classList.remove('is-open');
            overlay.style.display = 'none';
            overlay.setAttribute('aria-hidden', 'true');
        }

        var modalForms = document.querySelectorAll('.modal-overlay.is-open form');
        modalForms.forEach(function (form) {
            form.addEventListener('submit', function () {
                closeOpenModal();
            });
        });

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
