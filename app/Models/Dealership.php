<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Dealership extends Model
{
    protected $fillable = [
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

    public function scopeForDashboard($query)
    {
        return $query->select(['id', 'name', 'city', 'state', 'phone', 'status', 'rating']);
    }

    protected function casts(): array
    {
        return [
            'in_development' => 'boolean',
        ];
    }
}
