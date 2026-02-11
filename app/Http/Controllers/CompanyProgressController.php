<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Progress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

final class CompanyProgressController extends Controller
{
    public function store(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'details' => ['required', 'string', 'max:1000'],
            'contact_id' => [
                'nullable',
                'integer',
                Rule::exists('contacts', 'id')->where('company_id', $company->id),
            ],
            'date' => ['nullable', 'date'],
            'completed' => ['nullable', 'boolean'],
        ]);

        $company->progresses()->create([
            'user_id' => $request->user()->id,
            'contact_id' => $data['contact_id'] ?? null,
            'details' => $data['details'],
            'date' => $data['date'] ?? null,
            'completed_at' => ($data['completed'] ?? false) ? Carbon::now() : null,
        ]);

        return back()->with('success', 'Progress item created.');
    }

    public function update(Request $request, Company $company, Progress $progress): RedirectResponse
    {
        abort_unless($progress->company_id === $company->id, 404);

        $data = $request->validate([
            'details' => ['sometimes', 'string', 'max:1000'],
            'contact_id' => [
                'nullable',
                'integer',
                Rule::exists('contacts', 'id')->where('company_id', $company->id),
            ],
            'date' => ['nullable', 'date'],
            'completed' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('completed', $data)) {
            $progress->completed_at = $data['completed'] ? Carbon::now() : null;
        }

        $progress->fill(collect($data)->except('completed')->all());
        $progress->save();

        return back()->with('success', 'Progress item updated.');
    }

    public function destroy(Company $company, Progress $progress): RedirectResponse
    {
        abort_unless($progress->company_id === $company->id, 404);

        $progress->delete();

        return back()->with('success', 'Progress item deleted.');
    }
}
