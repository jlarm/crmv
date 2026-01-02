<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DealershipShowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zipCode' => $this->zip_code,
            'phone' => $this->phone,
            'notes' => $this->notes,
            'currentSolutionName' => $this->current_solution_name,
            'currentSolutionUse' => $this->current_solution_use,
        ];
    }
}
