<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\Progress;
use App\Models\Store;
use App\Models\Task;
use App\Models\User;

function createCompanyContext(bool $isAdmin = false): array
{
    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'is_admin' => $isAdmin,
        'current_organization_id' => $organization->id,
    ]);
    $user->organizations()->attach($organization->id);

    $company = Company::query()->create([
        'organization_id' => $organization->id,
        'user_id' => $user->id,
        'name' => 'Test Company',
        'status' => 'active',
        'rating' => 'warm',
        'type' => 'general',
    ]);

    return [$user, $company, $organization];
}

test('can create update and delete contacts for a company', function () {
    [$user, $company] = createCompanyContext();
    $this->actingAs($user);

    $create = $this->post(route('companies.contacts.store', $company), [
        'name' => 'Jane Contact',
        'email' => 'jane@example.com',
        'primary_contact' => '1',
    ]);
    $create->assertRedirect();
    $create->assertSessionHas('success', 'Contact created successfully.');

    $contact = Contact::query()->where('company_id', $company->id)->firstOrFail();
    expect($contact->primary_contact)->toBeTrue();

    $update = $this->put(route('companies.contacts.update', [$company, $contact]), [
        'name' => 'Jane Updated',
        'email' => 'jane.updated@example.com',
        'primary_contact' => '0',
    ]);
    $update->assertRedirect();
    $update->assertSessionHas('success', 'Contact updated successfully.');
    expect($contact->fresh()->name)->toBe('Jane Updated');
    expect($contact->fresh()->primary_contact)->toBeFalse();

    $delete = $this->delete(route('companies.contacts.destroy', [$company, $contact]));
    $delete->assertRedirect();
    $delete->assertSessionHas('success', 'Contact deleted successfully.');
    $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
});

test('cannot update or delete a contact from another company', function () {
    [$user, $company] = createCompanyContext();
    [, $otherCompany] = createCompanyContext();
    $this->actingAs($user);

    $contact = Contact::query()->create([
        'company_id' => $otherCompany->id,
        'name' => 'Other',
    ]);

    $this->put(route('companies.contacts.update', [$company, $contact]), [
        'name' => 'Nope',
    ])->assertNotFound();

    $this->delete(route('companies.contacts.destroy', [$company, $contact]))
        ->assertNotFound();
});

test('can create update and delete stores for a company', function () {
    [$user, $company] = createCompanyContext();
    $this->actingAs($user);

    $create = $this->post(route('companies.stores.store', $company), [
        'name' => 'Store A',
        'city' => 'Austin',
    ]);
    $create->assertRedirect();
    $create->assertSessionHas('success', 'Store created successfully.');

    $store = Store::query()->where('company_id', $company->id)->firstOrFail();
    expect($store->user_id)->toBe($user->id);

    $update = $this->put(route('companies.stores.update', [$company, $store]), [
        'name' => 'Store B',
        'city' => 'Dallas',
    ]);
    $update->assertRedirect();
    $update->assertSessionHas('success', 'Store updated successfully.');
    expect($store->fresh()->name)->toBe('Store B');

    $delete = $this->delete(route('companies.stores.destroy', [$company, $store]));
    $delete->assertRedirect();
    $delete->assertSessionHas('success', 'Store deleted successfully.');
    $this->assertDatabaseMissing('stores', ['id' => $store->id]);
});

test('cannot update or delete a store from another company', function () {
    [$user, $company] = createCompanyContext();
    [, $otherCompany] = createCompanyContext();
    $this->actingAs($user);

    $store = Store::query()->create([
        'company_id' => $otherCompany->id,
        'user_id' => $user->id,
        'name' => 'Other Store',
    ]);

    $this->put(route('companies.stores.update', [$company, $store]), [
        'name' => 'Nope',
    ])->assertNotFound();

    $this->delete(route('companies.stores.destroy', [$company, $store]))
        ->assertNotFound();
});

test('can create update and delete progress items for a company', function () {
    [$user, $company] = createCompanyContext();
    $this->actingAs($user);

    $contact = Contact::query()->create([
        'company_id' => $company->id,
        'name' => 'Contact 1',
    ]);

    $create = $this->post(route('companies.progresses.store', $company), [
        'details' => 'Initial progress',
        'contact_id' => $contact->id,
        'completed' => '1',
    ]);
    $create->assertRedirect();
    $create->assertSessionHas('success', 'Progress item created.');

    $progress = Progress::query()->where('company_id', $company->id)->firstOrFail();
    expect($progress->completed_at)->not->toBeNull();

    $update = $this->put(route('companies.progresses.update', [$company, $progress]), [
        'details' => 'Updated progress',
        'completed' => false,
    ]);
    $update->assertRedirect();
    $update->assertSessionHas('success', 'Progress item updated.');
    expect($progress->fresh()->details)->toBe('Updated progress');
    expect($progress->fresh()->completed_at)->toBeNull();

    $delete = $this->delete(route('companies.progresses.destroy', [$company, $progress]));
    $delete->assertRedirect();
    $delete->assertSessionHas('success', 'Progress item deleted.');
    $this->assertDatabaseMissing('progresses', ['id' => $progress->id]);
});

test('progress contact must belong to the same company', function () {
    [$user, $company] = createCompanyContext();
    [, $otherCompany] = createCompanyContext();
    $this->actingAs($user);

    $otherContact = Contact::query()->create([
        'company_id' => $otherCompany->id,
        'name' => 'Other Contact',
    ]);

    $this->post(route('companies.progresses.store', $company), [
        'details' => 'Invalid contact',
        'contact_id' => $otherContact->id,
    ])->assertSessionHasErrors('contact_id');
});

test('company users can be synced', function () {
    [$admin, $company] = createCompanyContext(true);
    $this->actingAs($admin);

    $organization = Organization::query()->findOrFail($company->organization_id);
    $userA = User::factory()->create(['current_organization_id' => $organization->id]);
    $userB = User::factory()->create(['current_organization_id' => $organization->id]);

    $userA->organizations()->attach($organization->id);
    $userB->organizations()->attach($organization->id);

    $response = $this->put(route('companies.users.update', $company), [
        'user_ids' => [$userA->id, $userB->id],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Company users updated successfully.');
    expect($company->fresh()->users()->pluck('users.id')->sort()->values()->all())
        ->toBe([$userA->id, $userB->id]);
});

test('can create update and delete tasks for a company', function () {
    [$user, $company] = createCompanyContext();
    $this->actingAs($user);

    $store = Store::query()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'name' => 'Store A',
    ]);

    $contact = Contact::query()->create([
        'company_id' => $company->id,
        'name' => 'Contact A',
    ]);

    $create = $this->post(route('companies.tasks.store', $company), [
        'name' => 'Initial task',
        'description' => 'Call the store manager',
        'task_type' => 'Call',
        'priority' => 'High',
        'status' => 'Open',
        'due_date' => '2026-03-25',
        'assigned_to' => $user->id,
        'store_id' => $store->id,
        'contact_id' => $contact->id,
    ]);
    $create->assertRedirect();
    $create->assertSessionHas('success', 'Task created successfully.');

    $task = Task::query()->where('company_id', $company->id)->firstOrFail();
    expect($task->store_id)->toBe($store->id);
    expect($task->contact_id)->toBe($contact->id);

    $update = $this->put(route('companies.tasks.update', [$company, $task]), [
        'name' => 'Updated task',
        'description' => null,
        'task_type' => 'Meeting',
        'priority' => 'Medium',
        'status' => 'In Progress',
        'due_date' => '2026-03-26',
        'assigned_to' => null,
        'store_id' => null,
        'contact_id' => $contact->id,
    ]);
    $update->assertRedirect();
    $update->assertSessionHas('success', 'Task updated successfully.');
    expect($task->fresh()->name)->toBe('Updated task');
    expect($task->fresh()->assigned_to)->toBeNull();

    $delete = $this->delete(route('companies.tasks.destroy', [$company, $task]));
    $delete->assertRedirect();
    $delete->assertSessionHas('success', 'Task deleted successfully.');
    $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
});

test('task store and contact must belong to the same company', function () {
    [$user, $company] = createCompanyContext();
    [, $otherCompany] = createCompanyContext();
    $this->actingAs($user);

    $otherStore = Store::query()->create([
        'company_id' => $otherCompany->id,
        'user_id' => $user->id,
        'name' => 'Other Store',
    ]);

    $otherContact = Contact::query()->create([
        'company_id' => $otherCompany->id,
        'name' => 'Other Contact',
    ]);

    $this->post(route('companies.tasks.store', $company), [
        'name' => 'Invalid task',
        'task_type' => 'Call',
        'priority' => 'Low',
        'status' => 'Open',
        'store_id' => $otherStore->id,
        'contact_id' => $otherContact->id,
    ])->assertSessionHasErrors(['store_id', 'contact_id']);
});
