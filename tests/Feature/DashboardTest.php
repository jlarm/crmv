<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Organization;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'current_organization_id' => $organization->id,
    ]);
    $user->organizations()->attach($organization->id);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
});

test('dashboard defaults to my companies and can filter to all companies', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'current_organization_id' => $organization->id,
    ]);
    $otherUser = User::factory()->create([
        'current_organization_id' => $organization->id,
    ]);

    $user->organizations()->attach($organization->id);
    $otherUser->organizations()->attach($organization->id);

    $ownedCompany = Company::query()->create([
        'organization_id' => $organization->id,
        'user_id' => $user->id,
        'name' => 'Owned Company',
        'status' => 'active',
        'rating' => 'warm',
        'type' => 'general',
    ]);

    Company::query()->create([
        'organization_id' => $organization->id,
        'user_id' => $otherUser->id,
        'name' => 'Other Company',
        'status' => 'active',
        'rating' => 'warm',
        'type' => 'general',
    ]);

    $sharedCompany = Company::query()->create([
        'organization_id' => $organization->id,
        'user_id' => $otherUser->id,
        'name' => 'Shared Company',
        'status' => 'active',
        'rating' => 'warm',
        'type' => 'general',
    ]);

    $sharedCompany->users()->attach($user->id);

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('filters.scope', 'mine')
            ->has('companies.data', 2)
            ->where('companies.data.0.id', $ownedCompany->id)
            ->where('companies.data.1.id', $sharedCompany->id));

    $this->get(route('dashboard', ['scope' => 'all']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('filters.scope', 'all')
            ->has('companies.data', 3));
});
