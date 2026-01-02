<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DealershipIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'rating' => ['nullable', 'string', 'in:hot,warm,cold'],
            'sort' => ['nullable', 'string', 'in:name,city,state,status,rating'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.in' => 'The selected rating is invalid.',
            'status.in' => 'The selected status is invalid.',
            'sort.in' => 'The selected sort column is invalid.',
            'direction.in' => 'The sort direction must be asc or desc.',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
