<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'dosen_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_KOORDINATOR = 'koordinator';
    public const ROLE_MAHASISWA = 'mahasiswa';
    public const ROLE_DOSEN = 'dosen';

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

    public function isDosen(): bool
    {
        return $this->role === self::ROLE_DOSEN;
    }

    public function dosen(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_id');
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
            self::ROLE_DOSEN => 'Dosen',
            default => 'Mahasiswa',
        };
    }

    public function getRoleBadgeClassAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_SUPER_ADMIN => 'bg-purple-100 text-purple-700 border-purple-200',
            self::ROLE_KOORDINATOR => 'bg-blue-100 text-blue-700 border-blue-200',
            self::ROLE_MAHASISWA => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            self::ROLE_DOSEN => 'bg-amber-100 text-amber-700 border-amber-200',
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
    /**
     * Get all accessible menus for this user:
     * - Default menus assigned to their role via role_menu pivot or role_default attribute
     * - PLUS any custom extra menus assigned to this specific user via user_menu pivot table
     * - PLUS parent menus for any accessible child menu
     */
    protected static $accessibleMenusCache = [];

    public static function clearAccessibleMenusCache(): void
    {
        static::$accessibleMenusCache = [];
    }

    public function getAccessibleMenus()
    {
        if (app()->environment('testing')) {
            static::$accessibleMenusCache = [];
        }

        if (isset(static::$accessibleMenusCache[$this->id])) {
            return static::$accessibleMenusCache[$this->id];
        }

        // Super Admin gets all active menus
        if ($this->isSuperAdmin()) {
            $menus = Menu::where('is_active', true)->orderBy('sort_order')->get();
            static::$accessibleMenusCache[$this->id] = $menus;
            return $menus;
        }

        // For all other roles (Mahasiswa, Dosen, Koordinator):
        $roleMenuIds = \Illuminate\Support\Facades\DB::table('role_menu')
            ->where('role', $this->role)
            ->pluck('menu_id')
            ->toArray();

        $roleMenus = Menu::where('is_active', true)
            ->whereIn('id', $roleMenuIds)
            ->get();

        $customMenus = $this->menus()->where('is_active', true)->get();

        $allMenus = $roleMenus->concat($customMenus)->unique('id');

        // Automatically include parent menus for any child menu present
        $parentIds = $allMenus->pluck('parent_id')->filter()->unique()->toArray();
        if (!empty($parentIds)) {
            $parentMenus = Menu::where('is_active', true)->whereIn('id', $parentIds)->get();
            $allMenus = $allMenus->concat($parentMenus)->unique('id');
        }

        $result = $allMenus->sortBy('sort_order')->values();
        static::$accessibleMenusCache[$this->id] = $result;

        return $result;
    }

    /**
     * Check if user has access to a specific menu route
     */
    public function hasMenuAccess(string $routeOrName): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $accessibleRoutes = $this->getAccessibleMenus()->pluck('route')->filter()->toArray();
        return in_array($routeOrName, $accessibleRoutes, true);
    }
}
