<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\TaskStoreRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Models\Contact;
use App\Models\Store;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class TaskController extends Controller
{
    public function index(): Response
    {
        $tasks = Task::query()
            ->with([
                'assignedTo:id,name',
                'store:id,name',
                'contact:id,name',
            ])
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->orderBy('name')
            ->get()
            ->map(fn (Task $task): array => $this->taskPayload($task))
            ->all();

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tasks/Create', [
            'task' => null,
            'formOptions' => $this->formOptions(),
        ]);
    }

    public function store(TaskStoreRequest $request): RedirectResponse
    {
        Task::query()->create($request->validated());

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task created successfully.');
    }

    public function edit(Task $task): Response
    {
        $task->load([
            'assignedTo:id,name',
            'store:id,name',
            'contact:id,name',
        ]);

        return Inertia::render('Tasks/Edit', [
            'task' => $this->taskPayload($task),
            'formOptions' => $this->formOptions(),
        ]);
    }

    public function update(TaskUpdateRequest $request, Task $task): RedirectResponse
    {
        $task->update($request->validated());

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task deleted successfully.');
    }

    /**
     * @return array{taskTypes: list<string>, priorities: list<string>, statuses: list<string>, users: array<int, array{id: int, name: string}>, stores: array<int, array{id: int, name: string}>, contacts: array<int, array{id: int, name: string}>}
     */
    private function formOptions(): array
    {
        return [
            'taskTypes' => ['Call', 'Email', 'Meeting'],
            'priorities' => ['Low', 'Medium', 'High'],
            'statuses' => ['Open', 'In Progress', 'Completed'],
            'users' => User::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                ])
                ->all(),
            'stores' => Store::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
                ->map(fn (Store $store): array => [
                    'id' => $store->id,
                    'name' => $store->name,
                ])
                ->all(),
            'contacts' => Contact::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
                ->map(fn (Contact $contact): array => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                ])
                ->all(),
        ];
    }

    /**
     * @return array{id: int, name: string, description: string|null, task_type: string, priority: string, status: string, due_date: string|null, assigned_to: int|null, store_id: int|null, contact_id: int|null, assignedTo: array{id: int, name: string}|null, store: array{id: int, name: string}|null, contact: array{id: int, name: string}|null}
     */
    private function taskPayload(Task $task): array
    {
        return [
            'id' => $task->id,
            'name' => $task->name,
            'description' => $task->description,
            'task_type' => $task->task_type,
            'priority' => $task->priority,
            'status' => $task->status,
            'due_date' => $task->due_date?->toDateString(),
            'assigned_to' => $task->assigned_to,
            'store_id' => $task->store_id,
            'contact_id' => $task->contact_id,
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
        ];
    }
}
