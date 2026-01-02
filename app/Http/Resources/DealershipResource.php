<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DealershipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'city' => $this->city,
            'state' => $this->state,
            'status' => $this->status,
            'statusLabel' => ucfirst($this->status),
            'statusVariant' => $this->status === 'active' ? 'default' : 'secondary',
            'rating' => $this->rating,
            'ratingLabel' => ucfirst($this->rating),
            'ratingVariant' => $this->rating === 'warm' ? 'outline' : 'destructive',
        ];
    }
}
