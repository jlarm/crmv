<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\TaskStoreRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Store;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $tasks = Task::query()
            ->with([
                'company:id,name',
                'assignedTo:id,name',
                'store:id,name',
                'contact:id,name',
            ])
            ->where('assigned_to', $request->user()->id)
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->orderBy('name')
            ->get()
            ->map(fn (Task $task): array => $this->taskPayload($task));

        $priorityOrder = ['High', 'Medium', 'Low'];

        $activeTasks = $tasks->where('status', '!=', 'Completed');
        $completedTasks = $tasks
            ->where('status', 'Completed')
            ->values()
            ->all();

        $tasksByPriority = collect($priorityOrder)
            ->mapWithKeys(fn (string $priority): array => [
                $priority => $activeTasks
                    ->where('priority', $priority)
                    ->values()
                    ->all(),
            ])
            ->all();

        $prioritySummary = collect($priorityOrder)
            ->map(fn (string $priority): array => [
                'label' => $priority,
                'count' => count($tasksByPriority[$priority]),
            ])
            ->all();

        return Inertia::render('Tasks/Index', [
            'tasksByPriority' => $tasksByPriority,
            'completedTasks' => $completedTasks,
            'prioritySummary' => $prioritySummary,
            'createFormOptions' => $this->indexFormOptions(),
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
            'company:id,name',
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
     * @return JsonResponse<array<int, array{id: int, name: string, city: string|null, state: string|null}>>
     */
    public function searchCompanies(Request $request): JsonResponse
    {
        $search = mb_trim((string) $request->string('search'));

        if (mb_strlen($search) < 2) {
            return response()->json([]);
        }

        $companies = Company::query()
            ->search($search)
            ->select('id', 'name', 'city', 'state')
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (Company $company): array => [
                'id' => $company->id,
                'name' => $company->name,
                'city' => $company->city,
                'state' => $company->state,
            ])
            ->all();

        return response()->json($companies);
    }

    /**
     * @return JsonResponse<array{id: int, name: string, stores: array<int, array{id: int, name: string}>}>
     */
    public function companyOptions(Company $company): JsonResponse
    {
        $company->load([
            'stores' => fn ($query) => $query
                ->select('id', 'company_id', 'name')
                ->orderBy('name'),
        ]);

        return response()->json([
            'id' => $company->id,
            'name' => $company->name,
            'stores' => $company->stores
                ->map(fn (Store $store): array => [
                    'id' => $store->id,
                    'name' => $store->name,
                ])
                ->all(),
        ]);
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
     * @return array{taskTypes: list<string>, priorities: list<string>, statuses: list<string>, users: array<int, array{id: int, name: string}>}
     */
    private function indexFormOptions(): array
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
        ];
    }

    /**
     * @return array{id: int, name: string, description: string|null, task_type: string, priority: string, status: string, due_date: string|null, company_id: int|null, assigned_to: int|null, store_id: int|null, contact_id: int|null, company: array{id: int, name: string}|null, assignedTo: array{id: int, name: string}|null, store: array{id: int, name: string}|null, contact: array{id: int, name: string}|null}
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
            'company_id' => $task->company_id,
            'assigned_to' => $task->assigned_to,
            'store_id' => $task->store_id,
            'contact_id' => $task->contact_id,
            'company' => $task->company
                ? [
                    'id' => $task->company->id,
                    'name' => $task->company->name,
                ]
                : null,
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
