<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('password');
            }

            if (! Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false)->after('is_active');
            }

            if (! Schema::hasColumn('users', 'app_authentication_secret')) {
                $table->text('app_authentication_secret')->nullable()->after('must_change_password');
            }

            if (! Schema::hasColumn('users', 'app_authentication_recovery_codes')) {
                $table->text('app_authentication_recovery_codes')->nullable()->after('app_authentication_secret');
            }
        });

        if (Schema::hasTable('members')) {
            if (Schema::hasColumn('members', 'user_id')) {
                Schema::table('members', function (Blueprint $table) {
                    $table->dropForeign(['user_id']);
                });

                $hasUniqueUserIdIndex = collect(Schema::getIndexes('members'))
                    ->contains(fn (array $index): bool => ($index['unique'] ?? false)
                        && ! ($index['primary'] ?? false)
                        && in_array('user_id', $index['columns'], true));

                if ($hasUniqueUserIdIndex) {
                    Schema::table('members', function (Blueprint $table) {
                        $table->dropUnique(['user_id']);
                    });
                }

                Schema::table('members', function (Blueprint $table) {
                    $table->dropColumn('user_id');
                });
            }

            Schema::table('members', function (Blueprint $table) {
                if (! Schema::hasColumn('members', 'email_verified_at')) {
                    $table->timestamp('email_verified_at')->nullable()->after('email');
                }

                if (! Schema::hasColumn('members', 'remember_token')) {
                    $table->rememberToken();
                }

                if (! Schema::hasColumn('members', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('points');
                }

                if (! Schema::hasColumn('members', 'must_change_password')) {
                    $table->boolean('must_change_password')->default(false)->after('is_active');
                }
            });
        }
        if (Schema::hasTable('loans')) {
            $loanUserIdForeign = collect(Schema::getForeignKeys('loans'))
                ->first(fn (array $foreignKey): bool => in_array('user_id', $foreignKey['columns'], true));

            if ($loanUserIdForeign !== null && ($loanUserIdForeign['foreign_table'] ?? null) !== 'members') {
                Schema::table('loans', function (Blueprint $table) {
                    $table->dropForeign(['user_id']);
                });
            }

            if (($loanUserIdForeign['foreign_table'] ?? null) !== 'members') {
                $this->remapLoanUserIdsToMembers();

                Schema::table('loans', function (Blueprint $table) {
                    $table->foreign('user_id')->references('id')->on('members')->cascadeOnDelete();
                });
            }

            Schema::table('loans', function (Blueprint $table) {
                if (! Schema::hasColumn('loans', 'decided_by')) {
                    $table->foreignId('decided_by')
                        ->nullable()
                        ->after('approved_at')
                        ->constrained('users')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn('loans', 'decision_notes')) {
                    $table->text('decision_notes')->nullable()->after('decided_by');
                }

                if (! Schema::hasColumn('loans', 'rejected_at')) {
                    $table->timestamp('rejected_at')->nullable()->after('decision_notes');
                }
            });
        }

        if (Schema::hasTable('loan_payments')) {
            Schema::table('loan_payments', function (Blueprint $table) {
                if (! Schema::hasColumn('loan_payments', 'received_by')) {
                    $table->foreignId('received_by')
                        ->nullable()
                        ->after('received_at')
                        ->constrained('users')
                        ->nullOnDelete();
                }
            });
        }
        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->nullableMorphs('actor');
                $table->string('action', 64);
                $table->nullableMorphs('subject');
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 512)->nullable();
                $table->json('properties')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    private function remapLoanUserIdsToMembers(): void
    {
        $memberIdByEmail = DB::table('members')->pluck('id', 'email');
        $legacyEmailByUserId = DB::table('users')
            ->where('role', 'user')
            ->pluck('email', 'id');

        foreach (DB::table('loans')->get(['id', 'user_id']) as $loan) {
            $legacyEmail = $legacyEmailByUserId[$loan->user_id] ?? null;
            $memberId = $legacyEmail !== null ? ($memberIdByEmail[$legacyEmail] ?? null) : null;

            if ($memberId === null || (int) $memberId === (int) $loan->user_id) {
                continue;
            }

            DB::table('loans')->where('id', $loan->id)->update([
                'user_id' => $memberId,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');

        if (Schema::hasTable('loan_payments')) {
            Schema::table('loan_payments', function (Blueprint $table) {
                if (Schema::hasColumn('loan_payments', 'received_by')) {
                    $table->dropConstrainedForeignId('received_by');
                }
            });
        }

        if (Schema::hasTable('loans')) {
            Schema::table('loans', function (Blueprint $table) {
                if (Schema::hasColumn('loans', 'decided_by')) {
                    $table->dropConstrainedForeignId('decided_by');
                }

                $drop = array_values(array_filter(
                    ['decision_notes', 'rejected_at'],
                    fn (string $column): bool => Schema::hasColumn('loans', $column),
                ));

                if ($drop !== []) {
                    $table->dropColumn($drop);
                }
            });
        }
        if (Schema::hasTable('members')) {
            Schema::table('members', function (Blueprint $table) {
                $drop = array_values(array_filter(
                    ['email_verified_at', 'remember_token', 'is_active', 'must_change_password'],
                    fn (string $column): bool => Schema::hasColumn('members', $column),
                ));

                if ($drop !== []) {
                    $table->dropColumn($drop);
                }
            });
        }
        Schema::table('users', function (Blueprint $table) {
            $drop = array_values(array_filter(
                ['is_active', 'must_change_password', 'app_authentication_secret', 'app_authentication_recovery_codes'],
                fn (string $column): bool => Schema::hasColumn('users', $column),
            ));

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
