<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ScreeningSlot;
use App\Notifications\ApplicantResetPassword;
use App\Notifications\ApplicantUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicantPortalTest extends TestCase
{
    use RefreshDatabase;

    private function slot(string $kind = 'exam', array $attributes = []): ScreeningSlot
    {
        return ScreeningSlot::create($attributes + [
            'kind' => $kind, 'island' => 'grand_bahama', 'starts_at' => now()->addDays(3)->setTime(9, 0),
            'duration_minutes' => 60, 'location' => 'Freeport office', 'capacity' => 2,
        ]);
    }

    public function test_portal_requires_an_applicant_login(): void
    {
        $this->get('/portal')->assertRedirect(route('portal.login'));
        // A staff session is not an applicant session.
        $this->actingAs($this->staff())->get('/portal')->assertRedirect(route('portal.login'));
    }

    public function test_applicants_cannot_reach_the_back_office(): void
    {
        $a = $this->submitted();
        $this->actingAs($a->applicant, 'applicant')->get('/admin')->assertRedirect(route('login'));
        $this->actingAs($a->applicant, 'applicant')->get(route('admin.applications.show', $a))->assertRedirect(route('login'));
    }

    public function test_login_register_and_logout(): void
    {
        $applicant = Applicant::factory()->create(['email' => 'jo@example.com']);
        $this->post(route('portal.login'), ['email' => 'JO@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post(route('portal.login'), ['email' => 'JO@example.com', 'password' => 'password'])->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticatedAs($applicant, 'applicant');
        $this->post(route('portal.logout'))->assertRedirect(route('careers'));
        $this->assertGuest('applicant');

        $this->post(route('portal.register'), ['first_name' => 'Kim', 'last_name' => 'Moss', 'email' => 'kim@example.com', 'password' => 'long-enough', 'password_confirmation' => 'long-enough'])
            ->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticated('applicant');
    }

    public function test_password_reset_email_uses_the_portal_link(): void
    {
        Notification::fake();
        $applicant = Applicant::factory()->create();
        $this->post(route('portal.password.email'), ['email' => $applicant->email])->assertSessionHas('status');
        Notification::assertSentTo($applicant, ApplicantResetPassword::class, function ($n) use ($applicant) {
            return str_contains($n->toMail($applicant)->actionUrl, '/portal/reset-password/');
        });
        // Unknown addresses get the same answer.
        $this->post(route('portal.password.email'), ['email' => 'nobody@example.com'])->assertSessionHas('status');
    }

    public function test_dashboard_and_tracker_show_the_public_stage(): void
    {
        $a = $this->submitted('document_check');
        $this->actingAs($a->applicant, 'applicant')->get('/portal')->assertOk()->assertSee($a->reference)->assertSee('Checking your documents')->assertSee('Action needed');
        $this->actingAs($a->applicant, 'applicant')->get(route('portal.applications.show', $a->reference))->assertOk()
            ->assertSee('Checking your documents')->assertSee('Still needed: Police Character Certificate');
    }

    public function test_internal_notes_never_reach_the_applicant(): void
    {
        $a = $this->submitted('interview');
        $a->notes()->create(['type' => 'note', 'body' => 'Seemed nervous, check references twice']);
        $a->notes()->create(['type' => 'status', 'is_public' => true, 'body' => 'Interview']);

        $this->actingAs($a->applicant, 'applicant')->get(route('portal.applications.show', $a->reference))
            ->assertDontSee('Seemed nervous')->assertSee('Interview');
    }

    public function test_another_applicants_application_and_documents_are_404(): void
    {
        Storage::fake('local');
        $mine = $this->submitted();
        $theirs = $this->submitted();
        $doc = ApplicationDocument::store($theirs, 'police_certificate', UploadedFile::fake()->image('cert.jpg'));
        $me = $mine->applicant;

        $this->actingAs($me, 'applicant')->get(route('portal.applications.show', $theirs->reference))->assertNotFound();
        $this->actingAs($me, 'applicant')->get(route('portal.documents.show', $doc))->assertNotFound();
        $this->actingAs($me, 'applicant')->post(route('portal.applications.documents', $theirs->reference), ['type' => 'cv', 'file' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf')])->assertNotFound();
        $this->actingAs($me, 'applicant')->post(route('portal.applications.book', $theirs->reference), ['slot' => 1])->assertNotFound();
        $this->actingAs($me, 'applicant')->post(route('portal.applications.withdraw', $theirs->reference))->assertNotFound();
        $this->assertSame(1, $theirs->documents()->count());

        // The owner can download their own file.
        $this->actingAs($theirs->applicant, 'applicant')->get(route('portal.documents.show', $doc))->assertOk();
    }

    public function test_applicant_uploads_a_missing_document(): void
    {
        Storage::fake('local');
        $a = $this->submitted('document_check');
        $this->actingAs($a->applicant, 'applicant')
            ->post(route('portal.applications.documents', $a->reference), ['type' => 'police_certificate', 'file' => UploadedFile::fake()->image('cert.png')])
            ->assertSessionHas('status');

        $doc = $a->documents()->firstOrFail();
        $this->assertSame('pending', $doc->status);
        Storage::disk('local')->assertExists($doc->path);
        $this->assertTrue($a->notes()->where('is_public', true)->where('body', 'like', '%uploaded')->exists());
    }

    public function test_portal_upload_validation(): void
    {
        Storage::fake('local');
        $a = $this->submitted();
        $as = $this->actingAs($a->applicant, 'applicant');

        $as->post(route('portal.applications.documents', $a->reference), ['type' => 'police_certificate'])->assertSessionHasErrors('file');
        $as->post(route('portal.applications.documents', $a->reference), ['type' => 'tax_return', 'file' => UploadedFile::fake()->image('a.jpg')])->assertSessionHasErrors('type');
        $as->post(route('portal.applications.documents', $a->reference), ['type' => 'passport', 'file' => UploadedFile::fake()->create('script.php', 5, 'text/x-php')])->assertSessionHasErrors('file');
        $as->post(route('portal.applications.documents', $a->reference), ['type' => 'passport', 'file' => UploadedFile::fake()->create('big.pdf', ApplicationDocument::MAX_KB + 1, 'application/pdf')])->assertSessionHasErrors('file');
        $this->assertSame(0, $a->documents()->count());
    }

    public function test_closed_applications_do_not_accept_uploads(): void
    {
        $a = $this->submitted('rejected');
        $this->actingAs($a->applicant, 'applicant')->post(route('portal.applications.documents', $a->reference), ['type' => 'cv', 'file' => UploadedFile::fake()->create('cv.pdf', 5, 'application/pdf')])->assertForbidden();
    }

    public function test_a_sent_back_document_is_replaced_by_the_new_upload(): void
    {
        Storage::fake('local');
        $a = $this->submitted('document_check');
        $old = ApplicationDocument::store($a, 'passport', UploadedFile::fake()->image('blurry.jpg'));
        $old->forceFill(['status' => 'rejected', 'review_note' => 'Too blurry'])->save();

        $this->actingAs($a->applicant, 'applicant')->post(route('portal.applications.documents', $a->reference), ['type' => 'passport', 'file' => UploadedFile::fake()->image('sharp.jpg')]);

        $this->assertModelMissing($old);
        Storage::disk('local')->assertMissing($old->path);
        $this->assertSame(['sharp.jpg'], $a->documents()->pluck('original_name')->all());
    }

    public function test_applicant_books_a_slot_for_their_stage_and_island(): void
    {
        Notification::fake();
        $a = $this->submitted('entrance_exam');
        $exam = $this->slot('exam');
        $this->slot('exam', ['island' => 'new_providence', 'location' => 'Carmichael Road exam room']);
        $this->slot('interview', ['location' => 'Interview room']);

        $this->actingAs($a->applicant, 'applicant')->get(route('portal.applications.show', $a->reference))
            ->assertSee('Freeport office')->assertDontSee('Carmichael Road exam room')->assertDontSee('Interview room');

        $this->actingAs($a->applicant, 'applicant')->post(route('portal.applications.book', $a->reference), ['slot' => $exam->id])
            ->assertRedirect(route('portal.applications.show', $a->reference));

        $this->assertSame($exam->id, $a->bookings()->value('screening_slot_id'));
        Notification::assertSentOnDemand(ApplicantUpdate::class, fn ($n) => $n->subject === 'Entrance exam booked');
        $this->actingAs($a->applicant, 'applicant')->get(route('portal.applications.show', $a->reference))->assertSee('You are booked');

        // A second booking for the same stage is refused.
        $this->actingAs($a->applicant, 'applicant')->post(route('portal.applications.book', $a->reference), ['slot' => $exam->id])->assertForbidden();
    }

    public function test_booking_rules(): void
    {
        $full = $this->slot('exam', ['capacity' => 1]);
        $this->submitted('entrance_exam')->bookings()->create(['screening_slot_id' => $full->id, 'kind' => 'exam']);
        $interview = $this->slot('interview');
        $soon = $this->slot('exam', ['starts_at' => now()->addHour()]);

        $a = $this->submitted('entrance_exam');
        $as = $this->actingAs($a->applicant, 'applicant');
        $as->post(route('portal.applications.book', $a->reference), ['slot' => $full->id])->assertSessionHasErrors('slot');
        $as->post(route('portal.applications.book', $a->reference), ['slot' => $interview->id])->assertSessionHasErrors('slot');
        $as->post(route('portal.applications.book', $a->reference), ['slot' => $soon->id])->assertSessionHasErrors('slot');
        $this->assertSame(0, $a->bookings()->count());

        // Not at a bookable stage at all.
        $b = $this->submitted('background_check');
        $this->actingAs($b->applicant, 'applicant')->post(route('portal.applications.book', $b->reference), ['slot' => $interview->id])->assertForbidden();
    }

    public function test_cancel_a_booking_more_than_a_day_ahead(): void
    {
        $a = $this->submitted('interview');
        $later = $this->slot('interview', ['starts_at' => now()->addDays(4)]);
        $booking = $a->bookings()->create(['screening_slot_id' => $later->id, 'kind' => 'interview']);

        $this->actingAs($a->applicant, 'applicant')->delete(route('portal.applications.cancel', [$a->reference, $booking]))->assertSessionHas('status');
        $this->assertModelMissing($booking);

        $tomorrow = $this->slot('interview', ['starts_at' => now()->addHours(20)]);
        $late = $a->bookings()->create(['screening_slot_id' => $tomorrow->id, 'kind' => 'interview']);
        $this->actingAs($a->applicant, 'applicant')->delete(route('portal.applications.cancel', [$a->reference, $late]))->assertSessionHasErrors('slot');
        $this->assertModelExists($late);

        // Someone else's booking id under my reference is 404.
        $other = $this->submitted('interview');
        $theirs = $other->bookings()->create(['screening_slot_id' => $later->id, 'kind' => 'interview']);
        $this->actingAs($a->applicant, 'applicant')->delete(route('portal.applications.cancel', [$a->reference, $theirs]))->assertNotFound();
    }

    public function test_withdraw_and_inbox(): void
    {
        Notification::fake();
        $a = $this->submitted('document_check');
        $this->actingAs($a->applicant, 'applicant')->post(route('portal.applications.withdraw', $a->reference))->assertSessionHas('status');
        $this->assertSame('withdrawn', $a->refresh()->status);

        $this->assertSame(1, $a->applicant->unreadCount());
        $this->actingAs($a->applicant, 'applicant')->get(route('portal.inbox'))->assertOk()->assertSee('Withdrawn');
        $this->assertSame(0, $a->applicant->unreadCount());
    }

    public function test_profile_update_requires_current_password_to_change_it(): void
    {
        $applicant = Applicant::factory()->create();
        $this->actingAs($applicant, 'applicant')->put(route('portal.profile.update'), ['first_name' => 'New', 'last_name' => 'Name', 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass'])
            ->assertSessionHasErrors('current_password');
        $this->actingAs($applicant, 'applicant')->put(route('portal.profile.update'), ['first_name' => 'New', 'last_name' => 'Name', 'current_password' => 'password', 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass'])
            ->assertSessionHas('status');
        $this->assertSame('New', $applicant->refresh()->first_name);
    }

    public function test_draft_reference_in_portal_redirects_to_the_form(): void
    {
        $draft = Application::factory()->create(['step' => 3]);
        $this->actingAs($draft->applicant, 'applicant')->get(route('portal.applications.show', $draft->reference))->assertRedirect(route('apply.step', [$draft->reference, 3]));
    }

    public function test_a_slot_on_another_island_cannot_be_booked(): void
    {
        Notification::fake();
        $a = $this->submitted('entrance_exam', ['island' => 'grand_bahama']);
        $abaco = $this->slot('exam', ['island' => 'abaco']);

        $this->actingAs($a->applicant, 'applicant')->post(route('portal.applications.book', $a->reference), ['slot' => $abaco->id])->assertSessionHasErrors('slot');
        $this->assertSame(0, $a->bookings()->count());
    }

    public function test_withdrawing_hands_a_future_seat_back(): void
    {
        Notification::fake();
        $slot = $this->slot('exam', ['capacity' => 1]);
        $a = $this->submitted('entrance_exam', ['island' => 'grand_bahama']);
        $this->actingAs($a->applicant, 'applicant')->post(route('portal.applications.book', $a->reference), ['slot' => $slot->id])->assertRedirect();
        $this->assertSame(0, ScreeningSlot::query()->available('exam', 'grand_bahama')->count());

        $this->actingAs($a->applicant, 'applicant')->post(route('portal.applications.withdraw', $a->reference))->assertRedirect();
        $this->assertSame(0, $a->bookings()->count());
        $this->assertSame(1, ScreeningSlot::query()->available('exam', 'grand_bahama')->count());
    }

    public function test_signing_out_of_the_portal_keeps_a_staff_session(): void
    {
        $staff = $this->staff('admin', ['email' => 'boss@candid.test']);
        $applicant = Applicant::factory()->create(['email' => 'jo@example.com']);
        $this->post(route('login'), ['email' => 'boss@candid.test', 'password' => 'password'])->assertRedirect();
        $this->post(route('portal.login'), ['email' => 'jo@example.com', 'password' => 'password'])->assertRedirect();
        $staffKey = auth()->guard('web')->getName();
        $applicantKey = auth()->guard('applicant')->getName();

        $this->post(route('portal.logout'))->assertRedirect(route('careers'));
        $this->assertSame($staff->id, session($staffKey));
        $this->assertNull(session($applicantKey));

        $this->post(route('portal.login'), ['email' => 'jo@example.com', 'password' => 'password'])->assertRedirect(route('portal.dashboard'));
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertSame($applicant->id, session($applicantKey));
        $this->assertNull(session($staffKey));
    }
}
