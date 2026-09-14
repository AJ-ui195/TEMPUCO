<?php

namespace App\Models;

use Database\Factories\MemberFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Member extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'created_by',
        'name',
        'email',
        'email_verified_at',
        'password',
        'date_of_birth',
        'sex',
        'civil_status',
        'address',
        'contact_number',
        'occupation',
        'employer_department',
        'is_retiree',
        'points',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'is_retiree' => 'boolean',
            'password' => 'hashed',
            'points' => 'integer',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'user';
    }

    public function isRetiree(): bool
    {
        return (bool) $this->is_retiree;
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'user_id');
    }

    public function posSales(): HasMany
    {
        return $this->hasMany(PosSale::class, 'member_id');
    }

    public function age(): ?int
    {
        if ($this->date_of_birth === null) {
            return null;
        }

        return $this->date_of_birth->age;
    }

    /**
     * @param  Builder<Member>  $query
     * @return Builder<Member>
     */
    public function scopeMatchingSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $inner) use ($term): void {
            $inner->where($inner->qualifyColumn('name'), 'like', "%{$term}%")
                ->orWhere($inner->qualifyColumn('email'), 'like', "%{$term}%")
                ->orWhere($inner->qualifyColumn('contact_number'), 'like', "%{$term}%");
        });
    }

    /**
     * @param  Builder<Member>  $query
     * @return Builder<Member>
     */
    public function scopeOrderedByName(Builder $query): Builder
    {
        return $query->orderBy($query->qualifyColumn('name'));
    }
}
