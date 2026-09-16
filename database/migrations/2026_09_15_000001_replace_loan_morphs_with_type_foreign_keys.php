<?php

use App\Models\CharacterLoan;
use App\Models\QuickLoan;
use App\Models\RegularLoan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = [
        'loan_payments',
        'loan_certifications',
        'loan_committee_decisions',
    ];

    /**
     * @var array<string, string>
     */
    private array $ownerColumns = [
        'character_loan_id' => 'character_loans',
        'quick_loan_id' => 'quick_loans',
        'regular_loan_id' => 'regular_loans',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                foreach (array_keys($this->ownerColumns) as $column) {
                    if (! Schema::hasColumn($table, $column)) {
                        $blueprint->foreignId($column)->nullable()->after('id');
                    }
                }
            });

            if (Schema::hasColumn($table, 'loanable_type') && Schema::hasColumn($table, 'loanable_id')) {
                $this->copyMorphsToOwnerColumns($table);
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                foreach ($this->ownerColumns as $column => $parent) {
                    if (! Schema::hasTable($parent)) {
                        continue;
                    }

                    $this->nullMissingParents($table, $column, $parent);
                    $blueprint->foreign($column)->references('id')->on($parent)->cascadeOnDelete();
                }
            });

            if (Schema::hasColumn($table, 'loanable_type')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->dropMorphs('loanable');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                if (! Schema::hasColumn($table, 'loanable_type')) {
                    $blueprint->nullableMorphs('loanable');
                }
            });

            $this->copyOwnerColumnsToMorphs($table);

            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                foreach (array_keys($this->ownerColumns) as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $blueprint->dropConstrainedForeignId($column);
                    }
                }
            });
        }
    }

    private function copyMorphsToOwnerColumns(string $table): void
    {
        $rows = DB::table($table)->select('id', 'loanable_type', 'loanable_id')->get();

        foreach ($rows as $row) {
            $column = $this->ownerColumnForMorphType((string) $row->loanable_type);

            if ($column === null || $row->loanable_id === null) {
                continue;
            }

            DB::table($table)->where('id', $row->id)->update([$column => $row->loanable_id]);
        }
    }

    private function copyOwnerColumnsToMorphs(string $table): void
    {
        $map = [
            'character_loan_id' => CharacterLoan::class,
            'quick_loan_id' => QuickLoan::class,
            'regular_loan_id' => RegularLoan::class,
        ];

        foreach ($map as $column => $type) {
            if (! Schema::hasColumn($table, $column)) {
                continue;
            }

            DB::table($table)
                ->whereNotNull($column)
                ->update([
                    'loanable_type' => $type,
                    'loanable_id' => DB::raw($column),
                ]);
        }
    }

    private function nullMissingParents(string $table, string $column, string $parent): void
    {
        DB::table($table)
            ->whereNotNull($column)
            ->whereNotIn($column, DB::table($parent)->select('id'))
            ->update([$column => null]);
    }

    private function ownerColumnForMorphType(string $type): ?string
    {
        $normalized = str_replace('\\\\', '\\', trim($type));

        return match (true) {
            $normalized === CharacterLoan::class, str_ends_with($normalized, 'CharacterLoan') => 'character_loan_id',
            $normalized === QuickLoan::class, str_ends_with($normalized, 'QuickLoan') => 'quick_loan_id',
            $normalized === RegularLoan::class, str_ends_with($normalized, 'RegularLoan') => 'regular_loan_id',
            default => null,
        };
    }
};
