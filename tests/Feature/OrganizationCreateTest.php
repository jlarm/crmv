<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;

test('guests cannot create organizations', function () {
    $response = $this->post(route('organization.store'), [
        'name' => 'Test Organization',
    ]);

    $response->assertRedirect(route('login'));
});

test('non-admin users cannot create organizations', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user);

    $response = $this->post(route('organization.store'), [
        'name' => 'Test Organization',
    ]);

    $response->assertForbidden();
});

test('admin users can create organizations', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user);

    $response = $this->post(route('organization.store'), [
        'name' => 'Test Organization',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Organization created successfully.');
    $this->assertDatabaseHas('organizations', ['name' => 'Test Organization']);
});

test('creator is attached to the organization', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user);

    $this->post(route('organization.store'), [
        'name' => 'Test Organization',
    ]);

    $organization = Organization::where('name', 'Test Organization')->first();
    expect($user->organizations)->toHaveCount(1);
    expect($user->organizations->first()->id)->toBe($organization->id);
});

test('name is required', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user);

    $response = $this->post(route('organization.store'), [
        'name' => '',
    ]);

    $response->assertSessionHasErrors('name');
});

test('name must be unique', function () {
    $user = User::factory()->create(['is_admin' => true]);
    Organization::factory()->create(['name' => 'Existing Organization']);

    $this->actingAs($user);

    $response = $this->post(route('organization.store'), [
        'name' => 'Existing Organization',
    ]);

    $response->assertSessionHasErrors('name');
});

test('name must not exceed 255 characters', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user);

    $response = $this->post(route('organization.store'), [
        'name' => str_repeat('a', 256),
    ]);

    $response->assertSessionHasErrors('name');
});
