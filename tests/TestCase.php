<?php

namespace Tests;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Setting::flush();          // the per-request settings cache is static
        $this->withoutDefer();     // office emails go out after the response; run them inline
        config(['candid.notify' => 'office@candid.test']);
    }

    protected function staff(string $role = 'admin', array $attributes = []): User
    {
        return User::factory()->role($role)->create($attributes);
    }

    /** A submitted application at the given stage, owned by a portal account. */
    protected function submitted(string $stage = 'new', array $attributes = []): Application
    {
        return Application::factory()->submitted($stage)->create($attributes);
    }

    protected function applicantFor(Application $application): Applicant
    {
        return $application->applicant;
    }
}
