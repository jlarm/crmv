<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;

test('guests cannot switch organizations', function () {
    $organization = Organization::factory()->create();

    $response = $this->put(route('organization.switch', $organization));

    $response->assertRedirect(route('login'));
});

test('users can switch to an organization they belong to', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->organizations()->attach($organization);

    $this->actingAs($user);

    $response = $this->put(route('organization.switch', $organization));

    $response->assertRedirect();
    expect($user->fresh()->current_organization_id)->toBe($organization->id);
});

test('users cannot switch to an organization they do not belong to', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    $this->actingAs($user);

    $response = $this->put(route('organization.switch', $organization));

    $response->assertForbidden();
    expect($user->fresh()->current_organization_id)->toBeNull();
});

test('switching organizations updates the current organization', function () {
    $user = User::factory()->create();
    $organizationA = Organization::factory()->create();
    $organizationB = Organization::factory()->create();
    $user->organizations()->attach([$organizationA->id, $organizationB->id]);
    $user->update(['current_organization_id' => $organizationA->id]);

    $this->actingAs($user);

    $response = $this->put(route('organization.switch', $organizationB));

    $response->assertRedirect();
    expect($user->fresh()->current_organization_id)->toBe($organizationB->id);
});
