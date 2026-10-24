<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\JobPosting;
use App\Models\ScreeningBooking;
use App\Models\ScreeningSlot;
use App\Services\ApplicantNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Everything here is scoped to the signed-in applicant: another applicant's
 * reference, document or booking is a 404, never a 403 that confirms it exists.
 */
class PortalController extends Controller
{
    public function dashboard(Request $request): View
    {
        $applicant = $this->applicant($request);
        $applications = $applicant->applications()->with(['job', 'documents', 'bookings.slot'])->get();

        return view('portal.dashboard', [
            'applicant' => $applicant,
            'drafts' => $applications->filter->isDraft(),
            'submitted' => $applications->reject->isDraft(),
            'inbox' => $applicant->inbox()->take(4)->get(),
            'unread' => $applicant->unreadCount(),
            'jobs' => JobPosting::live()->whereNotIn('id', $applications->pluck('job_posting_id')->filter())->latest('published_at')->take(3)->get(),
        ]);
    }

    public function show(Request $request, string $reference): View|RedirectResponse
    {
        $application = $this->own($request, $reference);
        if ($application->isDraft()) {
            return to_route('apply.step', [$reference, $application->step]);
        }
        $application->load(['job', 'documents', 'bookings.slot']);
        $kind = $application->bookableKind();

        return view('portal.application', [
            'application' => $application,
            'timeline' => $application->notes()->where('is_public', true)->get(),
            'slots' => $application->canBook() && $kind
                ? ScreeningSlot::query()->available($kind, $application->island)->take(20)->get()
                : collect(),
        ]);
    }

    public function upload(Request $request, string $reference): RedirectResponse
    {
        $application = $this->own($request, $reference);
        abort_unless($application->isActive(), 403, 'This application is closed.');
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(ApplicationDocument::TYPES))],
            'file' => ApplicationDocument::rules(required: true),
        ], [
            'file.max' => 'That file is too large. The limit is '.(ApplicationDocument::MAX_KB / 1024).' MB; a phone photo is fine.',
            'file.mimes' => 'Upload a PDF, JPG, PNG, HEIC or Word file.',
            'file.extensions' => 'Upload a PDF, JPG, PNG, HEIC or Word file.',
        ]);

        $application->documents()->where('type', $data['type'])->where('status', 'rejected')->get()->each->delete();
        $doc = ApplicationDocument::store($application, $data['type'], $request->file('file'));
        $application->notes()->create(['type' => 'document', 'is_public' => true, 'body' => $doc->typeLabel().' uploaded']);

        return back()->with('status', $doc->typeLabel().' uploaded. The recruitment team will review it.');
    }

    public function document(Request $request, ApplicationDocument $document): StreamedResponse
    {
        abort_unless($document->application()->where('applicant_id', $this->applicant($request)->id)->exists(), 404);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    public function book(Request $request, string $reference): RedirectResponse
    {
        $application = $this->own($request, $reference)->load('bookings');
        $kind = $application->bookableKind();
        abort_unless($kind && $application->canBook(), 403, 'There is nothing to book at this stage.');
        $data = $request->validate(['slot' => ['required', 'integer']]);

        $booking = DB::transaction(function () use ($application, $kind, $data) {
            $slot = ScreeningSlot::query()->whereKey($data['slot'])->where('kind', $kind)->lockForUpdate()->withCount('bookings')->first();
            if (! $slot || ! $slot->isBookable()) {
                return null;
            }

            return $application->bookings()->create(['screening_slot_id' => $slot->id, 'kind' => $kind]);
        });

        if (! $booking) {
            return back()->withErrors(['slot' => 'That time is no longer available. Please choose another.']);
        }

        $booking->load(['slot', 'application.applicant']);
        $application->notes()->create(['type' => 'booking', 'is_public' => true, 'body' => $booking->slot->kindLabel().' booked for '.$booking->slot->starts_at->format('D j M Y, g:i a').' at '.$booking->slot->location]);
        app(ApplicantNotifier::class)->booked($booking);

        return to_route('portal.applications.show', $reference)->with('status', 'Booked: '.$booking->slot->starts_at->format('l j F \a\t g:i a').'.');
    }

    public function cancel(Request $request, string $reference, ScreeningBooking $booking): RedirectResponse
    {
        $application = $this->own($request, $reference);
        abort_unless($booking->application_id === $application->id, 404);
        $booking->load('slot');
        if ($booking->slot->starts_at->lt(now()->addDay())) {
            return back()->withErrors(['slot' => 'Bookings within 24 hours cannot be changed online. Please call the office.']);
        }
        $application->notes()->create(['type' => 'booking', 'is_public' => true, 'body' => $booking->slot->kindLabel().' booking cancelled by applicant']);
        $booking->delete();

        return back()->with('status', 'Booking cancelled. Choose a new time below.');
    }

    public function withdraw(Request $request, string $reference): RedirectResponse
    {
        $application = $this->own($request, $reference);
        abort_unless($application->isActive(), 403);
        $application->moveTo('withdrawn', null, 'Withdrawn by the applicant');

        return back()->with('status', 'Your application has been withdrawn.');
    }

    public function inbox(Request $request): View
    {
        $applicant = $this->applicant($request);
        $items = $applicant->inbox()->paginate(20);
        $applicant->inbox()->whereNull('read_at')->update(['read_at' => now()]);

        return view('portal.inbox', ['items' => $items]);
    }

    public function profile(Request $request): View
    {
        return view('portal.profile', ['applicant' => $this->applicant($request)]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $applicant = $this->applicant($request);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'current_password' => ['nullable', 'required_with:password', 'current_password:applicant'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);
        $applicant->fill(['first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'phone' => $data['phone'] ?? null]);
        if (! empty($data['password'])) {
            $applicant->password = $data['password'];
        }
        $applicant->save();

        return back()->with('status', 'Profile saved.');
    }

    private function applicant(Request $request): Applicant
    {
        /** @var Applicant $applicant */
        $applicant = $request->user('applicant');

        return $applicant;
    }

    private function own(Request $request, string $reference): Application
    {
        return $this->applicant($request)->applications()->where('reference', $reference)->firstOrFail();
    }
}
