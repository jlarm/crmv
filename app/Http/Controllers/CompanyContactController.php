<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CompanyContactController extends Controller
{
    public function store(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'position' => ['nullable', 'string', 'max:255'],
            'linkedin_link' => ['nullable', 'string', 'max:255'],
            'primary_contact' => ['nullable', 'boolean'],
        ]);

        $company->contacts()->create([
            ...$data,
            'primary_contact' => $request->boolean('primary_contact'),
        ]);

        return back()->with('success', 'Contact created successfully.');
    }

    public function update(Request $request, Company $company, Contact $contact): RedirectResponse
    {
        abort_unless($contact->company_id === $company->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'position' => ['nullable', 'string', 'max:255'],
            'linkedin_link' => ['nullable', 'string', 'max:255'],
            'primary_contact' => ['nullable', 'boolean'],
        ]);

        $contact->update([
            ...$data,
            'primary_contact' => $request->boolean('primary_contact'),
        ]);

        return back()->with('success', 'Contact updated successfully.');
    }
}
