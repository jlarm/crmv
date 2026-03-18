<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class CompanyTaskController extends Controller
{
    public function store(Request $request, Company $company): RedirectResponse
    {
        $data = $this->validateTask($request, $company);

        $company->tasks()->create($data);

        return back()->with('success', 'Task created successfully.');
    }

    public function update(Request $request, Company $company, Task $task): RedirectResponse
    {
        abort_unless($task->company_id === $company->id, 404);

        $task->update($this->validateTask($request, $company));

        return back()->with('success', 'Task updated successfully.');
    }

    public function destroy(Company $company, Task $task): RedirectResponse
    {
        abort_unless($task->company_id === $company->id, 404);

        $task->delete();

        return back()->with('success', 'Task deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTask(Request $request, Company $company): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'task_type' => ['required', 'string', 'in:Call,Email,Meeting'],
            'priority' => ['required', 'string', 'in:Low,Medium,High'],
            'status' => ['required', 'string', 'in:Open,In Progress,Completed'],
            'due_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'store_id' => [
                'nullable',
                'integer',
                Rule::exists('stores', 'id')->where('company_id', $company->id),
            ],
            'contact_id' => [
                'nullable',
                'integer',
                Rule::exists('contacts', 'id')->where('company_id', $company->id),
            ],
        ]);
    }
}
