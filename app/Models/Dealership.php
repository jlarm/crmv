<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dealership extends Model
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

    protected function casts(): array
    {
        return [
            'in_development' => 'boolean',
        ];
    }

    public function scopeForDashboard($query)
    {
        return $query->select(['id', 'name', 'city', 'state', 'phone', 'status', 'rating']);
    }
}
