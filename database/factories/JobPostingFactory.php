<?php

namespace Database\Factories;

use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<JobPosting> */
class JobPostingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => 'Security Officer',
            'position' => 'guard_unarmed',
            'island' => 'grand_bahama',
            'employment_type' => 'full_time',
            'summary' => 'Uniformed security officer for commercial sites.',
            'duties' => "Control access\nPatrol the site",
            'requirements' => "Police Certificate of Character\nGovernment photo ID",
            'openings' => 2,
            'status' => 'open',
        ];
    }

    public function island(string $island): static
    {
        return $this->state(fn () => ['island' => $island]);
    }
}
