<?php

namespace Tests\Feature;

use App\Models\JobPosting;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_public_page_renders_with_the_shared_header(): void
    {
        JobPosting::factory()->create(['title' => 'Security Officer']);
        $pages = ['/', '/services', '/about', '/locations', '/careers', '/apply', '/request-a-quote', '/armoured-pickup', '/contact', '/privacy', '/photo-credits', '/sitemap', '/portal/login', '/portal/register', '/portal/forgot-password'];
        foreach (array_keys(config('candid.services')) as $slug) {
            $pages[] = '/services/'.$slug;
        }

        foreach ($pages as $url) {
            $response = $this->get($url);
            $response->assertOk();
            // Inner pages all open with <x-page-header>, whose bottom edge is the patrol route.
            if ($url !== '/') {
                $response->assertSee('class="wrap pb-12 pt-10 md:pb-20 md:pt-20"', false);
            }
            $response->assertSee('<meta name="viewport"', false);
            $this->assertMatchesRegularExpression('/<title>[^<]+<\/title>/', $response->getContent(), "{$url} has an empty <title>");
        }
    }

    public function test_home_shows_sourced_facts_and_both_offices(): void
    {
        $this->get('/')
            ->assertSee('242-352-5499')
            ->assertSee('242-361-5499')
            ->assertSee('1997')
            ->assertSee('Armoured transport')
            ->assertSee('Apply online. No more paper forms.');
    }

    public function test_about_page_lists_leadership_and_history(): void
    {
        $this->get('/about')
            ->assertSee('Cleveland A. Duncombe')
            ->assertSee('Marcia Duncombe')
            ->assertSee('Polaris Drive')
            ->assertSee('2002')
            ->assertSee('100% client satisfaction');
    }

    public function test_unknown_service_is_404(): void
    {
        $this->get('/services/bodyguards')->assertNotFound();
    }

    public function test_old_wordpress_urls_redirect(): void
    {
        $this->get('/contact-information-3')->assertRedirect('/contact')->assertStatus(301);
        $this->get('/employment-opportunities-2')->assertRedirect('/careers');
        $this->get('/corporate-info/company-history')->assertRedirect('/about');
        $this->get('/executive-and-management-team')->assertRedirect('/about');
    }

    public function test_careers_filters_by_island_and_hides_drafts(): void
    {
        JobPosting::factory()->create(['title' => 'Freeport Officer', 'island' => 'grand_bahama']);
        JobPosting::factory()->create(['title' => 'Nassau Officer', 'island' => 'new_providence']);
        JobPosting::factory()->create(['title' => 'Secret Draft', 'status' => 'draft']);
        JobPosting::factory()->create(['title' => 'Expired Post', 'closes_on' => now()->subDay()]);

        $this->get('/careers')->assertSee('Freeport Officer')->assertSee('Nassau Officer')->assertDontSee('Secret Draft')->assertDontSee('Expired Post');
        $this->get('/careers?island=new_providence')->assertSee('Nassau Officer')->assertDontSee('Freeport Officer');
        $this->get('/careers?island=mars')->assertOk()->assertSee('Freeport Officer');
    }

    public function test_job_page_has_posting_schema_and_drafts_are_hidden(): void
    {
        $job = JobPosting::factory()->create(['title' => 'Mobile Patrol Officer']);
        $draft = JobPosting::factory()->create(['title' => 'Draft Role', 'status' => 'draft']);

        $this->get(route('careers.show', $job))->assertOk()->assertSee('"@type":"JobPosting"', false)->assertSee(route('apply', ['job' => $job->slug]), false);
        $this->get(route('careers.show', $draft))->assertNotFound();
    }

    public function test_sitemap_lists_services_and_live_jobs(): void
    {
        $job = JobPosting::factory()->create();
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')
            ->assertSee(route('services.show', 'armored-transport'))
            ->assertSee(route('careers.show', $job));
    }

    public function test_office_settings_drive_the_site(): void
    {
        Setting::put('nassau_phone', '242-999-0000');
        Setting::put('freeport_fax', '');

        $this->get('/locations')->assertSee('242-999-0000')->assertDontSee('242-361-5499')->assertDontSee('242-352-5489');
    }

    public function test_preview_mode_adds_noindex_and_banner(): void
    {
        config(['candid.preview' => true]);
        $this->get('/')->assertSee('noindex, nofollow', false)->assertSee('Concept preview')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get('/')->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_photo_credits_cover_every_photo(): void
    {
        $credits = config('photo-credits');
        foreach (config('candid.photos') as $key => $meta) {
            $this->assertArrayHasKey($meta['credit'], $credits, "{$key} has no credit");
            foreach ([480, 960, 1920] as $w) {
                $this->assertFileExists(public_path("img/{$key}-{$w}.webp"));
            }
        }
        foreach (config('candid.services') as $s) {
            $this->assertArrayHasKey($s['photo'], config('candid.photos'));
        }
    }

    public function test_every_photo_on_the_public_pages_exists(): void
    {
        $pages = ['/', '/services', '/about', '/locations', '/careers', '/request-a-quote', '/armoured-pickup', '/contact', '/apply'];
        foreach (service_keys() as $key) {
            $pages[] = '/services/'.$key;
        }
        foreach ($pages as $page) {
            preg_match_all('#/img/[a-z0-9-]+\.webp#', $this->get($page)->assertOk()->getContent(), $m);
            foreach (array_unique($m[0]) as $src) {
                $this->assertFileExists(public_path(ltrim($src, '/')), "{$page} links a missing image");
            }
        }
    }

    public function test_the_logo_and_icons_exist(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        preg_match_all('#href="[^"]*/((?:favicon|apple-touch-icon)[^"/]*\.png)"|src="[^"]*/(img/brand/[^"]+\.png)"#', (string) $html, $m);
        $files = array_values(array_unique(array_filter(array_merge($m[1], $m[2]))));

        $this->assertContains('img/brand/candid-badge.png', $files);
        $this->assertCount(4, $files);
        foreach ($files as $f) {
            $this->assertFileExists(public_path($f));
        }
    }
}
