<?php

namespace App\Models;

use App\Mail\ResetPasswordMail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'branch_id',
        'profile_image',
    ];

    protected static function booted(): void
    {
        static::saved(function (User $user) {
            if ($user->email === 'superadmin@multicred.com' || ($user->relationLoaded('roles') && $user->roles->contains('slug', 'super_admin'))) {
                $user->ensureSuperAdminAgentProfile();
            }
        });
    }

    public function getProfileImageUrlAttribute(): ?string
    {
        $image = $this->profile_image ?? $this->agentProfile?->profile_image;

        if (! empty($image)) {
            if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
                return $image;
            }

            if (file_exists(public_path($image))) {
                return asset($image);
            }

            return asset('storage/'.ltrim($image, '/'));
        }

        if ($this->isSuperAdmin()) {
            if (file_exists(public_path('logo.png'))) {
                return asset('logo.png');
            }

            if (file_exists(public_path('logo-transparent.png'))) {
                return asset('logo-transparent.png');
            }
        }

        return null;
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function staffProfile()
    {
        return $this->hasOne(Staff::class, 'user_id');
    }

    public function agentProfile()
    {
        return $this->hasOne(Agent::class, 'user_id');
    }

    public function getAgentProfileAttribute(): ?Agent
    {
        if ($this->relationLoaded('agentProfile')) {
            $profile = $this->getRelation('agentProfile');
            if ($profile) {
                return $profile;
            }
        }

        if ($this->isSuperAdmin()) {
            $agent = $this->ensureSuperAdminAgentProfile();
            if ($agent) {
                $this->setRelation('agentProfile', $agent);

                return $agent;
            }
        }

        return $this->getRelationValue('agentProfile');
    }

    /**
     * Dynamically ensure that a Super Admin user has an active Agent profile
     * with the top rank: Deputy Director (Rank 12).
     */
    public function ensureSuperAdminAgentProfile(): ?Agent
    {
        if (! $this->isSuperAdmin()) {
            return null;
        }

        $ddRank = CareerLevel::where('level', 12)
            ->orWhere('code', 'DEPUTY_DIRECTOR')
            ->first();

        if (! $ddRank) {
            return null;
        }

        /** @var Agent|null $agent */
        $agent = Agent::where('user_id', $this->id)->first();

        if (! $agent) {
            $code = Agent::generateAgentId($this->name, $this->created_at ?? now());
            $agent = Agent::create([
                'user_id' => $this->id,
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone ?? '9999999999',
                'status' => 'active',
                'rank_id' => $ddRank->id,
                'branch_id' => null, // Global access across all branches
                'agent_id' => $code,
                'member_code' => $code,
                'joining_date' => $this->created_at?->toDateString() ?? now()->toDateString(),
                'parent_agent_id' => null,
                'profile_image' => $this->profile_image,
            ]);
        } else {
            $dirty = false;
            if ((int) $agent->rank_id !== (int) $ddRank->id) {
                $agent->rank_id = $ddRank->id;
                $dirty = true;
            }
            if ($agent->status !== 'active') {
                $agent->status = 'active';
                $dirty = true;
            }
            if (! empty($this->name) && $agent->name !== $this->name) {
                $agent->name = $this->name;
                $dirty = true;
            }
            if (! empty($this->email) && $agent->email !== $this->email) {
                $agent->email = $this->email;
                $dirty = true;
            }
            if ($this->profile_image !== null && $agent->profile_image !== $this->profile_image) {
                $agent->profile_image = $this->profile_image;
                $dirty = true;
            }
            if ($dirty) {
                $agent->save();
            }
        }

        return $agent;
    }

    // ─── Role Helpers ────────────────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->roles->contains('slug', 'super_admin') || $this->email === 'superadmin@multicred.com';
    }

    public function isBranchAdmin(): bool
    {
        return $this->roles->contains('slug', 'admin');
    }

    public function isAdmin(): bool
    {
        return $this->roles->contains(fn ($r) => in_array($r->slug, ['super_admin', 'admin']));
    }

    public function isAgent(): bool
    {
        return $this->roles->contains('slug', 'agent');
    }

    public function isStaff(): bool
    {
        return $this->roles->contains(fn ($r) => in_array($r->slug, ['staff', 'employee', 'facilitator', 'finance']));
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    public function permissions()
    {
        if ($this->isStaff() && ! $this->isAdmin()) {
            $perms = $this->staffProfile?->permissions;

            return collect(is_array($perms) ? $perms : []);
        }

        if ($this->staffProfile && ! $this->isAdmin()) {
            $perms = $this->staffProfile->permissions;

            return collect(is_array($perms) ? $perms : []);
        }

        return $this->roles->loadMissing('permissions')->flatMap->permissions->pluck('slug')->unique();
    }

    public function hasPermission(string $slug): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Staff permissions are strictly controlled by the permissions array in their staff profile
        if ($this->isStaff() && ! $this->isAdmin()) {
            $perms = $this->staffProfile?->permissions;

            return is_array($perms) && in_array($slug, $perms, true);
        }

        if ($this->staffProfile && ! $this->isAdmin()) {
            $perms = $this->staffProfile->permissions;

            return is_array($perms) && in_array($slug, $perms, true);
        }

        // Agents do not have administrative permissions
        if ($this->isAgent() && ! $this->isAdmin()) {
            return false;
        }

        return $this->permissions()->contains($slug);
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token): void
    {
        $url = route('password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ]);

        Mail::to($this->getEmailForPasswordReset())
            ->send(new ResetPasswordMail($this, $token, $url));
    }
}
