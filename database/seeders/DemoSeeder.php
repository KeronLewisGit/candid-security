<?php

namespace Database\Seeders;

use App\Models\Applicant;
use App\Models\Application;
use App\Models\JobPosting;
use App\Models\ScreeningSlot;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Pitch demo data: php artisan migrate:fresh --seeder=DemoSeeder
 * Job postings, applicants and client requests are illustrative only (no real
 * people or clients). Logins: admin@candid.test, hr@candid.test, ops@candid.test,
 * applicant@candid.test, all with the password "password".
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(TemplateSeeder::class);

        User::updateOrCreate(['email' => 'admin@candid.test'], ['name' => 'Demo Admin', 'password' => Hash::make('password'), 'role' => 'admin', 'is_active' => true]);
        $hr = User::updateOrCreate(['email' => 'hr@candid.test'], ['name' => 'Demo Recruiter', 'password' => Hash::make('password'), 'role' => 'recruiter', 'is_active' => true]);
        $ops = User::updateOrCreate(['email' => 'ops@candid.test'], ['name' => 'Demo Operations', 'password' => Hash::make('password'), 'role' => 'operations', 'is_active' => true]);

        $requirements = implode("\n", (array) config('candid.careers.requirements'));
        $jobs = collect([
            ['Security Officer (Unarmed)', 'guard_unarmed', 'grand_bahama', 'full_time', 6, true, 'Uniformed officers for commercial and industrial sites around Freeport. Day and night rotations.', "Control access and keep visitor logs\nPatrol the site on a set schedule\nWrite clear incident reports\nHand over to the next shift"],
            ['Armed Security Officer', 'guard_armed', 'new_providence', 'full_time', 3, true, 'Armed posts in Nassau for officers who hold, or can obtain, the required firearm licensing.', "Staff armed posts at client sites\nFollow firearm handling and storage procedures\nRespond to incidents and alarms"],
            ['Armoured Transport Crew Member', 'armored', 'grand_bahama', 'full_time', 2, false, 'Join a cash-in-transit crew covering business-to-bank pickups on Grand Bahama.', "Collect and deliver cash and valuables\nFollow route and handover procedures\nKeep accurate custody records"],
            ['Mobile Patrol Officer', 'patrol', 'new_providence', 'full_time', 2, false, 'Drive scheduled and random patrols of client properties, mostly overnight.', "Check properties on a patrol schedule\nRespond to alarms\nFile patrol reports each shift"],
            ['Lifeguard', 'lifeguard', 'grand_bahama', 'seasonal', 4, false, 'Pool and beach lifeguards for hotel and event clients.', "Supervise swimmers\nRespond to emergencies\nKeep the pool or beach area safe"],
            ['Security Officer', 'guard_unarmed', 'abaco', 'part_time', 2, false, 'Part-time officers for sites in Abaco.', "Control access\nPatrol the site\nReport incidents"],
        ])->map(fn (array $j) => JobPosting::updateOrCreate(['title' => $j[0], 'island' => $j[2]], [
            'position' => $j[1], 'employment_type' => $j[3], 'openings' => $j[4], 'is_featured' => $j[5], 'summary' => $j[6],
            'duties' => $j[7], 'requirements' => $requirements, 'status' => 'open', 'published_at' => now()->subDays(rand(2, 20)),
        ]));

        // Screening sessions over the next three weeks, Freeport and Nassau.
        if (! ScreeningSlot::query()->exists()) {
            foreach ([['exam', 'grand_bahama', 'Candid Freeport office', 20], ['exam', 'new_providence', 'Candid Nassau office', 20], ['drug_test', 'grand_bahama', 'Candid Freeport office', 8], ['interview', 'grand_bahama', 'Candid Freeport office', 1], ['interview', 'new_providence', 'Candid Nassau office', 1]] as $i => [$kind, $island, $where, $cap]) {
                for ($d = 0; $d < 3; $d++) {
                    ScreeningSlot::create([
                        'kind' => $kind, 'island' => $island, 'location' => $where, 'capacity' => $cap, 'created_by' => $hr->id,
                        'starts_at' => now()->addDays(3 + $d * 7 + $i % 3)->setTime($kind === 'interview' ? 10 + $d : 9, 0),
                        'duration_minutes' => ['exam' => 90, 'drug_test' => 30, 'interview' => 45][$kind],
                    ]);
                }
            }
        }

        // The demo applicant: documents accepted, invited to the entrance exam, slots available to book.
        $demo = Applicant::updateOrCreate(['email' => 'applicant@candid.test'], ['first_name' => 'Jordan', 'last_name' => 'Demo', 'phone' => '242-555-0142', 'password' => Hash::make('password')]);
        if (! $demo->applications()->exists()) {
            $app = $this->application($demo, $jobs[0], 'entrance_exam', 5, ['island' => 'grand_bahama', 'settlement' => 'Freeport']);
            foreach (['police_certificate', 'passport'] as $type) {
                $this->document($app, $type, 'accepted', $hr);
            }
            $app->notes()->create(['type' => 'status', 'is_public' => true, 'body' => 'Checking your documents', 'created_at' => now()->subDays(4)]);
            $app->notes()->create(['type' => 'document', 'is_public' => true, 'body' => 'Police Character Certificate accepted', 'created_at' => now()->subDays(3)]);
            $app->notes()->create(['type' => 'status', 'is_public' => true, 'body' => 'Entrance exam', 'created_at' => now()->subDays(1)]);
            $demo->inbox()->create(['application_id' => $app->id, 'title' => 'Next step: entrance exam', 'body' => 'You are invited to sit the Candid entrance exam. Choose a date in your portal.', 'link' => route('portal.applications.show', $app->reference)]);
        }

        // A second, unfinished application saved up to "Review & send", so every step of the
        // online form can be walked through (and shown) without sending anything.
        if (! $demo->applications()->whereNull('submitted_at')->exists()) {
            $draft = new Application([
                'first_name' => 'Jordan', 'last_name' => 'Demo', 'email' => $demo->email, 'phone' => '242-555-0142',
                'date_of_birth' => now()->subYears(27)->subDays(55)->toDateString(), 'island' => 'grand_bahama', 'settlement' => 'Freeport', 'citizenship' => 'bahamian',
                'emergency_name' => 'Next of kin', 'emergency_phone' => '242-555-0100', 'position' => $jobs[2]->position,
                'work_islands' => ['grand_bahama'], 'employment_type' => 'full_time', 'shifts' => ['day', 'evening'], 'available_from' => now()->addWeeks(2)->toDateString(),
                'drivers_licence' => 'commercial', 'firearm_licence' => false, 'guard_licence' => true, 'experience_years' => 3,
                'last_employer' => 'Sample Employer Ltd.', 'education' => 'bgcse', 'certifications' => ['first_aid', 'security_course'], 'step' => 5,
            ]);
            $draft->applicant_id = $demo->id;
            $draft->job_posting_id = $jobs[2]->id;
            $draft->save();
            $this->document($draft, 'police_certificate', 'pending', $hr);
            $this->document($draft, 'passport', 'pending', $hr);
        }

        // A spread of illustrative applicants across stages and islands.
        if (Application::query()->count() < 5) {
            $names = [['Andre', 'Rolle'], ['Keisha', 'Bethel'], ['Marvin', 'Ferguson'], ['Tamika', 'Johnson'], ['Delano', 'Smith'], ['Shanice', 'Pinder'], ['Romeo', 'Cartwright'], ['Latoya', 'Knowles'], ['Jamal', 'Saunders'], ['Crystal', 'Moss'], ['Dwight', 'Thompson'], ['Nadia', 'Higgs'], ['Kevin', 'Munnings'], ['Sasha', 'Gibson']];
            $stages = ['new', 'new', 'new', 'document_check', 'document_check', 'entrance_exam', 'drug_test', 'interview', 'interview', 'background_check', 'offer', 'hired', 'rejected', 'new'];
            foreach ($names as $i => [$first, $last]) {
                $job = $jobs[$i % $jobs->count()];
                $applicant = Applicant::create(['first_name' => $first, 'last_name' => $last, 'email' => strtolower($first.'.'.$last).'@example.com', 'phone' => '242-555-01'.str_pad((string) ($i + 10), 2, '0', STR_PAD_LEFT), 'password' => Hash::make(str()->random(16))]);
                $a = $this->application($applicant, $job, $stages[$i], $i % 9 + 1, ['island' => $job->island, 'settlement' => $job->island === 'new_providence' ? 'Nassau' : ($job->island === 'abaco' ? 'Marsh Harbour' : 'Freeport')]);
                $a->forceFill(['assigned_to' => $i % 3 ? $hr->id : null, 'rating' => $i % 4 ? ($i % 5) + 1 : null])->save();
                if ($i % 4 !== 0) {
                    $this->document($a, 'police_certificate', $stages[$i] === 'new' ? 'pending' : 'accepted', $hr);
                }
                if ($i % 3 !== 0) {
                    $this->document($a, 'passport', $stages[$i] === 'new' ? 'pending' : 'accepted', $hr);
                }
                if (in_array($stages[$i], ['drug_test', 'interview', 'background_check', 'offer', 'hired'], true)) {
                    $a->forceFill(['exam_score' => 64 + ($i * 7) % 33, 'exam_taken_on' => now()->subDays(6)])->save();
                }
                if (in_array($stages[$i], ['interview', 'background_check', 'offer', 'hired'], true)) {
                    $a->forceFill(['drug_test_result' => 'negative', 'drug_test_on' => now()->subDays(4)])->save();
                }
                if (in_array($stages[$i], ['offer', 'hired'], true)) {
                    $a->forceFill(['background_result' => 'clear'])->save();
                }
            }
            // One unfinished draft, so the "drafts" figure is not zero.
            $draftOwner = Applicant::create(['first_name' => 'Trevor', 'last_name' => 'Major', 'email' => 'trevor.major@example.com', 'password' => Hash::make(str()->random(16))]);
            $draft = new Application(['first_name' => 'Trevor', 'last_name' => 'Major', 'email' => $draftOwner->email, 'phone' => '242-555-0199', 'island' => 'bimini', 'position' => 'guard_unarmed', 'step' => 3]);
            $draft->applicant_id = $draftOwner->id;
            $draft->save();
        }

        // Client side: quotes, pickups and messages, two of them unanswered for over a day.
        if (! ServiceRequest::query()->exists()) {
            $requests = [
                ['quote', 'new', 30, 'Retail store manager', 'Sample Retail Ltd.', 'grand_bahama', ['site_type' => 'retail', 'coverage' => 'unarmed', 'posts' => 2, 'hours' => 'night', 'days' => 7, 'duration' => 'ongoing']],
                ['quote', 'new', 52, 'Property manager', 'Sample Gated Community', 'new_providence', ['site_type' => 'residential', 'coverage' => 'both', 'posts' => 3, 'hours' => '24h', 'days' => 7, 'duration' => 'ongoing']],
                ['quote', 'new', 3, 'Event organiser', 'Sample Events Co.', 'grand_bahama', ['site_type' => 'event', 'coverage' => 'unarmed', 'posts' => 8, 'hours' => 'event', 'days' => 2, 'duration' => 'one_off']],
                ['quote', 'contacted', 70, 'Operations lead', 'Sample Warehouse Ltd.', 'grand_bahama', ['site_type' => 'industrial', 'coverage' => 'armed', 'posts' => 1, 'hours' => 'night', 'days' => 7, 'duration' => 'ongoing']],
                ['quote', 'quoted', 160, 'Office manager', 'Sample Offices', 'new_providence', ['site_type' => 'commercial', 'coverage' => 'unarmed', 'posts' => 1, 'hours' => 'day', 'days' => 5, 'duration' => 'ongoing']],
                ['pickup', 'new', 5, 'Store owner', 'Sample Pharmacy', 'grand_bahama', ['pickup_address' => 'East Mall Drive, Freeport', 'destination' => 'Bank branch, Freeport', 'window' => 'afternoon', 'frequency' => 'weekly', 'existing_client' => 'no']],
                ['pickup', 'confirmed', 26, 'Finance clerk', 'Sample Restaurant Group', 'new_providence', ['pickup_address' => 'Bay Street, Nassau', 'destination' => 'Bank branch, Nassau', 'window' => 'morning', 'frequency' => 'daily', 'existing_client' => 'yes']],
                ['contact', 'new', 2, 'Website visitor', null, 'abaco', []],
            ];
            foreach ($requests as $i => $row) {
                [$kind, $status, $hoursAgo, $name, $company, $island, $details] = $row;
                $isNew = in_array($status, ['new'], true);
                $r = ServiceRequest::create([
                    'kind' => $kind, 'name' => $name, 'company' => $company, 'email' => 'client'.($i + 1).'@example.com', 'phone' => '242-555-02'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                    'island' => $island, 'service' => ['pickup' => 'armored-transport', 'quote' => 'security-officers', 'contact' => null][$kind], 'details' => $details ?: null,
                    'needed_on' => in_array($kind, ['pickup'], true) ? today()->addDays($i % 3) : null,
                    'message' => in_array($kind, ['contact'], true) ? 'Do you cover Marsh Harbour for a short-term site?' : null,
                    'assigned_to' => $isNew ? null : $ops->id,
                ]);
                $r->forceFill(['status' => $status, 'created_at' => now()->subHours($hoursAgo), 'status_changed_at' => now()->subHours(max(1, $hoursAgo - 2)), 'responded_at' => $isNew ? null : now()->subHours(max(1, $hoursAgo - 4))])->save();
            }
        }
    }

    /** @param array<string, string> $extra */
    private function application(Applicant $applicant, JobPosting $job, string $status, int $daysAgo, array $extra): Application
    {
        $a = new Application([
            'first_name' => $applicant->first_name, 'last_name' => $applicant->last_name, 'email' => $applicant->email, 'phone' => (string) $applicant->phone,
            'date_of_birth' => now()->subYears(22 + $daysAgo)->subDays($daysAgo * 11)->toDateString(), 'citizenship' => 'bahamian', 'emergency_name' => 'Next of kin', 'emergency_phone' => '242-555-0100', 'position' => $job->position,
            'work_islands' => [$job->island], 'employment_type' => 'full_time', 'shifts' => ['day', 'night'], 'drivers_licence' => $daysAgo % 2 ? 'private' : 'none',
            'firearm_licence' => $job->position === 'guard_armed', 'experience_years' => $daysAgo % 6, 'education' => 'bgcse', 'certifications' => $daysAgo % 2 ? ['first_aid'] : [], 'step' => 5,
        ] + $extra);
        $a->applicant_id = $applicant->id;
        $a->job_posting_id = $job->id;
        $a->save();
        $a->forceFill(['submitted_at' => now()->subDays($daysAgo), 'consented_at' => now()->subDays($daysAgo), 'status' => $status, 'status_changed_at' => now()->subDays(max(0, $daysAgo - 2))->subHours(3), 'created_at' => now()->subDays($daysAgo)])->save();
        $a->notes()->create(['type' => 'system', 'is_public' => true, 'body' => 'Application submitted online', 'created_at' => now()->subDays($daysAgo)]);

        return $a;
    }

    private function document(Application $application, string $type, string $status, User $by): void
    {
        $path = 'applications/'.$application->id.'/'.$type.'-sample.pdf';
        Storage::disk('local')->put($path, "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[]/Count 0>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF");
        $application->documents()->create([
            'type' => $type, 'path' => $path, 'original_name' => $type.'-sample.pdf', 'mime' => 'application/pdf', 'size' => 120,
            'status' => $status, 'reviewed_by' => $status === 'pending' ? null : $by->id, 'reviewed_at' => $status === 'pending' ? null : now()->subDays(2),
        ]);
    }
}
