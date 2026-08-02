<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'role',
        'address',
        'cellphone',
        'password',
    ];

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
            'role' => UserRole::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isCashier(): bool
    {
        return $this->role === UserRole::Cashier;
    }

    public function isCanteenCashier(): bool
    {
        return $this->role === UserRole::CanteenCashier;
    }

    public function isInventory(): bool
    {
        return $this->role === UserRole::Inventory;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->role === UserRole::Admin,
            'user' => $this->role === UserRole::User,
            'pos' => $this->role === UserRole::Cashier,
            'pos-canteen' => $this->role === UserRole::CanteenCashier,
            default => false,
        };
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /** Purchases made by this member at the POS. */
    public function posSales(): HasMany
    {
        return $this->hasMany(PosSale::class, 'member_id');
    }

    /** Sales processed by this cashier at the POS. */
    public function posSalesProcessed(): HasMany
    {
        return $this->hasMany(PosSale::class, 'cashier_id');
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeMembers(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('role'), UserRole::User);
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeMatchingSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $inner) use ($term): void {
            $inner->where($inner->qualifyColumn('name'), 'like', "%{$term}%")
                ->orWhere($inner->qualifyColumn('email'), 'like', "%{$term}%")
                ->orWhere($inner->qualifyColumn('cellphone'), 'like', "%{$term}%");
        });
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeGroceryCashiers(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('role'), UserRole::Cashier);
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeCanteenCashiers(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('role'), UserRole::CanteenCashier);
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeOrderedByName(Builder $query): Builder
    {
        return $query->orderBy($query->qualifyColumn('name'));
    }
}
