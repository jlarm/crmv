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

    $this->get(route('tasks.company-search', ['search' => 'Acme']))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $company->id)
        ->assertJsonPath('0.name', 'Acme Motors');

    $this->get(route('tasks.company-options', $company))
        ->assertOk()
        ->assertJsonPath('id', $company->id)
        ->assertJsonPath('stores.0.id', $store->id)
        ->assertJsonPath('stores.0.name', $store->name);

    $createResponse = $this->post(route('tasks.store'), [
        'name' => 'Follow up on proposal',
        'description' => 'Send pricing recap',
        'task_type' => 'Email',
        'priority' => 'High',
        'status' => 'Open',
        'due_date' => '2026-03-20',
        'company_id' => $company->id,
        'assigned_to' => $user->id,
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
        'company_id' => $company->id,
        'assigned_to' => $user->id,
        'store_id' => $store->id,
        'contact_id' => $contact->id,
    ]);

    Task::query()->create([
        'name' => 'Someone else task',
        'task_type' => 'Call',
        'priority' => 'Low',
        'status' => 'Open',
        'due_date' => '2026-03-21',
        'assigned_to' => $assignedUser->id,
    ]);

    $this->get(route('tasks.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tasks/Index')
            ->has('prioritySummary', 3)
            ->has('createFormOptions.users')
            ->where('prioritySummary.0.label', 'High')
            ->where('prioritySummary.0.count', 1)
            ->where('tasksByPriority.High.0.id', $task->id)
            ->where('tasksByPriority.High.0.company_id', $company->id)
            ->where('tasksByPriority.High.0.assignedTo.name', $user->name)
            ->where('tasksByPriority.High.0.store.name', $store->name)
            ->where('tasksByPriority.High.0.contact.name', $contact->name)
            ->where('completedTasks', [])
            ->where('tasksByPriority.Medium', [])
            ->where('tasksByPriority.Low', []));

    $this->get(route('tasks.edit', $task))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tasks/Edit')
            ->where('task.id', $task->id)
            ->where('task.assigned_to', $user->id));

    $updateResponse = $this->put(route('tasks.update', $task), [
        'name' => 'Schedule onsite visit',
        'description' => null,
        'task_type' => 'Meeting',
        'priority' => 'Medium',
        'status' => 'In Progress',
        'due_date' => '2026-03-25',
        'company_id' => $company->id,
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

    $completeResponse = $this->put(route('tasks.update', $task), [
        'name' => 'Schedule onsite visit',
        'description' => null,
        'task_type' => 'Meeting',
        'priority' => 'Medium',
        'status' => 'Completed',
        'due_date' => '2026-03-25',
        'company_id' => $company->id,
        'assigned_to' => $user->id,
        'store_id' => null,
        'contact_id' => $contact->id,
    ]);

    $completeResponse->assertRedirect(route('tasks.index'));
    $completeResponse->assertSessionHas('success', 'Task updated successfully.');

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'status' => 'Completed',
        'assigned_to' => $user->id,
    ]);

    $this->get(route('tasks.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tasks/Index')
            ->where('tasksByPriority.High', [])
            ->where('tasksByPriority.Medium', [])
            ->where('tasksByPriority.Low', [])
            ->has('completedTasks', 1)
            ->where('completedTasks.0.id', $task->id)
            ->where('completedTasks.0.status', 'Completed'));

    $deleteResponse = $this->delete(route('tasks.destroy', $task));

    $deleteResponse->assertRedirect(route('tasks.index'));
    $deleteResponse->assertSessionHas('success', 'Task deleted successfully.');

    $this->assertDatabaseMissing('tasks', [
        'id' => $task->id,
    ]);
});
