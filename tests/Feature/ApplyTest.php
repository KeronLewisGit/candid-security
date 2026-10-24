<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\JobPosting;
use App\Notifications\ApplicantUpdate;
use App\Notifications\NewApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplyTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function step1(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Andre', 'last_name' => 'Rolle', 'email' => 'Andre@Example.com', 'phone' => '242-555-0101',
            'date_of_birth' => '1995-04-02', 'island' => 'grand_bahama', 'settlement' => 'Eight Mile Rock', 'citizenship' => 'bahamian',
            'emergency_name' => 'Mary Rolle', 'emergency_phone' => '242-555-0102',
            'password' => 'secret-pass', 'password_confirmation' => 'secret-pass',
        ], $overrides);
    }

    private function begin(?JobPosting $job = null): Application
    {
        $this->post(route('apply.begin'), $this->step1($job ? ['job' => $job->slug] : []))->assertRedirect();

        return Application::query()->latest('id')->firstOrFail();
    }

    public function test_step_one_creates_the_account_and_a_draft_and_signs_in(): void
    {
        $job = JobPosting::factory()->create();
        $application = $this->begin($job);

        $this->assertAuthenticatedAs(Applicant::where('email', 'andre@example.com')->firstOrFail(), 'applicant');
        $this->assertTrue($application->isDraft());
        $this->assertSame($job->id, $application->job_posting_id);
        $this->assertSame('guard_unarmed', $application->position);
        $this->assertSame(2, $application->step);
        $this->assertStringStartsWith('CSL-', $application->reference);
    }

    public function test_step_one_validates_and_never_asks_for_national_insurance(): void
    {
        $this->get(route('apply'))->assertOk()->assertDontSee('National Insurance number')->assertDontSee('name="nib"', false);

        $this->post(route('apply.begin'), $this->step1(['phone' => 'call me', 'date_of_birth' => now()->addDay()->toDateString(), 'island' => 'cuba', 'password_confirmation' => 'nope']))
            ->assertSessionHasErrors(['phone', 'date_of_birth', 'island', 'password']);
        $this->assertSame(0, Applicant::count());
    }

    public function test_existing_account_is_told_to_sign_in(): void
    {
        Applicant::factory()->create(['email' => 'andre@example.com']);

        $this->post(route('apply.begin'), $this->step1())->assertSessionHasErrors(['email' => 'You already have an applicant account with this email. Sign in to continue your application.']);
    }

    public function test_full_flow_with_uploads_submits_and_notifies(): void
    {
        Storage::fake('local');
        Notification::fake();
        $a = $this->begin();
        $ref = $a->reference;

        $this->post(route('apply.save', [$ref, 2]), ['position' => 'guard_armed', 'work_islands' => ['grand_bahama', 'bimini'], 'employment_type' => 'full_time', 'shifts' => ['day', 'night']])
            ->assertRedirect(route('apply.step', [$ref, 3]));
        $this->post(route('apply.save', [$ref, 3]), ['drivers_licence' => 'private', 'firearm_licence' => '1', 'experience_years' => 3, 'education' => 'bgcse', 'certifications' => ['first_aid']])
            ->assertRedirect(route('apply.step', [$ref, 4]));
        $this->post(route('apply.save', [$ref, 4]), [
            'police_certificate' => UploadedFile::fake()->image('police.jpg'),
            'passport' => UploadedFile::fake()->create('passport.pdf', 300, 'application/pdf'),
        ])->assertRedirect(route('apply.step', [$ref, 5]));

        $this->get(route('apply.step', [$ref, 5]))->assertOk()->assertSee('Security officer (armed)')->assertSee('Passport (first three pages) or photo ID')->assertDontSee('we still need');

        $this->post(route('apply.save', [$ref, 5]), ['declaration' => '1', 'consent' => '1'])->assertRedirect(route('apply.submitted', $ref));

        $a->refresh()->load('documents');
        $this->assertFalse($a->isDraft());
        $this->assertSame('new', $a->status);
        $this->assertTrue($a->firearm_licence);
        $this->assertSame(['grand_bahama', 'bimini'], $a->work_islands);
        $this->assertCount(2, $a->documents);
        $this->assertTrue($a->documentsComplete());
        foreach ($a->documents as $doc) {
            Storage::disk('local')->assertExists($doc->path);
            $this->assertStringStartsWith('applications/'.$a->id.'/', $doc->path);
        }

        Notification::assertSentTo(new AnonymousNotifiable, NewApplication::class, fn ($n, $c, $notifiable) => $notifiable->routes['mail'] === ['office@candid.test']);
        Notification::assertSentTo(new AnonymousNotifiable, ApplicantUpdate::class, fn ($n, $c, $notifiable) => $notifiable->routes['mail'] === 'andre@example.com');
        $this->assertSame(1, $a->applicant->inbox()->count());
        $this->get(route('apply.submitted', $ref))->assertOk()->assertSee($ref);
    }

    public function test_upload_validation_rejects_wrong_types_and_big_files(): void
    {
        Storage::fake('local');
        $a = $this->begin();
        $a->forceFill(['step' => 4])->save();

        $this->post(route('apply.save', [$a->reference, 4]), [
            'police_certificate' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'),
            'passport' => UploadedFile::fake()->create('huge.pdf', 9000, 'application/pdf'),
            'cv' => UploadedFile::fake()->create('page.html', 5, 'text/html'),
        ])->assertSessionHasErrors(['police_certificate', 'passport', 'cv']);

        $this->assertSame(0, $a->documents()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_sending_without_documents_is_allowed_but_flagged(): void
    {
        Notification::fake();
        $a = $this->begin();
        $a->forceFill(['step' => 5, 'position' => 'guard_unarmed', 'work_islands' => ['grand_bahama'], 'shifts' => ['day'], 'employment_type' => 'either', 'drivers_licence' => 'none', 'education' => 'high_school'])->save();

        $this->get(route('apply.step', [$a->reference, 5]))->assertSee('we still need');
        $this->post(route('apply.save', [$a->reference, 5]), [])->assertSessionHasErrors(['declaration', 'consent']);
        $this->post(route('apply.save', [$a->reference, 5]), ['declaration' => '1', 'consent' => '1']);

        $this->assertSame(['police_certificate', 'passport'], $a->refresh()->load('documents')->missingDocuments());
    }

    public function test_save_and_exit_goes_to_the_portal_and_resume_returns_to_the_step(): void
    {
        $job = JobPosting::factory()->create();
        $a = $this->begin($job);

        $this->post(route('apply.save', [$a->reference, 2]), ['job_posting_id' => $job->id, 'work_islands' => ['grand_bahama'], 'employment_type' => 'full_time', 'shifts' => ['night'], 'action' => 'exit'])
            ->assertRedirect(route('portal.dashboard'));
        $this->get(route('portal.dashboard'))->assertSee('Draft · step 3 of 5')->assertSee(route('apply.step', [$a->reference, 3]), false);

        // Starting the same job again resumes the draft instead of creating a second one.
        $this->get(route('apply', ['job' => $job->slug]))->assertRedirect(route('apply.step', [$a->reference, 3]));
        $this->assertSame(1, Application::count());
    }

    public function test_steps_cannot_be_skipped(): void
    {
        $a = $this->begin();
        $this->get(route('apply.step', [$a->reference, 5]))->assertRedirect(route('apply.step', [$a->reference, 2]));
        $this->post(route('apply.save', [$a->reference, 5]), ['declaration' => '1', 'consent' => '1'])->assertNotFound();
        $this->assertTrue($a->refresh()->isDraft());
    }

    public function test_someone_elses_draft_is_not_found(): void
    {
        $a = $this->begin();
        $other = Applicant::factory()->create();

        $this->actingAs($other, 'applicant')->get(route('apply.step', [$a->reference, 2]))->assertNotFound();
        $this->actingAs($other, 'applicant')->post(route('apply.save', [$a->reference, 2]), [])->assertNotFound();
    }

    public function test_guests_cannot_reach_draft_steps(): void
    {
        $a = Application::factory()->create(['step' => 2]);
        $this->get(route('apply.step', [$a->reference, 2]))->assertRedirect(route('portal.login'));
    }

    public function test_a_sent_application_cannot_be_edited(): void
    {
        $a = $this->submitted();
        $this->actingAs($a->applicant, 'applicant')->get(route('apply.step', [$a->reference, 3]))->assertRedirect(route('portal.applications.show', $a->reference));
    }

    public function test_signed_in_applicant_cannot_apply_twice_for_the_same_job(): void
    {
        $job = JobPosting::factory()->create();
        $a = $this->submitted('document_check', ['job_posting_id' => $job->id]);

        $this->actingAs($a->applicant, 'applicant')->get(route('apply', ['job' => $job->slug]))->assertRedirect(route('portal.dashboard'));
        $this->assertSame(1, Application::count());
    }

    public function test_signed_in_applicant_gets_a_prefilled_new_draft(): void
    {
        $a = $this->submitted('rejected', ['settlement' => 'Hunters']);
        $job = JobPosting::factory()->create(['title' => 'Lifeguard', 'position' => 'lifeguard']);

        $this->actingAs($a->applicant, 'applicant')->get(route('apply', ['job' => $job->slug]))->assertRedirect();
        $draft = Application::query()->whereNull('submitted_at')->firstOrFail();
        $this->assertSame('Hunters', $draft->settlement);
        $this->assertSame('lifeguard', $draft->position);
    }
}
