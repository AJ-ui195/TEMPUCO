<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('username')->unique();
            $table->string('name');
            $table->string('email');
            $table->date('date_of_birth')->nullable();
            $table->string('sex', 16)->nullable();
            $table->string('civil_status', 32)->nullable();
            $table->string('address')->nullable();
            $table->string('contact_number', 32)->nullable();
            $table->string('occupation')->nullable();
            $table->string('employer_department')->nullable();
            $table->boolean('is_retiree')->default(false);
            $table->timestamps();
        });

        $memberUsers = DB::table('users')
            ->where('role', UserRole::User->value)
            ->orderBy('id')
            ->get();

        $usedUsernames = [];

        foreach ($memberUsers as $user) {
            $base = strtolower((string) str($user->email)->before('@')->slug('_'));
            if ($base === '') {
                $base = 'member'.$user->id;
            }

            $username = $base;
            $suffix = 1;
            while (isset($usedUsernames[$username])) {
                $username = $base.'_'.$suffix;
                $suffix++;
            }
            $usedUsernames[$username] = true;

            DB::table('members')->insert([
                'user_id' => $user->id,
                'username' => $username,
                'name' => $user->name,
                'email' => $user->email,
                'date_of_birth' => $user->date_of_birth ?? null,
                'sex' => null,
                'civil_status' => null,
                'address' => $user->address ?? null,
                'contact_number' => $user->cellphone ?? null,
                'occupation' => null,
                'employer_department' => null,
                'is_retiree' => (bool) ($user->is_retiree ?? false),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
