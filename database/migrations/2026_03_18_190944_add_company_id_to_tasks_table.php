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
        if (! Schema::hasTable('tasks') || Schema::hasColumn('tasks', 'company_id')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('due_date')->constrained()->nullOnDelete();
        });

        DB::table('tasks')
            ->whereNull('company_id')
            ->whereNotNull('store_id')
            ->update([
                'company_id' => DB::raw('(select company_id from stores where stores.id = tasks.store_id limit 1)'),
            ]);

        DB::table('tasks')
            ->whereNull('company_id')
            ->whereNotNull('contact_id')
            ->update([
                'company_id' => DB::raw('(select company_id from contacts where contacts.id = tasks.contact_id limit 1)'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('tasks') || ! Schema::hasColumn('tasks', 'company_id')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
