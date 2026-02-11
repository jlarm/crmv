<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Organization;
use App\Models\User;

test('guests cannot create companies', function () {
    $response = $this->post(route('company.store'), [
        'name' => 'Acme Motors',
    ]);

    $response->assertRedirect(route('login'));
});

test('authenticated users can create a company and are redirected to the company page', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'current_organization_id' => $organization->id,
    ]);
    $user->organizations()->attach($organization->id);

    $this->actingAs($user);

    $response = $this->post(route('company.store'), [
        'name' => 'Acme Motors',
    ]);

    $company = Company::query()->where('name', 'Acme Motors')->firstOrFail();

    $response->assertRedirect(route('company.show', $company));
    $response->assertSessionHas('success', 'Company created successfully.');

    $this->assertDatabaseHas('companies', [
        'id' => $company->id,
        'organization_id' => $organization->id,
        'user_id' => $user->id,
        'name' => 'Acme Motors',
        'status' => 'active',
        'rating' => 'warm',
        'type' => 'general',
    ]);
});

test('company name is required', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'current_organization_id' => $organization->id,
    ]);
    $user->organizations()->attach($organization->id);

    $this->actingAs($user);

    $response = $this->post(route('company.store'), [
        'name' => '',
    ]);

    $response->assertSessionHasErrors('name');
});

test('user must have a current organization to create a company', function () {
    $user = User::factory()->create([
        'current_organization_id' => null,
    ]);

    $this->actingAs($user);

    $response = $this->post(route('company.store'), [
        'name' => 'No Org Company',
    ]);

    $response->assertSessionHasErrors('name');
    $this->assertDatabaseMissing('companies', [
        'name' => 'No Org Company',
    ]);
});
