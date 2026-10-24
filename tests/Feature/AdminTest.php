<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\JobPosting;
use App\Models\MessageTemplate;
use App\Models\ScreeningSlot;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\ApplicantUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_go_to_the_staff_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/admin/applications')->assertRedirect(route('login'));
    }

    public function test_login_and_deactivated_accounts(): void
    {
        $user = $this->staff('recruiter', ['email' => 'hr@candid.test']);
        $this->post('/login', ['email' => 'HR@candid.test', 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
        $this->assertNotNull($user->refresh()->last_login_at);
        $this->post('/logout');

        $user->update(['is_active' => false]);
        $this->post('/login', ['email' => 'hr@candid.test', 'password' => 'password'])->assertSessionHasErrors('email');
        // An existing session is ended on the next request.
        $this->actingAs($user)->get('/admin')->assertRedirect(route('login'));
    }

    public function test_role_boundaries(): void
    {
        $a = $this->submitted();
        $r = ServiceRequest::factory()->create();
        $recruiter = $this->staff('recruiter');
        $ops = $this->staff('operations');
        $admin = $this->staff('admin');

        $this->actingAs($recruiter)->get(route('admin.applications.show', $a))->assertOk();
        $this->actingAs($recruiter)->get(route('admin.requests.index'))->assertForbidden();
        $this->actingAs($recruiter)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($recruiter)->get(route('admin.users.index'))->assertForbidden();

        $this->actingAs($ops)->get(route('admin.requests.show', $r))->assertOk();
        $this->actingAs($ops)->get(route('admin.applications.index'))->assertForbidden();
        $this->actingAs($ops)->patch(route('admin.applications.update', $a), ['status' => 'hired'])->assertForbidden();
        $this->actingAs($ops)->get(route('admin.jobs.index'))->assertForbidden();
        $this->assertSame('new', $a->refresh()->status);

        foreach (['admin.dashboard', 'admin.applications.index', 'admin.applications.pipeline', 'admin.requests.index', 'admin.settings.edit', 'admin.users.index', 'admin.jobs.index', 'admin.slots.index', 'admin.templates.index'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }

    public function test_dashboard_leads_with_the_number_to_act_on(): void
    {
        $this->submitted('new');
        $this->submitted('document_check');
        $this->submitted('interview');
        $old = ServiceRequest::factory()->create();
        $old->forceFill(['created_at' => now()->subHours(30)])->save();
        ServiceRequest::factory()->create(); // fresh, not overdue

        $this->actingAs($this->staff('recruiter'))->get('/admin')->assertOk()
            ->assertSeeInOrder(['>2<', 'applicants are waiting on the document check'], false)->assertDontSee('unanswered');
        $this->actingAs($this->staff('operations'))->get('/admin')->assertOk()
            ->assertSeeInOrder(['>1<', 'client request has gone unanswered for over 24 hours'], false);
        $this->actingAs($this->staff('admin'))->get('/admin')->assertOk()->assertSeeInOrder(['>3<', 'items need action now'], false);
    }

    public function test_kanban_drag_moves_stage_notifies_and_logs(): void
    {
        Notification::fake();
        $a = $this->submitted('document_check');
        $recruiter = $this->staff('recruiter');

        $this->actingAs($recruiter)->patchJson(route('admin.applications.update', $a), ['status' => 'entrance_exam'])
            ->assertOk()->assertJson(['status' => 'entrance_exam', 'label' => 'Entrance exam', 'moved' => true]);

        $a->refresh();
        $this->assertSame('entrance_exam', $a->status);
        $this->assertTrue($a->notes()->where('type', 'status')->where('is_public', true)->exists());
        Notification::assertSentOnDemand(ApplicantUpdate::class, fn ($n) => str_contains($n->body, 'Choose a time') || str_contains($n->body, 'choose a time'));
        $this->assertSame(1, $a->applicant->inbox()->count());

        // Invalid stage is refused and changes nothing.
        $this->actingAs($recruiter)->patchJson(route('admin.applications.update', $a), ['status' => 'promoted'])->assertUnprocessable();
    }

    public function test_pipeline_filters_by_island(): void
    {
        $this->submitted('new', ['first_name' => 'Freeporter', 'island' => 'grand_bahama']);
        $this->submitted('new', ['first_name' => 'Nassauvian', 'island' => 'new_providence']);
        $this->submitted('hired', ['first_name' => 'Hiredperson']);

        $this->actingAs($this->staff())->get(route('admin.applications.pipeline', ['island' => 'new_providence']))->assertOk()
            ->assertSee('Nassauvian')->assertDontSee('Freeporter')->assertDontSee('Hiredperson');
    }

    public function test_drafts_stay_out_of_the_back_office(): void
    {
        $draft = Application::factory()->create(['first_name' => 'Halfway']);
        $admin = $this->staff();
        $this->actingAs($admin)->get(route('admin.applications.index'))->assertDontSee('Halfway');
        $this->actingAs($admin)->get(route('admin.applications.show', $draft))->assertNotFound();
    }

    public function test_screening_results_are_saved_and_flagged(): void
    {
        $a = $this->submitted('drug_test');
        $this->actingAs($this->staff('recruiter'))->patch(route('admin.applications.update', $a), ['exam_score' => 64, 'exam_taken_on' => '2026-10-20', 'drug_test_result' => 'negative'])
            ->assertSessionHas('status');

        $a->refresh();
        $this->assertSame(64, $a->exam_score);
        $this->assertFalse($a->examPassed());
        $this->assertSame('negative', $a->drug_test_result);
        $this->assertSame('drug_test', $a->status);
        $this->assertTrue($a->notes()->where('body', 'like', 'Exam score: 64%%')->exists());

        $this->actingAs($this->staff())->get(route('admin.applications.show', $a))->assertSee('Below pass mark');
        $this->patchJson(route('admin.applications.update', $a), ['exam_score' => 101])->assertUnprocessable();
    }

    public function test_document_review_accept_and_send_back(): void
    {
        Storage::fake('local');
        Notification::fake();
        $a = $this->submitted('document_check');
        $doc = ApplicationDocument::store($a, 'passport', UploadedFile::fake()->image('id.jpg'));
        $recruiter = $this->staff('recruiter');

        $this->actingAs($recruiter)->get(route('admin.documents.show', $doc))->assertOk();
        $this->actingAs($recruiter)->patch(route('admin.documents.update', $doc), ['status' => 'rejected'])->assertSessionHasErrors('review_note');
        $this->actingAs($recruiter)->patch(route('admin.documents.update', $doc), ['status' => 'rejected', 'review_note' => 'Expired passport']);

        $this->assertSame('rejected', $doc->refresh()->status);
        $this->assertSame(['police_certificate', 'passport'], $a->load('documents')->missingDocuments());
        Notification::assertSentOnDemand(ApplicantUpdate::class, fn ($n) => str_contains($n->body, 'Expired passport'));

        // Operations staff cannot open applicant documents.
        $this->actingAs($this->staff('operations'))->get(route('admin.documents.show', $doc))->assertForbidden();
    }

    public function test_staff_can_upload_a_document_for_the_applicant(): void
    {
        Storage::fake('local');
        $a = $this->submitted();
        $this->actingAs($this->staff('recruiter'))->post(route('admin.documents.store', $a), ['type' => 'police_certificate', 'file' => UploadedFile::fake()->create('cert.pdf', 50, 'application/pdf')])
            ->assertSessionHas('status');
        $this->assertSame(1, $a->documents()->count());
        $this->actingAs($this->staff('recruiter'))->post(route('admin.documents.store', $a), ['type' => 'police_certificate', 'file' => UploadedFile::fake()->create('x.exe', 5)])->assertSessionHasErrors('file');
    }

    public function test_single_email_uses_placeholders_and_can_move_stage(): void
    {
        Notification::fake();
        $a = $this->submitted('document_check', ['first_name' => 'Keisha']);
        $this->actingAs($this->staff('recruiter', ['name' => 'Rita Recruiter']))->post(route('admin.applications.message', $a), [
            'subject' => 'Hello {first_name}', 'body' => 'Book here {portal_link} – {recruiter}', 'set_status' => 'entrance_exam',
        ])->assertSessionHas('status');

        Notification::assertSentOnDemand(ApplicantUpdate::class, fn ($n) => $n->subject === 'Hello Keisha' && str_contains($n->body, 'Rita Recruiter') && str_contains($n->body, '/portal/applications/'.$a->reference));
        $this->assertSame('entrance_exam', $a->refresh()->status);
        $this->assertTrue($a->notes()->where('type', 'email')->where('is_public', false)->exists());
    }

    public function test_bulk_email_reaches_only_selected_applicants(): void
    {
        Notification::fake();
        $picked = [$this->submitted('new'), $this->submitted('new')];
        $skipped = $this->submitted('new');
        $template = MessageTemplate::create(['name' => 'Docs', 'subject' => 'Documents for {reference}', 'body' => 'Hi {first_name}', 'set_status' => 'document_check']);

        $this->actingAs($this->staff('recruiter'))->post(route('admin.applications.bulk'), [
            'ids' => [$picked[0]->id, $picked[1]->id], 'subject' => $template->subject, 'body' => $template->body, 'set_status' => 'document_check',
        ])->assertSessionHas('status', 'Email sent to 2 applicants.');

        foreach ($picked as $a) {
            $this->assertSame('document_check', $a->refresh()->status);
        }
        $this->assertSame('new', $skipped->refresh()->status);
        Notification::assertSentOnDemandTimes(ApplicantUpdate::class, 4); // message + stage change for each
        $this->actingAs($this->staff('recruiter'))->post(route('admin.applications.bulk'), ['subject' => 'x', 'body' => 'y'])->assertSessionHasErrors('ids');
    }

    public function test_csv_export_respects_filters(): void
    {
        $this->submitted('new', ['first_name' => 'Abaconian', 'island' => 'abaco']);
        $this->submitted('new', ['first_name' => 'Freeporter', 'island' => 'grand_bahama', 'exam_score' => 88]);

        $response = $this->actingAs($this->staff())->get(route('admin.applications.export', ['island' => 'abaco']));
        $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Reference,Submitted', $csv);
        $this->assertStringContainsString('Abaconian', $csv);
        $this->assertStringNotContainsString('Freeporter', $csv);
    }

    public function test_list_filters_island_stage_and_missing_documents(): void
    {
        Storage::fake('local');
        $complete = $this->submitted('new', ['first_name' => 'Complete']);
        ApplicationDocument::store($complete, 'police_certificate', UploadedFile::fake()->image('a.jpg'));
        ApplicationDocument::store($complete, 'passport', UploadedFile::fake()->image('b.jpg'));
        $this->submitted('new', ['first_name' => 'Missing']);
        $this->submitted('offer', ['first_name' => 'Offered', 'island' => 'bimini']);
        $admin = $this->staff();

        $this->actingAs($admin)->get(route('admin.applications.index', ['docs' => 'missing']))->assertSee('Missing')->assertDontSee('>Complete<', false);
        $this->actingAs($admin)->get(route('admin.applications.index', ['stage' => 'offer']))->assertSee('Offered')->assertDontSee('>Missing<', false);
        $this->actingAs($admin)->get(route('admin.applications.index', ['island' => 'bimini']))->assertSee('Offered')->assertDontSee('>Complete<', false);
        $this->actingAs($admin)->get(route('admin.applications.index', ['q' => 'Offered']))->assertSee('Offered');
    }

    public function test_job_crud_and_closing_instead_of_deleting(): void
    {
        $admin = $this->staff('recruiter');
        $this->actingAs($admin)->post(route('admin.jobs.store'), [
            'title' => 'Armed Officer', 'position' => 'guard_armed', 'island' => 'new_providence', 'employment_type' => 'full_time', 'status' => 'open', 'openings' => 3, 'summary' => 'Armed posts in Nassau.',
        ])->assertRedirect(route('admin.jobs.index'));
        $job = JobPosting::firstOrFail();
        $this->assertSame('armed-officer-nassau', $job->slug);
        $this->assertNotNull($job->published_at);
        $this->get('/careers')->assertSee('Armed Officer');

        $this->submitted('new', ['job_posting_id' => $job->id]);
        $this->actingAs($admin)->delete(route('admin.jobs.destroy', $job));
        $this->assertSame('closed', $job->refresh()->status);
    }

    public function test_screening_sessions_repeat_and_are_protected_once_booked(): void
    {
        $recruiter = $this->staff('recruiter');
        $this->actingAs($recruiter)->post(route('admin.slots.store'), [
            'kind' => 'exam', 'island' => 'grand_bahama', 'date' => now()->addDays(2)->toDateString(), 'time' => '09:00',
            'duration_minutes' => 90, 'location' => 'Freeport office', 'capacity' => 20, 'repeat' => 4,
        ])->assertSessionHas('status');
        $this->assertSame(4, ScreeningSlot::count());

        $slot = ScreeningSlot::firstOrFail();
        $this->submitted('entrance_exam')->bookings()->create(['screening_slot_id' => $slot->id, 'kind' => 'exam']);
        $this->actingAs($recruiter)->delete(route('admin.slots.destroy', $slot))->assertSessionHasErrors('slot');
        $this->assertModelExists($slot);
        $this->actingAs($recruiter)->get(route('admin.slots.index'))->assertOk()->assertSee('1/20');
    }

    public function test_templates_crud(): void
    {
        $recruiter = $this->staff('recruiter');
        $this->actingAs($recruiter)->post(route('admin.templates.store'), ['name' => 'Exam reminder', 'subject' => 'Exam', 'body' => 'Bring a pen', 'set_status' => 'entrance_exam'])->assertRedirect(route('admin.templates.index'));
        $t = MessageTemplate::firstOrFail();
        $this->actingAs($recruiter)->put(route('admin.templates.update', $t), ['name' => 'Exam reminder', 'subject' => 'Exam day', 'body' => 'Bring a pen'])->assertRedirect();
        $this->assertSame('Exam day', $t->refresh()->subject);
        $this->actingAs($recruiter)->delete(route('admin.templates.destroy', $t));
        $this->assertModelMissing($t);
    }

    public function test_settings_update_validates_and_appears_on_the_site(): void
    {
        $admin = $this->staff();
        $this->actingAs($admin)->put(route('admin.settings.update'), ['hr_email' => 'not-an-email', 'exam_pass_mark' => 70])->assertSessionHasErrors('hr_email');
        $this->actingAs($admin)->put(route('admin.settings.update'), ['freeport_phone' => '242-352-1111', 'freeport_hours' => 'Mon–Fri 9–5', 'hr_email' => 'jobs@candidbahamas.net', 'exam_pass_mark' => 75])->assertSessionHas('status');

        $this->get('/locations')->assertSee('242-352-1111')->assertSee('Mon–Fri 9–5');
        $this->get('/careers')->assertSee('jobs@candidbahamas.net')->assertSee('75%');
    }

    public function test_users_admin_cannot_lock_themselves_out(): void
    {
        $admin = $this->staff();
        $this->actingAs($admin)->post(route('admin.users.store'), ['name' => 'Ops Lead', 'email' => 'ops@candid.test', 'role' => 'operations', 'password' => 'long-password-1', 'is_active' => '1'])->assertRedirect(route('admin.users.index'));
        $this->assertSame('operations', User::where('email', 'ops@candid.test')->value('role'));

        $this->actingAs($admin)->put(route('admin.users.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'role' => 'recruiter', 'is_active' => '1'])->assertSessionHasErrors('role');
        $this->assertSame('admin', $admin->refresh()->role);
    }
}
