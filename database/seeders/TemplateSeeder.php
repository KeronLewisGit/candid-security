<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;

/** Starter emails for each step of guard hiring. Recruiters edit them in Admin → Templates. */
class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (MessageTemplate::query()->exists()) {
            return;
        }

        $templates = [
            ['Documents missing', 'Your Candid application {reference}: documents needed', "Hello {first_name},\n\nThank you for applying for {position}. Before we can move your application forward we need a clear copy of your Police Certificate of Character and a government photo ID.\n\nYou can upload them from your applicant portal: {portal_link}\n\n{recruiter}\n{company}", 'document_check'],
            ['Invite to entrance exam', 'Book your Candid entrance exam', "Hello {first_name},\n\nYour documents have been checked and you are invited to sit the Candid entrance exam.\n\nPlease choose a date in your applicant portal: {portal_link}\n\nBring your photo ID and a pen, and arrive 15 minutes early.\n\n{recruiter}\n{company}", 'entrance_exam'],
            ['Invite to drug test', 'Next step: drug test', "Hello {first_name},\n\nWell done on the entrance exam. The next step is a drug test. Please choose a time in your applicant portal: {portal_link}\n\n{recruiter}\n{company}", 'drug_test'],
            ['Invite to interview', 'Interview invitation: {position}', "Hello {first_name},\n\nWe would like to meet you for an interview for {position} on {island}. Please choose a time in your applicant portal: {portal_link}\n\n{recruiter}\n{company}", 'interview'],
            ['Offer', 'Offer of employment: {position}', "Hello {first_name},\n\nCongratulations. We are pleased to offer you the position of {position}. A member of the team will call you to go through the start date, uniform and training.\n\n{recruiter}\n{company}", 'offer'],
            ['Not successful', 'Your application to Candid Security', "Hello {first_name},\n\nThank you for your interest in {company}. After careful consideration we will not be moving forward with your application ({reference}) at this time. You are welcome to apply again for future openings.\n\n{recruiter}\n{company}", 'rejected'],
        ];

        foreach ($templates as $i => [$name, $subject, $body, $status]) {
            MessageTemplate::create(['name' => $name, 'subject' => $subject, 'body' => $body, 'set_status' => $status, 'sort_order' => $i]);
        }
    }
}
