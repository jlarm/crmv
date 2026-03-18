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
            'status' => $this->status,
            'rating' => $this->rating,
            'stores' => $this->whenLoaded('stores', function () {
                return $this->stores->map(fn ($store) => [
                    'id' => $store->id,
                    'name' => $store->name,
                    'address' => $store->address,
                    'city' => $store->city,
                    'state' => $store->state,
                    'zipCode' => $store->zip_code,
                    'phone' => $store->phone,
                    'currentSolutionName' => $store->current_solution_name,
                    'currentSolutionUse' => $store->current_solution_use,
                    'notes' => $store->notes,
                ]);
            }),
            'contacts' => $this->whenLoaded('contacts', function () {
                return $this->contacts->map(fn ($contact) => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'position' => $contact->position,
                    'linkedinLink' => $contact->linkedin_link,
                    'primaryContact' => (bool) $contact->primary_contact,
                ]);
            }),
            'progresses' => $this->whenLoaded('progresses', function () {
                return $this->progresses->map(fn ($progress) => [
                    'id' => $progress->id,
                    'details' => $progress->details,
                    'date' => optional($progress->date)->toDateString(),
                    'completedAt' => $progress->completed_at?->toDateTimeString(),
                    'createdAt' => $progress->created_at?->toDateTimeString(),
                    'contact' => $progress->contact
                        ? [
                            'id' => $progress->contact->id,
                            'name' => $progress->contact->name,
                        ]
                        : null,
                ]);
            }),
            'tasks' => $this->whenLoaded('tasks', function () {
                return $this->tasks->map(fn ($task) => [
                    'id' => $task->id,
                    'name' => $task->name,
                    'description' => $task->description,
                    'taskType' => $task->task_type,
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'dueDate' => $task->due_date?->toDateString(),
                    'assignedTo' => $task->assignedTo
                        ? [
                            'id' => $task->assignedTo->id,
                            'name' => $task->assignedTo->name,
                        ]
                        : null,
                    'store' => $task->store
                        ? [
                            'id' => $task->store->id,
                            'name' => $task->store->name,
                        ]
                        : null,
                    'contact' => $task->contact
                        ? [
                            'id' => $task->contact->id,
                            'name' => $task->contact->name,
                        ]
                        : null,
                ]);
            }),
            'users' => UserResource::collection($this->whenLoaded('users')),
        ];
    }
}
