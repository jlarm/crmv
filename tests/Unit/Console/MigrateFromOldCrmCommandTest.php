<?php

declare(strict_types=1);

use App\Console\Commands\MigrateFromOldCrm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('only truncates tables that exist in the current schema', function () {
    $command = new MigrateFromOldCrm;
    $method = new ReflectionMethod(MigrateFromOldCrm::class, 'tablesToTruncate');
    $method->setAccessible(true);

    /** @var array<int, string> $tables */
    $tables = $method->invoke($command);

    expect($tables)
        ->toContain('company_user')
        ->toContain('companies')
        ->not->toContain('dealership_user');
});
