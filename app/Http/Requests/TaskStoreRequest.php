<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $companyId = $this->input('company_id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'task_type' => ['required', 'string', 'in:Call,Email,Meeting'],
            'priority' => ['required', 'string', 'in:Low,Medium,High'],
            'status' => ['required', 'string', 'in:Open,In Progress,Completed'],
            'due_date' => ['nullable', 'date'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'store_id' => [
                'nullable',
                'integer',
                $companyId
                    ? Rule::exists('stores', 'id')->where(
                        fn ($query) => $query->where('company_id', $companyId),
                    )
                    : 'exists:stores,id',
            ],
            'contact_id' => [
                'nullable',
                'integer',
                $companyId
                    ? Rule::exists('contacts', 'id')->where(
                        fn ($query) => $query->where('company_id', $companyId),
                    )
                    : 'exists:contacts,id',
            ],
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
