<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class TaskStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'task_type' => ['required', 'string', 'in:Call,Email,Meeting'],
            'priority' => ['required', 'string', 'in:Low,Medium,High'],
            'status' => ['required', 'string', 'in:Open,In Progress,Completed'],
            'due_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'task_type.in' => 'The selected task type is invalid.',
            'priority.in' => 'The selected priority is invalid.',
            'status.in' => 'The selected status is invalid.',
        ];
    }
}
