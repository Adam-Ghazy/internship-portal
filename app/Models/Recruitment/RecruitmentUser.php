<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class RecruitmentUser extends Authenticatable
{
    protected $table = 'recruitment.users';

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'email_verified_at' => 'datetime',
        'disabled_at' => 'datetime',
    ];

    public function staffAssignments(): HasMany
    {
        return $this->hasMany(StaffAssignment::class, 'user_id');
    }

    public function applicantProfile(): HasOne
    {
        return $this->hasOne(ApplicantProfile::class, 'user_id');
    }

    public function isApplicant(): bool
    {
        return ! $this->isStaff();
    }

    public function isStaff(): bool
    {
        return $this->activeStaffAssignments()->exists();
    }

    public function hasRole(string $code): bool
    {
        return $this->activeStaffAssignments()->where('role_code', $code)->exists();
    }

    public function activeStaffAssignments(): HasMany
    {
        return $this->staffAssignments()
            ->whereRaw('valid_from <= CURRENT_TIMESTAMP')
            ->whereRaw('(valid_to IS NULL OR CURRENT_TIMESTAMP < valid_to)');
    }
}
