<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Editable office details (phones, addresses, hours), see SettingsSeeder.
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('position', 30);                       // Application::POSITIONS key
            $table->string('island', 30);                         // config('candid.islands') key
            $table->string('employment_type', 20)->default('full_time');
            $table->string('summary', 600);
            $table->text('description')->nullable();
            $table->text('duties')->nullable();                   // one per line
            $table->text('requirements')->nullable();             // one per line
            $table->unsignedSmallInteger('openings')->default(1);
            $table->string('status', 20)->default('draft');       // draft | open | paused | closed
            $table->boolean('is_featured')->default(false);
            $table->date('closes_on')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'island']);
        });

        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email')->unique();
            $table->string('phone', 40)->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('applicant_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 12)->unique();
            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_posting_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('step')->default(1);      // furthest step saved (1-5)
            $table->timestamp('submitted_at')->nullable();        // null = draft, not in the pipeline

            // Step 1: about you
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email');
            $table->string('phone', 40);
            $table->date('date_of_birth')->nullable();
            $table->string('island', 30)->nullable();             // island of residence
            $table->string('settlement', 120)->nullable();        // town / settlement / area
            $table->string('citizenship', 20)->nullable();        // bahamian | resident | permit | other
            $table->string('emergency_name', 120)->nullable();
            $table->string('emergency_phone', 40)->nullable();

            // Step 2: position + availability
            $table->string('position', 30)->nullable();
            $table->json('work_islands')->nullable();
            $table->string('employment_type', 20)->nullable();    // full_time | part_time | either
            $table->json('shifts')->nullable();                   // day | evening | night | weekends
            $table->date('available_from')->nullable();

            // Step 3: licences + experience
            $table->string('drivers_licence', 20)->nullable();    // none | private | commercial
            $table->boolean('firearm_licence')->default(false);
            $table->boolean('guard_licence')->default(false);     // currently licensed as a security guard
            $table->unsignedTinyInteger('experience_years')->default(0);
            $table->string('last_employer', 160)->nullable();
            $table->string('education', 30)->nullable();
            $table->json('certifications')->nullable();
            $table->text('message')->nullable();

            // Pipeline
            $table->string('status', 20)->default('new');
            $table->timestamp('status_changed_at')->nullable();
            $table->unsignedTinyInteger('exam_score')->nullable(); // percent
            $table->date('exam_taken_on')->nullable();
            $table->string('drug_test_result', 20)->nullable();   // pending | negative | positive | inconclusive
            $table->date('drug_test_on')->nullable();
            $table->string('background_result', 20)->nullable();  // pending | clear | flagged
            $table->unsignedTinyInteger('rating')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 20)->default('website');
            $table->timestamp('consented_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['status', 'submitted_at']);
            $table->index(['island', 'status']);
            $table->index('email');
        });

        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);                           // ApplicationDocument::TYPES key
            $table->string('path');
            $table->string('original_name', 200);
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->string('status', 20)->default('pending');     // pending | accepted | rejected
            $table->string('review_note', 300)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['application_id', 'type']);
        });

        Schema::create('application_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->default('note');          // note | status | email | booking | document | system
            $table->boolean('is_public')->default(false);         // shown on the applicant's timeline
            $table->text('body');
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject');
            $table->text('body');
            $table->string('set_status', 20)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Entrance exams, drug tests and interviews are held in batches; applicants book a seat.
        Schema::create('screening_slots', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20);                            // exam | drug_test | interview
            $table->string('island', 30);
            $table->dateTime('starts_at');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->string('location');
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['kind', 'island', 'starts_at']);
        });

        Schema::create('screening_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screening_slot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->timestamps();
            $table->unique(['application_id', 'kind']);
        });

        Schema::create('applicant_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('link')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['applicant_id', 'read_at']);
        });

        // Client side: guard-service quotes, armoured-transport pickups and general messages.
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 14)->unique();
            $table->string('kind', 20);                            // quote | pickup | contact
            $table->string('status', 20)->default('new');
            $table->string('name', 120);
            $table->string('company', 160)->nullable();
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->string('island', 30)->nullable();
            $table->string('service', 30)->nullable();             // config('candid.services') key
            $table->json('details')->nullable();
            $table->date('needed_on')->nullable();                 // start date / pickup date
            $table->text('message')->nullable();
            $table->text('staff_notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->index(['kind', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['service_requests', 'applicant_notifications', 'screening_bookings', 'screening_slots', 'message_templates',
            'application_notes', 'application_documents', 'applications', 'applicant_password_reset_tokens', 'applicants',
            'job_postings', 'settings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
