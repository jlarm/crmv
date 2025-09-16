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
            'location' => "{$this->city}, {$this->state}",
            'phone' => $this->phone,
            'status' => $this->status,
            'rating' => $this->rating,
        ];
    }
}
