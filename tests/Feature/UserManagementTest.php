<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('guests cannot access user management page', function () {
    $response = $this->get(route('users.index'));

    $response->assertRedirect(route('login'));
});

test('non-admin users cannot access user management page', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user);

    $response = $this->get(route('users.index'));

    $response->assertForbidden();
});

test('admin users can access user management page', function () {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user);

    $response = $this->get(route('users.index'));

    $response->assertOk();
});

test('admin users can create users and assign organizations', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $organizationA = Organization::factory()->create();
    $organizationB = Organization::factory()->create();

    $this->actingAs($admin);

    $response = $this->post(route('users.store'), [
        'name' => 'Managed User',
        'email' => 'managed@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'organization_ids' => [$organizationA->id, $organizationB->id],
        'is_admin' => '1',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'User created successfully.');

    $managedUser = User::query()->where('email', 'managed@example.com')->firstOrFail();

    expect($managedUser->is_admin)->toBeTrue();
    expect($managedUser->current_organization_id)->toBe($organizationA->id);
    expect(Hash::check('Password123!', $managedUser->password))->toBeTrue();

    $this->assertDatabaseHas('organization_user', [
        'user_id' => $managedUser->id,
        'organization_id' => $organizationA->id,
    ]);
    $this->assertDatabaseHas('organization_user', [
        'user_id' => $managedUser->id,
        'organization_id' => $organizationB->id,
    ]);
});

test('admin users can update user organizations', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $organizationA = Organization::factory()->create();
    $organizationB = Organization::factory()->create();
    $organizationC = Organization::factory()->create();

    $managedUser = User::factory()->create([
        'current_organization_id' => $organizationA->id,
    ]);
    $managedUser->organizations()->attach([$organizationA->id, $organizationB->id]);

    $this->actingAs($admin);

    $response = $this->put(route('users.organizations.update', $managedUser), [
        'organization_ids' => [$organizationC->id],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'User organizations updated.');

    expect($managedUser->fresh()->current_organization_id)->toBe($organizationC->id);
    expect(
        $managedUser->fresh()->organizations()->pluck('organizations.id')->all()
    )->toBe([$organizationC->id]);
});

test('admin users can delete users', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $managedUser = User::factory()->create();

    $this->actingAs($admin);

    $response = $this->delete(route('users.destroy', $managedUser));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'User deleted successfully.');
    $this->assertSoftDeleted('users', ['id' => $managedUser->id]);
});

test('admin users cannot delete themselves from user management', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin);

    $response = $this->delete(route('users.destroy', $admin));

    $response->assertSessionHasErrors('delete');
    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

test('non-admin users cannot create update or delete users', function () {
    $nonAdmin = User::factory()->create(['is_admin' => false]);
    $organization = Organization::factory()->create();
    $targetUser = User::factory()->create();

    $this->actingAs($nonAdmin);

    $createResponse = $this->post(route('users.store'), [
        'name' => 'Blocked User',
        'email' => 'blocked@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'organization_ids' => [$organization->id],
    ]);
    $createResponse->assertForbidden();

    $updateResponse = $this->put(route('users.organizations.update', $targetUser), [
        'organization_ids' => [$organization->id],
    ]);
    $updateResponse->assertForbidden();

    $deleteResponse = $this->delete(route('users.destroy', $targetUser));
    $deleteResponse->assertForbidden();
});
