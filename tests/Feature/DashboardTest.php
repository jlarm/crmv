<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
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

test('dashboard shows upcoming and past due task cards for visible companies', function () {
    CarbonImmutable::setTestNow('2026-03-18 09:00:00');

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

    $otherCompany = Company::query()->create([
        'organization_id' => $organization->id,
        'user_id' => $otherUser->id,
        'name' => 'Other Company',
        'status' => 'active',
        'rating' => 'warm',
        'type' => 'general',
    ]);

    $upcomingTask = Task::query()->create([
        'company_id' => $ownedCompany->id,
        'assigned_to' => $user->id,
        'name' => 'Call before renewal window',
        'task_type' => 'Call',
        'priority' => 'High',
        'status' => 'Open',
        'due_date' => '2026-03-22',
    ]);

    $pastDueTask = Task::query()->create([
        'company_id' => $ownedCompany->id,
        'assigned_to' => $user->id,
        'name' => 'Send overdue proposal',
        'task_type' => 'Email',
        'priority' => 'Medium',
        'status' => 'In Progress',
        'due_date' => '2026-03-15',
    ]);

    Task::query()->create([
        'company_id' => $ownedCompany->id,
        'assigned_to' => $user->id,
        'name' => 'Already completed',
        'task_type' => 'Meeting',
        'priority' => 'Low',
        'status' => 'Completed',
        'due_date' => '2026-03-20',
    ]);

    Task::query()->create([
        'company_id' => $otherCompany->id,
        'assigned_to' => $otherUser->id,
        'name' => 'Other company task',
        'task_type' => 'Call',
        'priority' => 'Low',
        'status' => 'Open',
        'due_date' => '2026-03-20',
    ]);

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('upcomingTasks', 1)
            ->has('pastDueTasks', 1)
            ->where('upcomingTasks.0.id', $upcomingTask->id)
            ->where('upcomingTasks.0.company.id', $ownedCompany->id)
            ->where('pastDueTasks.0.id', $pastDueTask->id)
            ->where('pastDueTasks.0.company.id', $ownedCompany->id));

    $this->get(route('dashboard', ['scope' => 'all']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('upcomingTasks', 2)
            ->has('pastDueTasks', 1));

    CarbonImmutable::setTestNow();
});
