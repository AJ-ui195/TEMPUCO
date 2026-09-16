<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = [
        'regular_loans',
        'quick_loans',
        'character_loans',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'decided_by')) {
                    $table->foreignId('decided_by')
                        ->nullable()
                        ->after('approved_at')
                        ->constrained('users')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn($tableName, 'decision_notes')) {
                    $table->text('decision_notes')->nullable()->after('decided_by');
                }

                if (! Schema::hasColumn($tableName, 'rejected_at')) {
                    $table->timestamp('rejected_at')->nullable()->after('decision_notes');
                }

                if (! Schema::hasColumn($tableName, 'email_verification_token')) {
                    $table->string('email_verification_token', 64)->nullable()->after('rejected_at');
                }

                if (! Schema::hasColumn($tableName, 'email_verified_at')) {
                    $table->timestamp('email_verified_at')->nullable()->after('email_verification_token');
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (Schema::hasColumn($tableName, 'decided_by')) {
                    $table->dropConstrainedForeignId('decided_by');
                }

                $drop = array_values(array_filter(
                    ['decision_notes', 'rejected_at', 'email_verification_token', 'email_verified_at'],
                    fn (string $column): bool => Schema::hasColumn($tableName, $column),
                ));

                if ($drop !== []) {
                    $table->dropColumn($drop);
                }
            });
        }
    }
};
