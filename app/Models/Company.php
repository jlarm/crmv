<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'user_id',
        'name',
        'address',
        'city',
        'state',
        'zip_code',
        'phone',
        'email',
        'current_solution_name',
        'current_solution_use',
        'notes',
        'status',
        'rating',
        'type',
        'in_development',
        'dev_status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function progresses(): HasMany
    {
        return $this->hasMany(Progress::class);
    }

    public function dealerEmails(): HasMany
    {
        return $this->hasMany(DealerEmail::class);
    }

    public function sentEmails(): HasMany
    {
        return $this->hasMany(SentEmail::class);
    }

    public function scopeSearch(Builder $query, ?string $search): void
    {
        if (! $search) {
            return;
        }

        $query->where(function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('city', 'like', "%{$search}%");
        });
    }

    public function scopeWithStatus(Builder $query, ?string $status): void
    {
        if (! $status) {
            return;
        }

        $query->where('status', $status);
    }

    public function scopeWithRating(Builder $query, ?string $rating): void
    {
        if (! $rating) {
            return;
        }

        $query->where('rating', $rating);
    }

    public function scopeWithType(Builder $query, ?string $type): void
    {
        if (! $type) {
            return;
        }

        $query->where('type', $type);
    }

    public function scopeForUser(Builder $query, ?User $user): void
    {
        if (! $user) {
            return;
        }

        $query->where(function (Builder $query) use ($user): void {
            $query->where('user_id', $user->id)
                ->orWhereHas('users', fn (Builder $relationQuery) => $relationQuery->where('users.id', $user->id));
        });
    }

    public function scopeSortBy(Builder $query, ?string $sort, ?string $direction = 'asc'): void
    {
        if (! $sort) {
            $query->orderBy('name', 'asc');

            return;
        }

        $allowedSorts = ['name', 'city', 'state', 'status', 'rating'];

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('name', 'asc');
        }
    }

    protected static function booted(): void
    {
        self::addGlobalScope('organization', function (Builder $query): void {
            $user = auth()->user();

            if (! $user?->current_organization_id) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->where('organization_id', $user->current_organization_id);
        });

        self::creating(function (self $company): void {
            if (! $company->organization_id) {
                $company->organization_id = auth()->user()?->current_organization_id;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'in_development' => 'boolean',
        ];
    }
}
