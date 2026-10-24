<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobPosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JobController extends Controller
{
    public function index(Request $request): View
    {
        $island = input_string($request->query('island'));

        return view('admin.jobs.index', [
            'jobs' => JobPosting::query()->when($island, fn ($q) => $q->where('island', $island))
                ->withCount(['applications as active_count' => fn ($q) => $q->active()])
                ->orderByRaw("case status when 'open' then 0 when 'paused' then 1 when 'draft' then 2 else 3 end")->latest()->get(),
            'island' => $island,
        ]);
    }

    public function create(): View
    {
        return view('admin.jobs.form', ['job' => new JobPosting(['status' => 'draft', 'employment_type' => 'full_time', 'openings' => 1, 'island' => 'grand_bahama', 'position' => 'guard_unarmed'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $job = JobPosting::create($this->validated($request));

        return to_route('admin.jobs.index')->with('status', '“'.$job->title.'” saved.');
    }

    public function edit(JobPosting $job): View
    {
        return view('admin.jobs.form', ['job' => $job]);
    }

    public function update(Request $request, JobPosting $job): RedirectResponse
    {
        $job->update($this->validated($request));

        return to_route('admin.jobs.index')->with('status', '“'.$job->title.'” updated.');
    }

    public function destroy(JobPosting $job): RedirectResponse
    {
        if ($job->applications()->exists()) {
            $job->update(['status' => 'closed']);

            return back()->with('status', '“'.$job->title.'” has applications, so it was closed instead of deleted.');
        }
        $job->delete();

        return back()->with('status', 'Job deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'position' => ['required', Rule::in(array_keys(Application::POSITIONS))],
            'island' => ['required', Rule::in(array_keys((array) config('candid.islands')))],
            'employment_type' => ['required', Rule::in(array_keys(JobPosting::EMPLOYMENT_TYPES))],
            'status' => ['required', Rule::in(array_keys(JobPosting::STATUSES))],
            'openings' => ['required', 'integer', 'min:1', 'max:500'],
            'summary' => ['required', 'string', 'max:600'],
            'description' => ['nullable', 'string', 'max:5000'],
            'duties' => ['nullable', 'string', 'max:5000'],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'closes_on' => ['nullable', 'date'],
            'is_featured' => ['boolean'],
        ]);
        $data['is_featured'] = $request->boolean('is_featured');

        return $data;
    }
}
