<?php

namespace Database\Factories;

use App\Models\Applicant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<Applicant> */
class ApplicantFactory extends Factory
{
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '242-'.fake()->numerify('5##-####'),
            'password' => static::$password ??= Hash::make('password'),
        ];
    }
}
