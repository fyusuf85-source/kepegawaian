<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'employee_id',
        'is_active',
        'must_change_password',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot('org_unit_id')
            ->withTimestamps();
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    /**
     * Cache permission codes during a single request.
     *
     * @var array<int, string>|null
     */
    protected ?array $permissionCache = null;

    public function hasPermission(string $code): bool
    {
        if ($this->permissionCache === null) {
            $this->permissionCache = $this->roles()
                ->with('permissions:id,code')
                ->get()
                ->flatMap(fn (Role $role) => $role->permissions->pluck('code'))
                ->unique()
                ->values()
                ->all();
        }

        return in_array($code, $this->permissionCache, true);
    }

    public function hasAnyPermission(array $codes): bool
    {
        foreach ($codes as $code) {
            if ($this->hasPermission($code)) {
                return true;
            }
        }

        return false;
    }

    public function hasRole(string $code): bool
    {
        return $this->roles()->where('code', $code)->exists();
    }

    /**
     * Unit organisasi yang tercakup oleh peran pengguna.
     *
     * @return array<int, int>
     */
    public function scopedOrgUnitIds(): array
    {
        $ids = $this->roles()
            ->whereNotNull('role_user.org_unit_id')
            ->pluck('role_user.org_unit_id')
            ->all();

        if ($this->employee?->org_unit_id) {
            $ids[] = $this->employee->org_unit_id;
        }

        $ids = array_values(array_unique(array_filter($ids)));

        return OrgUnit::descendantIds($ids);
    }
}
