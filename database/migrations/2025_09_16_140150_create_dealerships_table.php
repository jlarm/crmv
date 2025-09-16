<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dealerships', function (Blueprint $table) {
            $table->id();
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
            $table->string('status');
            $table->string('rating');
            $table->string('type');
            $table->boolean('in_development');
            $table->string('dev_status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealerships');
    }
};
