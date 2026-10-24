<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Notifications\NewServiceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ServiceRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function quote(array $overrides = []): array
    {
        return array_merge([
            'service' => 'security-officers', 'site_type' => 'retail', 'coverage' => 'armed', 'posts' => 2, 'hours' => 'night', 'days' => 7,
            'duration' => 'ongoing', 'name' => 'Pat Client', 'company' => 'Sample Store', 'email' => 'PAT@example.com', 'phone' => '242-352-0000', 'island' => 'grand_bahama',
        ], $overrides);
    }

    public function test_quote_request_is_stored_with_details_and_office_is_emailed(): void
    {
        Notification::fake();
        $this->post(route('requests.store', 'quote'), $this->quote())->assertRedirect(route('requests.received'));

        $r = ServiceRequest::firstOrFail();
        $this->assertSame('quote', $r->kind);
        $this->assertSame('new', $r->status);
        $this->assertSame('pat@example.com', $r->email);
        $this->assertSame(2, (int) $r->detail('posts'));
        $this->assertSame('Armed officers', $r->option('coverage', ServiceRequest::COVERAGE));
        $this->assertMatchesRegularExpression('/^CQ-\d{2}-\d{4}$/', $r->reference);
        Notification::assertSentOnDemand(NewServiceRequest::class);

        $this->get(route('requests.received'))->assertOk()->assertSee($r->reference);
    }

    public function test_quote_validation(): void
    {
        $this->post(route('requests.store', 'quote'), $this->quote(['posts' => 0, 'coverage' => 'tanks', 'island' => 'florida', 'phone' => 'none', 'service' => 'armored-transport-x']))
            ->assertSessionHasErrors(['posts', 'coverage', 'island', 'phone', 'service']);
        $this->assertSame(0, ServiceRequest::count());
    }

    public function test_pickup_requires_business_date_and_route(): void
    {
        $this->post(route('requests.store', 'pickup'), ['name' => 'Sam', 'email' => 'sam@example.com', 'phone' => '242-352-0001', 'island' => 'grand_bahama'])
            ->assertSessionHasErrors(['company', 'pickup_address', 'destination', 'needed_on', 'window', 'frequency', 'existing_client']);

        $this->post(route('requests.store', 'pickup'), [
            'name' => 'Sam', 'company' => 'Sample Pharmacy', 'email' => 'sam@example.com', 'phone' => '242-352-0001', 'island' => 'grand_bahama',
            'pickup_address' => 'East Mall Drive', 'destination' => 'Bank, Freeport', 'needed_on' => now()->addDay()->toDateString(),
            'window' => 'afternoon', 'frequency' => 'weekly', 'existing_client' => 'no',
        ])->assertRedirect(route('requests.received'));
        $r = ServiceRequest::firstOrFail();
        $this->assertSame('armored-transport', $r->service);
        $this->assertSame('East Mall Drive', $r->detail('pickup_address'));
        $this->assertStringStartsWith('CP-', $r->reference);

        $this->post(route('requests.store', 'pickup'), ['needed_on' => now()->subDay()->toDateString()])->assertSessionHasErrors('needed_on');
    }

    public function test_contact_message_and_honeypot(): void
    {
        $this->post(route('requests.store', 'contact'), ['name' => 'Visitor', 'email' => 'v@example.com', 'message' => 'Do you cover Bimini?'])->assertRedirect(route('requests.received'));
        $this->assertSame(1, ServiceRequest::where('kind', 'contact')->count());

        $this->post(route('requests.store', 'contact'), ['name' => 'Bot', 'email' => 'b@example.com', 'message' => 'spam', 'website' => 'http://spam'])->assertRedirect(route('requests.received'));
        $this->assertSame(1, ServiceRequest::count());

        $this->post('/requests/hack', ['name' => 'x'])->assertNotFound();
        $this->get(route('requests.received'))->assertOk();
    }

    public function test_ops_board_move_records_first_response_and_clears_overdue(): void
    {
        $r = ServiceRequest::factory()->create();
        $r->forceFill(['created_at' => now()->subHours(30)])->save();
        $this->assertTrue($r->isOverdue());
        $this->assertSame(1, ServiceRequest::query()->overdue()->count());

        $ops = $this->staff('operations');
        $this->actingAs($ops)->get(route('admin.requests.index'))->assertOk()->assertSee($r->reference)->assertSee('waiting more than 24 hours');
        $this->actingAs($ops)->patchJson(route('admin.requests.update', $r), ['status' => 'contacted'])->assertOk()->assertJson(['label' => 'Contacted']);

        $r->refresh();
        $this->assertNotNull($r->responded_at);
        $this->assertFalse($r->isOverdue());
        $this->assertSame(0, ServiceRequest::query()->overdue()->count());

        // A pickup status is not valid for a quote.
        $this->actingAs($ops)->patchJson(route('admin.requests.update', $r), ['status' => 'completed'])->assertUnprocessable();
    }

    public function test_ops_board_tabs_and_detail_page(): void
    {
        $pickup = ServiceRequest::factory()->create(['kind' => 'pickup', 'company' => 'Pickup Co', 'details' => ['pickup_address' => 'Bay St', 'destination' => 'Bank', 'window' => 'morning', 'frequency' => 'daily', 'existing_client' => 'yes'], 'needed_on' => today()]);
        $ops = $this->staff('operations');

        $this->actingAs($ops)->get(route('admin.requests.index', ['kind' => 'pickup']))->assertOk()->assertSee('Pickup Co')->assertSee('Morning (8–11)');
        $this->actingAs($ops)->get(route('admin.requests.index', ['kind' => 'nonsense']))->assertNotFound();
        $this->actingAs($ops)->get(route('admin.requests.show', $pickup))->assertOk()->assertSee('Bay St');
        $this->actingAs($ops)->patch(route('admin.requests.update', $pickup), ['staff_notes' => 'Crew 2', 'assigned_to' => $ops->id, 'status' => 'confirmed'])->assertSessionHas('status');
        $this->assertSame('confirmed', $pickup->refresh()->status);
        $this->assertSame('Crew 2', $pickup->staff_notes);
    }
}
