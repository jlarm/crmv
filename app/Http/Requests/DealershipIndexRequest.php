<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DealershipIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'sort' => ['nullable', 'string', 'in:name,city,state,status'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'The selected status is invalid.',
            'region.in' => 'The selected region is invalid.',
            'sort.in' => 'The selected sort column is invalid.',
            'direction.in' => 'The sort direction must be asc or desc.',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
