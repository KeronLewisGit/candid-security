<?php

namespace Database\Factories;

use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ServiceRequest> */
class ServiceRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kind' => 'quote',
            'name' => fake()->name(),
            'company' => fake()->company(),
            'email' => fake()->safeEmail(),
            'phone' => '242-'.fake()->numerify('3##-####'),
            'island' => 'grand_bahama',
            'service' => 'security-officers',
            'details' => ['site_type' => 'retail', 'coverage' => 'unarmed', 'posts' => 2, 'hours' => 'night', 'duration' => 'ongoing'],
        ];
    }
}
