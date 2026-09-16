<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_fee_payments', function (Blueprint $table) {
            $table->decimal('interest', 15, 2)->default(0)->after('invoice_no');
            $table->decimal('surcharge', 15, 2)->default(0)->after('interest');
            $table->decimal('membership_fee', 15, 2)->default(0)->after('surcharge');
            $table->decimal('others', 15, 2)->default(0)->after('membership_fee');
        });

        $groups = DB::table('invoice_fee_payments')->orderBy('id')->get()->groupBy('invoice_no');

        foreach ($groups as $rows) {
            $keepId = $rows->first()->id;
            $interest = 0.0;
            $surcharge = 0.0;
            $membershipFee = 0.0;
            $others = 0.0;

            foreach ($rows as $row) {
                $amount = (float) $row->amount;

                match ((string) $row->category) {
                    'interest' => $interest += $amount,
                    'surcharge' => $surcharge += $amount,
                    'membership_fee' => $membershipFee += $amount,
                    default => $others += $amount,
                };
            }

            DB::table('invoice_fee_payments')->where('id', $keepId)->update([
                'interest' => $interest,
                'surcharge' => $surcharge,
                'membership_fee' => $membershipFee,
                'others' => $others,
            ]);

            $extraIds = $rows->pluck('id')->reject(fn ($id) => (int) $id === (int) $keepId)->all();

            if ($extraIds !== []) {
                DB::table('invoice_fee_payments')->whereIn('id', $extraIds)->delete();
            }
        }

        Schema::table('invoice_fee_payments', function (Blueprint $table) {
            $table->dropColumn(['category', 'amount']);
        });
    }

    public function down(): void
    {
        Schema::table('invoice_fee_payments', function (Blueprint $table) {
            $table->string('category', 32)->nullable()->after('invoice_no');
            $table->decimal('amount', 15, 2)->default(0)->after('category');
        });

        $rows = DB::table('invoice_fee_payments')->orderBy('id')->get();

        foreach ($rows as $row) {
            $lines = [
                'interest' => (float) $row->interest,
                'surcharge' => (float) $row->surcharge,
                'membership_fee' => (float) $row->membership_fee,
                'others' => (float) $row->others,
            ];
            $first = true;

            foreach ($lines as $category => $amount) {
                if ($amount < 0.01) {
                    continue;
                }

                if ($first) {
                    DB::table('invoice_fee_payments')->where('id', $row->id)->update([
                        'category' => $category,
                        'amount' => $amount,
                    ]);
                    $first = false;

                    continue;
                }

                DB::table('invoice_fee_payments')->insert([
                    'member_id' => $row->member_id,
                    'invoice_no' => $row->invoice_no,
                    'category' => $category,
                    'amount' => $amount,
                    'collection_method' => $row->collection_method,
                    'received_by' => $row->received_by,
                    'received_at' => $row->received_at,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        }

        Schema::table('invoice_fee_payments', function (Blueprint $table) {
            $table->dropColumn(['interest', 'surcharge', 'membership_fee', 'others']);
        });
    }
};
