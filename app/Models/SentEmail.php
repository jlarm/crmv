<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SentEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_id',
        'recipient',
        'message_id',
        'subject',
        'tracking_data',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function emailTrackingEvents(): HasMany
    {
        return $this->hasMany(EmailTrackingEvent::class);
    }

    protected function casts(): array
    {
        return [
            'tracking_data' => 'array',
        ];
    }
}
