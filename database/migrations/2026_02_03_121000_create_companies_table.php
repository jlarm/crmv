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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('user_id')->constrained()->onUpdate('cascade')->onDelete('restrict');
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('current_solution_name')->nullable();
            $table->string('current_solution_use')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->index();
            $table->string('rating')->index();
            $table->string('type')->index();
            $table->boolean('in_development')->default(false)->index();
            $table->string('dev_status')->nullable();
            $table->timestamps();
        });

        DB::table('companies')->insertUsing(
            [
                'id',
                'organization_id',
                'user_id',
                'name',
                'address',
                'city',
                'state',
                'zip_code',
                'phone',
                'email',
                'current_solution_name',
                'current_solution_use',
                'notes',
                'status',
                'rating',
                'type',
                'in_development',
                'dev_status',
                'created_at',
                'updated_at',
            ],
            DB::table('dealerships')->select([
                'id',
                'organization_id',
                'user_id',
                'name',
                'address',
                'city',
                'state',
                'zip_code',
                'phone',
                'email',
                'current_solution_name',
                'current_solution_use',
                'notes',
                'status',
                'rating',
                'type',
                'in_development',
                'dev_status',
                'created_at',
                'updated_at',
            ])
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
