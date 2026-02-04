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
        if (! Schema::hasTable('company_user')) {
            Schema::create('company_user', function (Blueprint $table) {
                $table->foreignId('company_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
                $table->timestamps();
                $table->primary(['company_id', 'user_id']);
            });
        }

        if (Schema::hasTable('dealership_user') && DB::table('company_user')->count() === 0) {
            DB::table('company_user')->insertUsing(
                ['company_id', 'user_id'],
                DB::table('dealership_user')->select(['dealership_id', 'user_id'])
            );
        }

        $this->migrateContacts();
        $this->migrateStores();
        $this->migrateProgresses();
        $this->migrateDealerEmails();
        $this->migrateSentEmails();

        Schema::dropIfExists('dealership_user');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('dealership_user', function (Blueprint $table) {
            $table->foreignId('dealership_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
            $table->primary(['dealership_id', 'user_id']);
        });

        DB::table('dealership_user')->insertUsing(
            ['dealership_id', 'user_id'],
            DB::table('company_user')->select(['company_id', 'user_id'])
        );

        $this->rollbackSentEmails();
        $this->rollbackDealerEmails();
        $this->rollbackProgresses();
        $this->rollbackStores();
        $this->rollbackContacts();

        Schema::dropIfExists('company_user');
    }

    private function migrateContacts(): void
    {
        if (! Schema::hasColumn('contacts', 'company_id')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            });
        }

        DB::table('contacts')->update([
            'company_id' => DB::raw('COALESCE(dealership_id, 3)'),
        ]);

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dealership_id');
            $table->dropIndex(['dealership_id', 'primary_contact']);
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
            $table->index(['company_id', 'primary_contact']);
        });
    }

    private function migrateStores(): void
    {
        if (! Schema::hasColumn('stores', 'company_id')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            });
        }

        DB::table('stores')->update([
            'company_id' => DB::raw('COALESCE(dealership_id, 3)'),
        ]);

        Schema::table('stores', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dealership_id');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
        });
    }

    private function migrateProgresses(): void
    {
        if (! Schema::hasColumn('progresses', 'company_id')) {
            Schema::table('progresses', function (Blueprint $table) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
            });
        }

        DB::table('progresses')->update([
            'company_id' => DB::raw('COALESCE(dealership_id, 3)'),
        ]);

        Schema::table('progresses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dealership_id');
            $table->dropIndex(['dealership_id', 'date']);
        });

        Schema::table('progresses', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
            $table->index(['company_id', 'date']);
        });
    }

    private function migrateDealerEmails(): void
    {
        if (! Schema::hasColumn('dealer_emails', 'company_id')) {
            Schema::table('dealer_emails', function (Blueprint $table) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
            });
        }

        DB::table('dealer_emails')->update([
            'company_id' => DB::raw('COALESCE(dealership_id, 3)'),
        ]);

        Schema::table('dealer_emails', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dealership_id');
        });

        Schema::table('dealer_emails', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
        });
    }

    private function migrateSentEmails(): void
    {
        if (! Schema::hasColumn('sent_emails', 'company_id')) {
            Schema::table('sent_emails', function (Blueprint $table) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
            });
        }

        DB::table('sent_emails')->update([
            'company_id' => DB::raw('COALESCE(dealership_id, 3)'),
        ]);

        Schema::table('sent_emails', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dealership_id');
        });

        Schema::table('sent_emails', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
        });
    }

    private function rollbackContacts(): void
    {
        if (! Schema::hasColumn('contacts', 'dealership_id')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->foreignId('dealership_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            });
        }

        DB::table('contacts')->update([
            'dealership_id' => DB::raw('company_id'),
        ]);

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropIndex(['company_id', 'primary_contact']);
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignId('dealership_id')->nullable(false)->change();
            $table->index(['dealership_id', 'primary_contact']);
        });
    }

    private function rollbackStores(): void
    {
        if (! Schema::hasColumn('stores', 'dealership_id')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->foreignId('dealership_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            });
        }

        DB::table('stores')->update([
            'dealership_id' => DB::raw('company_id'),
        ]);

        Schema::table('stores', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->foreignId('dealership_id')->nullable(false)->change();
        });
    }

    private function rollbackProgresses(): void
    {
        if (! Schema::hasColumn('progresses', 'dealership_id')) {
            Schema::table('progresses', function (Blueprint $table) {
                $table->foreignId('dealership_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
            });
        }

        DB::table('progresses')->update([
            'dealership_id' => DB::raw('company_id'),
        ]);

        Schema::table('progresses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropIndex(['company_id', 'date']);
        });

        Schema::table('progresses', function (Blueprint $table) {
            $table->foreignId('dealership_id')->nullable(false)->change();
            $table->index(['dealership_id', 'date']);
        });
    }

    private function rollbackDealerEmails(): void
    {
        if (! Schema::hasColumn('dealer_emails', 'dealership_id')) {
            Schema::table('dealer_emails', function (Blueprint $table) {
                $table->foreignId('dealership_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
            });
        }

        DB::table('dealer_emails')->update([
            'dealership_id' => DB::raw('company_id'),
        ]);

        Schema::table('dealer_emails', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('dealer_emails', function (Blueprint $table) {
            $table->foreignId('dealership_id')->nullable(false)->change();
        });
    }

    private function rollbackSentEmails(): void
    {
        if (! Schema::hasColumn('sent_emails', 'dealership_id')) {
            Schema::table('sent_emails', function (Blueprint $table) {
                $table->foreignId('dealership_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
            });
        }

        DB::table('sent_emails')->update([
            'dealership_id' => DB::raw('company_id'),
        ]);

        Schema::table('sent_emails', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('sent_emails', function (Blueprint $table) {
            $table->foreignId('dealership_id')->nullable(false)->change();
        });
    }
};
