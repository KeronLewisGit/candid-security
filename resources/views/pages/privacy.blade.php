<x-layouts.site title="Privacy notice" description="How Candid Security collects, uses and protects information from job applicants and clients.">
    <x-page-header title="Privacy notice" eyebrow="Your information" code="PRIVACY" lead="What we collect through this website, why, and how long we keep it." />
    <article class="wrap prose-copy measure mt-12 text-ink-2">
        <p><strong class="text-ink">Draft for review.</strong> This notice describes how this website works. Candid Security should review it with its attorneys before launch.</p>
        <h2 class="text-ink">Job applicants</h2>
        <p>When you apply we collect your contact details, date of birth, island, right to work, emergency contact, the position and shifts you want, your licences, experience and education, and the documents you upload (such as your police certificate, passport or ID, photo, references and permits).</p>
        <p>We use this to assess your application through the steps described on the careers page: document check, entrance exam, drug test, interview and background check. Documents are stored privately and can only be opened by Candid recruitment staff and by you, signed in to your applicant portal.</p>
        <p>We do not ask for your National Insurance number online. If you are hired it is collected in person.</p>
        <p>Applications that do not lead to a job are kept for 12 months so we can contact you about future openings, then deleted. Ask us to delete yours sooner by emailing <a href="mailto:{{ setting('hr_email') }}">{{ setting('hr_email') }}</a>.</p>
        <h2 class="text-ink">Clients and enquiries</h2>
        <p>Quote requests, armoured pickup requests and messages are used only to respond and to provide the service you ask for. We do not ask for cash amounts or account numbers online.</p>
        <h2 class="text-ink">Cookies</h2>
        <p>This site uses one essential cookie to keep forms secure and to keep you signed in to the applicant portal. Your light or dark theme choice is stored in your own browser. There is no advertising or tracking.</p>
        <h2 class="text-ink">Contact</h2>
        <p>Questions about your information: <a href="mailto:{{ setting('general_email') }}">{{ setting('general_email') }}</a>, or call {{ format_phone(setting('freeport_phone')) }}.</p>
    </article>
</x-layouts.site>
