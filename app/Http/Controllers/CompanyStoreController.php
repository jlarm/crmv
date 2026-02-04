<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CompanyStoreController extends Controller
{
    public function store(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:50'],
            'current_solution_name' => ['nullable', 'string', 'max:255'],
            'current_solution_use' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $company->stores()->create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Store created successfully.');
    }

    public function update(Request $request, Company $company, Store $store): RedirectResponse
    {
        abort_unless($store->company_id === $company->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:50'],
            'current_solution_name' => ['nullable', 'string', 'max:255'],
            'current_solution_use' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $store->update($data);

        return back()->with('success', 'Store updated successfully.');
    }
}
