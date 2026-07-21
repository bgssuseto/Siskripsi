<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_KOORDINATOR = 'koordinator';
    public const ROLE_MAHASISWA = 'mahasiswa';

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
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isKoordinator(): bool
    {
        return $this->role === self::ROLE_KOORDINATOR;
    }

    public function isMahasiswa(): bool
    {
        return $this->role === self::ROLE_MAHASISWA;
    }

    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles, true);
        }

        return $this->role === $roles;
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_SUPER_ADMIN => 'Super Admin',
            self::ROLE_KOORDINATOR => 'Koordinator',
            self::ROLE_MAHASISWA => 'Mahasiswa',
            default => 'Mahasiswa',
        };
    }

    public function getRoleBadgeClassAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_SUPER_ADMIN => 'bg-purple-100 text-purple-700 border-purple-200',
            self::ROLE_KOORDINATOR => 'bg-blue-100 text-blue-700 border-blue-200',
            self::ROLE_MAHASISWA => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            default => 'bg-gray-100 text-gray-700 border-gray-200',
        };
    }

    public function menus(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'user_menu')->withTimestamps();
    }

    /**
     * Get all accessible menus for this user:
     * - Default menus assigned to their role ('mahasiswa', 'super_admin', 'koordinator', 'all')
     * - PLUS any custom extra menus assigned to this specific user via user_menu pivot table.
     */
    public function getAccessibleMenus()
    {
        // Super Admin gets all active menus
        if ($this->isSuperAdmin()) {
            return Menu::where('is_active', true)->orderBy('sort_order')->get();
        }

        if ($this->isMahasiswa()) {
            // Mandatory default menu for Mahasiswa is "Daftar Sempro"
            $mandatory = Menu::where('is_active', true)
                ->whereIn('route', ['mahasiswa.sempro.index', 'mahasiswa.pendaftaran.index'])
                ->get();

            // Custom assigned menus for this specific user
            $customMenus = $this->menus()->where('is_active', true)->get();

            return $mandatory->concat($customMenus)->unique('id')->sortBy('sort_order')->values();
        }

        $roleDefaults = Menu::where('is_active', true)
            ->where(function ($q) {
                $q->where('role_default', 'all')
                  ->orWhere('role_default', $this->role);
            })
            ->get();

        $customMenus = $this->menus()->where('is_active', true)->get();

        return $roleDefaults->concat($customMenus)->unique('id')->sortBy('sort_order')->values();
    }

    /**
     * Check if user has access to a specific menu route
     */
    public function hasMenuAccess(string $routeOrName): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Mandatory default menu for Mahasiswa is "Daftar Sempro & Skripsi"
        if ($this->isMahasiswa() && in_array($routeOrName, ['mahasiswa.pendaftaran.index', 'mahasiswa.sempro.index'], true)) {
            return true;
        }

        $accessibleRoutes = $this->getAccessibleMenus()->pluck('route')->filter()->toArray();
        return in_array($routeOrName, $accessibleRoutes, true);
    }
}
