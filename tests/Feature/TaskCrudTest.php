<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\Store;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('authenticated users can manage tasks', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'current_organization_id' => $organization->id,
    ]);
    $assignedUser = User::factory()->create([
        'current_organization_id' => $organization->id,
    ]);

    $user->organizations()->attach($organization->id);
    $assignedUser->organizations()->attach($organization->id);

    $company = Company::query()->create([
        'organization_id' => $organization->id,
        'user_id' => $user->id,
        'name' => 'Acme Motors',
        'status' => 'active',
        'rating' => 'warm',
        'type' => 'general',
    ]);

    $store = Store::query()->create([
        'user_id' => $user->id,
        'company_id' => $company->id,
        'name' => 'Downtown Store',
    ]);

    $contact = Contact::query()->create([
        'company_id' => $company->id,
        'name' => 'Taylor Contact',
    ]);

    $this->actingAs($user);

    $this->get(route('tasks.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tasks/Create')
            ->has('formOptions.users')
            ->has('formOptions.stores')
            ->has('formOptions.contacts'));

    $createResponse = $this->post(route('tasks.store'), [
        'name' => 'Follow up on proposal',
        'description' => 'Send pricing recap',
        'task_type' => 'Email',
        'priority' => 'High',
        'status' => 'Open',
        'due_date' => '2026-03-20',
        'assigned_to' => $assignedUser->id,
        'store_id' => $store->id,
        'contact_id' => $contact->id,
    ]);

    $task = Task::query()->firstOrFail();

    $createResponse->assertRedirect(route('tasks.index'));
    $createResponse->assertSessionHas('success', 'Task created successfully.');

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'name' => 'Follow up on proposal',
        'task_type' => 'Email',
        'priority' => 'High',
        'status' => 'Open',
        'assigned_to' => $assignedUser->id,
        'store_id' => $store->id,
        'contact_id' => $contact->id,
    ]);

    $this->get(route('tasks.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tasks/Index')
            ->has('tasks', 1)
            ->where('tasks.0.id', $task->id)
            ->where('tasks.0.assignedTo.name', $assignedUser->name)
            ->where('tasks.0.store.name', $store->name)
            ->where('tasks.0.contact.name', $contact->name));

    $this->get(route('tasks.edit', $task))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tasks/Edit')
            ->where('task.id', $task->id)
            ->where('task.assigned_to', $assignedUser->id));

    $updateResponse = $this->put(route('tasks.update', $task), [
        'name' => 'Schedule onsite visit',
        'description' => null,
        'task_type' => 'Meeting',
        'priority' => 'Medium',
        'status' => 'In Progress',
        'due_date' => '2026-03-25',
        'assigned_to' => null,
        'store_id' => null,
        'contact_id' => $contact->id,
    ]);

    $updateResponse->assertRedirect(route('tasks.index'));
    $updateResponse->assertSessionHas('success', 'Task updated successfully.');

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'name' => 'Schedule onsite visit',
        'task_type' => 'Meeting',
        'priority' => 'Medium',
        'status' => 'In Progress',
        'assigned_to' => null,
        'store_id' => null,
        'contact_id' => $contact->id,
    ]);

    $deleteResponse = $this->delete(route('tasks.destroy', $task));

    $deleteResponse->assertRedirect(route('tasks.index'));
    $deleteResponse->assertSessionHas('success', 'Task deleted successfully.');

    $this->assertDatabaseMissing('tasks', [
        'id' => $task->id,
    ]);
});
