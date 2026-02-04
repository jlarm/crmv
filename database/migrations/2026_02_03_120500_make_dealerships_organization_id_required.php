<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $dealerships = DB::table('dealerships')
            ->whereNull('organization_id')
            ->get(['id', 'user_id']);

        if ($dealerships->isNotEmpty()) {
            $userOrganizationIds = DB::table('users')
                ->whereIn('id', $dealerships->pluck('user_id')->all())
                ->pluck('current_organization_id', 'id');

            foreach ($dealerships as $dealership) {
                $organizationId = $userOrganizationIds[$dealership->user_id] ?? null;

                if (! $organizationId) {
                    $organizationId = 3;
                }

                DB::table('dealerships')
                    ->where('id', $dealership->id)
                    ->update(['organization_id' => $organizationId]);
            }
        }

        $remainingNulls = DB::table('dealerships')->whereNull('organization_id')->count();

        if ($remainingNulls > 0) {
            throw new RuntimeException('Cannot enforce non-null organization_id on dealerships: some rows could not be backfilled.');
        }

        Schema::table('dealerships', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dealerships', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->change();
        });
    }
};
