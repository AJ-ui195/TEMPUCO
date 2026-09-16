<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => 'password',
            'date_of_birth' => fake()->optional()->date(),
            'sex' => fake()->randomElement(['male', 'female']),
            'civil_status' => fake()->randomElement(['single', 'married', 'widowed', 'separated']),
            'address' => fake()->streetAddress(),
            'contact_number' => fake()->numerify('09#########'),
            'occupation' => fake()->jobTitle(),
            'employer_department' => fake()->company(),
            'is_retiree' => false,
            'points' => 0,
            'is_active' => true,
            'must_change_password' => false,
        ];
    }
}
