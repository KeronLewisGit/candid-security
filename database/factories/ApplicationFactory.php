<?php

namespace Database\Factories;

use App\Models\Applicant;
use App\Models\Application;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Application> */
class ApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'applicant_id' => Applicant::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '242-'.fake()->numerify('5##-####'),
            'date_of_birth' => now()->subYears(fake()->numberBetween(19, 50))->toDateString(),
            'island' => 'grand_bahama',
            'settlement' => 'Freeport',
            'citizenship' => 'bahamian', 'emergency_name' => 'Next of kin', 'emergency_phone' => '242-555-0100',
            'position' => 'guard_unarmed',
            'work_islands' => ['grand_bahama'],
            'employment_type' => 'full_time',
            'shifts' => ['day', 'night'],
            'drivers_licence' => 'private',
            'experience_years' => 2,
            'education' => 'bgcse',
            'step' => 5,
        ];
    }

    /** Sent, in the pipeline at the given stage. */
    public function submitted(string $status = 'new'): static
    {
        return $this->afterMaking(function (Application $a) use ($status) {
            $a->submitted_at = now()->subDays(2);
            $a->status = $status;
            $a->consented_at = now()->subDays(2);
        })->afterCreating(function (Application $a) {
            // Keep email in step with the applicant account so portal tests read naturally.
            $a->forceFill(['email' => $a->applicant()->value('email')])->saveQuietly();
        });
    }
}
