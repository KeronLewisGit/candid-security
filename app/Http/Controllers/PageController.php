<?php

namespace App\Http\Controllers;

use App\Models\JobPosting;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home', [
            'jobs' => JobPosting::live()->orderByDesc('is_featured')->latest('published_at')->take(3)->get(),
            'openCount' => JobPosting::live()->count(),
        ]);
    }

    public function services(): View
    {
        return view('pages.services', ['services' => config('candid.services')]);
    }

    public function service(string $service): View
    {
        $services = (array) config('candid.services');
        abort_unless(isset($services[$service]), 404);

        return view('pages.service', [
            'key' => $service,
            'service' => $services[$service],
            'others' => array_diff_key($services, [$service => true]),
        ]);
    }

    public function about(): View
    {
        return view('pages.about');
    }

    public function locations(): View
    {
        return view('pages.locations', [
            'openByIsland' => JobPosting::live()->selectRaw('island, count(*) c')->groupBy('island')->pluck('c', 'island'),
        ]);
    }

    public function privacy(): View
    {
        return view('pages.privacy');
    }

    public function credits(): View
    {
        return view('pages.credits', ['credits' => config('photo-credits')]);
    }

    public function sitemapPage(): View
    {
        return view('pages.sitemap', ['jobs' => JobPosting::live()->orderBy('title')->get()]);
    }

    public function sitemap(): Response
    {
        $urls = collect(['home', 'services', 'about', 'locations', 'careers', 'quote', 'pickup', 'contact', 'privacy', 'credits', 'sitemap.page'])
            ->map(fn (string $r) => ['loc' => route($r), 'lastmod' => null])
            ->merge(collect(service_keys())->map(fn (string $k) => ['loc' => route('services.show', $k), 'lastmod' => null]))
            ->merge(JobPosting::live()->get()->map(fn (JobPosting $j) => ['loc' => route('careers.show', $j), 'lastmod' => $j->updated_at->toAtomString()]));

        return response()->view('sitemap', ['urls' => $urls], 200, ['Content-Type' => 'application/xml']);
    }
}
