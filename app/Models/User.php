<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'code',
        'parent_id',
        'password',
        'is_active',
        'last_login_at',
        'last_login_ip',
        'avatar',
        'phone',
        'address',
        'city',
        'province',
        'postal_code',
        'latitude',
        'longitude',
        'location_notes',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_ip',
        'updated_ip',
        'deleted_ip',
        'is_deleted',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_deleted' => 'boolean',
        ];
    }

    public function parentUser()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function tentorProfile()
    {
        return $this->hasOne(TentorProfile::class);
    }

    public function siswaProfile()
    {
        return $this->hasOne(SiswaProfile::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function historyEdits()
    {
        return $this->hasMany(HistoryEdit::class);
    }

    public function studentSessions()
    {
        return $this->hasMany(TutoringSession::class, 'student_id');
    }

    public function tentorSessions()
    {
        return $this->hasMany(TutoringSession::class, 'tentor_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
