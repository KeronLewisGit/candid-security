<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Services\ApplicantNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /** Staff upload on the applicant's behalf, e.g. a certificate dropped off at the office. */
    public function store(Request $request, Application $application): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(ApplicationDocument::TYPES))],
            'file' => ApplicationDocument::rules(required: true),
        ]);
        $doc = ApplicationDocument::store($application, $data['type'], $request->file('file'));
        $application->notes()->create(['user_id' => $request->user()?->id, 'type' => 'document', 'body' => $doc->typeLabel().' uploaded by staff']);

        return back()->with('status', $doc->typeLabel().' added.');
    }

    public function show(ApplicationDocument $document): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($document->path), 404);
        $inline = in_array($document->mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true);

        return Storage::disk('local')->download($document->path, $document->original_name, $inline ? ['Content-Disposition' => 'inline; filename="'.addslashes($document->original_name).'"'] : []);
    }

    public function update(Request $request, ApplicationDocument $document): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['accepted', 'rejected'])],
            'review_note' => ['nullable', 'required_if:status,rejected', 'string', 'max:300'],
        ], ['review_note.required_if' => 'Tell the applicant why the document was sent back.']);

        $document->forceFill([
            'status' => $data['status'],
            'review_note' => $data['review_note'] ?? null,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
        ])->save();
        $document->load('application.applicant');
        $document->application->notes()->create([
            'user_id' => $request->user()?->id, 'type' => 'document', 'is_public' => true,
            'body' => $document->typeLabel().' '.($data['status'] === 'accepted' ? 'accepted' : 'sent back'.($document->review_note ? ': '.$document->review_note : '')),
        ]);
        app(ApplicantNotifier::class)->documentReviewed($document);

        return back()->with('status', $document->typeLabel().' '.($data['status'] === 'accepted' ? 'accepted.' : 'sent back. The applicant has been asked to upload it again.'));
    }
}
