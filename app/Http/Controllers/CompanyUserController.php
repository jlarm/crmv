<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CompanyUserController extends Controller
{
    public function update(Request $request, Company $company): RedirectResponse
    {
        $userIds = $request->input('user_ids', []);

        $validated = $request->validate([
            'user_ids' => ['array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $company->users()->sync($validated['user_ids'] ?? []);

        return back()->with('success', 'Company users updated successfully.');
    }
}
