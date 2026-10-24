<?php

namespace App\Http\Controllers;

use App\Models\JobPosting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CareerController extends Controller
{
    public function index(Request $request): View
    {
        $island = input_string($request->query('island'));
        $island = array_key_exists($island, (array) config('candid.islands')) ? $island : '';
        $all = JobPosting::live()->orderByDesc('is_featured')->latest('published_at')->get();

        return view('pages.careers', [
            'jobs' => $island ? $all->where('island', $island)->values() : $all,
            'counts' => $all->countBy('island'),
            'total' => $all->count(),
            'island' => $island,
        ]);
    }

    public function show(JobPosting $job): View
    {
        abort_unless($job->isLive() || $job->status === 'paused', 404);

        return view('pages.job', [
            'job' => $job,
            'more' => JobPosting::live()->whereKeyNot($job->id)->where('island', $job->island)->take(3)->get(),
        ]);
    }
}
