<?php

/*
|--------------------------------------------------------------------------
| Candid Security Ltd. company facts
|--------------------------------------------------------------------------
| Public copy that is not edited in the admin. Sources: candidbahamas.com
| (company history, services, executive team, mission, core values, employment
| pages and the 2011 paper application, captured October 2026) and The Tribune,
| 24 Aug 2016, "Security company opens new Freeport offices". The website copy
| dates from about 2011, so names, titles and figures must be confirmed with the
| client before launch. Phones, addresses, emails and hours are editable in
| Admin → Settings (read with setting('key'), defaults in App\Models\Setting).
| Nothing here may be invented: unknowns stay neutral or editable.
*/

return [
    // Concept preview on a temporary domain: banner + noindex. Turn off at launch.
    'preview' => (bool) env('CANDID_PREVIEW', false),

    'name' => 'Candid Security Ltd.',
    'short_name' => 'Candid Security',
    'founded' => 1997,
    // Home page of candidbahamas.com, verbatim.
    'tagline' => 'Protecting you, and your business, is our business.',

    // Who receives "new application" and "new request" emails (comma separated).
    'notify' => env('CANDID_NOTIFY_EMAIL'),

    'islands' => [
        'grand_bahama' => ['name' => 'Grand Bahama', 'short' => 'Freeport', 'code' => 'GB'],
        'new_providence' => ['name' => 'New Providence', 'short' => 'Nassau', 'code' => 'NP'],
        'abaco' => ['name' => 'Abaco', 'short' => 'Abaco', 'code' => 'AB'],
        'bimini' => ['name' => 'Bimini', 'short' => 'Bimini', 'code' => 'BI'],
    ],

    /*
     * Services, each with its own page at /services/{slug}. Descriptions follow
     * candidbahamas.com/products-and-services-2 (paraphrased); years from the
     * company history page. "body_photo" sits beside the copy (Candid's own crew photos where they exist).
     */
    'services' => [
        'security-officers' => [
            'name' => 'Security officers',
            'eyebrow' => 'Armed & unarmed guarding',
            'icon' => 'badge',
            'photo' => 'security-guard',
            'card' => 'candid-patrol',
            'body_photo' => 'candid-training',
            'since' => 1997,
            'summary' => 'Uniformed officers posted at your site to keep it visible, controlled and reported on.',
            'body' => [
                'Guarding is where Candid started in 1997, with a team of officers at a single hotel on Grand Bahama. Today uniformed officers keep sites secure across Grand Bahama and Nassau, with cover in Abaco and Bimini.',
                'Officers carry out foot patrols, access control, investigations and incident reporting. Posts are planned around each client: a single night post, weekend cover or round-the-clock teams, armed or unarmed.',
            ],
            'points' => ['Armed or unarmed posts', 'Foot patrols and access control', 'Investigations and incident reports', 'Day, evening, night or 24-hour cover'],
            'industries' => ['Hotels and resorts', 'Retail stores', 'Banks and financial institutions', 'Casinos', 'Construction sites', 'Residential and gated communities', 'Industrial and chemical plants', 'Maritime', 'Aviation and airports'],
        ],
        'armored-transport' => [
            'name' => 'Armoured transport',
            'eyebrow' => 'Cash-in-transit',
            'icon' => 'truck',
            'photo' => 'armored-truck',
            'body_photo' => 'candid-armored',
            'since' => 2002,
            'summary' => 'Armed crews and armoured vehicles moving cash and valuables from your business to the bank.',
            'body' => [
                'Candid launched its armoured division in 2002. Armed, trained crews collect cash and other valuables from your premises in an armoured vehicle and deliver them to your bank or another agreed destination.',
                'Pickups can run on a regular schedule or be booked one at a time. Request a pickup online and operations confirms the time window with you by phone.',
            ],
            'points' => ['Scheduled or one-off pickups', 'Business-to-bank deposits', 'Armed, trained crews', 'Pickup requests online'],
            'industries' => ['Retail stores and supermarkets', 'Restaurants', 'Fuel stations', 'Hotels', 'Any business banking cash'],
        ],
        'mobile-patrol' => [
            'name' => 'Mobile patrol',
            'eyebrow' => 'Patrols & alarm response',
            'icon' => 'car',
            'photo' => 'cctv',
            'body_photo' => 'candid-patrol',
            'since' => 2007,
            'summary' => 'Marked patrol cars, property inspections and alarm response for sites that do not need a full-time post.',
            'body' => [
                'Mobile patrol has been part of Candid since 2007. It gives small and medium-sized businesses the kind of cover usually only larger organisations can justify: dispatched patrol officers, scheduled and random property checks, and alarm response.',
                'Patrols suit closed businesses, construction sites, vacant homes and gated communities, and can be combined with a fixed post on the same property.',
            ],
            'points' => ['Dispatched patrol officers', 'Scheduled and random property inspections', 'Alarm response', 'Written patrol reports'],
            'industries' => ['Small and medium businesses', 'Construction sites', 'Vacant and holiday homes', 'Gated communities'],
        ],
        'aviation-security' => [
            'name' => 'Aviation security',
            'eyebrow' => 'Airports & airlines',
            'icon' => 'plane',
            'photo' => 'airport',
            'body_photo' => null,
            'since' => 2008,
            'summary' => 'Officers for airline and airport operations, including passenger profiling and wheelchair assistance.',
            'body' => [
                'Candid began focusing on aviation security in 2008. Officers support airline and airport operations with duties such as access control, aircraft guarding and airport passenger profiling.',
                'Since 2011 Candid has also provided wheelchair services for airline passengers, to and from the gates at Lynden Pindling International Airport in Nassau.',
            ],
            'points' => ['Airport passenger profiling', 'Aircraft and access-point guarding', 'Wheelchair assistance to and from gates', 'Scheduled around flight times'],
            'industries' => ['Airlines', 'Airports', 'Fixed-base operators', 'Cargo handlers'],
        ],
        'lifeguards' => [
            'name' => 'Lifeguards',
            'eyebrow' => 'Pools & beaches',
            'icon' => 'lifebuoy',
            'photo' => 'lifeguard',
            'body_photo' => 'beach',
            'since' => null,
            'summary' => 'Lifeguards for hotel pools, beaches and events, scheduled alongside your security cover.',
            'body' => [
                'Lifeguards are part of Candid’s guard services. They can be scheduled on their own or alongside security officers on the same property, so one company covers the gate and the water.',
                'Tell us about the pool or beach, the opening hours and any events, and the team will plan the cover.',
            ],
            'points' => ['Hotel and resort pools', 'Beach cover', 'Events', 'One schedule with your security cover'],
            'industries' => ['Hotels and resorts', 'Residential complexes', 'Clubs', 'Events'],
        ],
    ],

    // Company history page + The Tribune (2016). Shown on /about and the home page.
    'history' => [
        ['year' => 1997, 'title' => 'Founded in Freeport', 'body' => 'Cleveland A. Duncombe and Marcia Duncombe start Candid with about 15 officers at one hotel on Grand Bahama.'],
        ['year' => 2002, 'title' => 'Armoured division', 'body' => 'Armoured cash-in-transit launches for banks and businesses.'],
        ['year' => 2007, 'title' => 'Mobile patrol', 'body' => 'Patrol officers, property inspections and alarm response.'],
        ['year' => 2008, 'title' => 'Aviation security', 'body' => 'Candid begins its focus on airline and airport security.'],
        ['year' => 2010, 'title' => 'Nassau', 'body' => 'The company establishes itself in the New Providence market.'],
        ['year' => 2016, 'title' => 'New Freeport head office', 'body' => 'Purpose-built offices open on Polaris Drive, with around 200 staff (The Tribune, August 2016).'],
    ],

    'about' => [
        'Candid Security Ltd. is a privately held Bahamian company, co-founded in 1997 by Cleveland A. Duncombe and Marcia Duncombe. It began with about 15 security officers serving one hotel on Grand Bahama.',
        'Before founding Candid, Cleveland Duncombe served eleven years with the Royal Bahamas Police Force, eight of them with the Criminal Investigation Department in Freeport.',
        'The company added armoured transport in 2002, mobile patrol in 2007 and aviation security in 2008, moved into Nassau in 2010 and has served Abaco and Bimini. In August 2016 it opened purpose-built head offices on Polaris Drive in Freeport.',
    ],

    // candidbahamas.com/corporate-info/mission-statement and /core-values (verbatim).
    'mission' => [
        'statement' => 'Candid Security Ltd. is a service-oriented security company, dedicated to being the premier supplier of an all-inclusive blend of security services to the Bahamas chain of islands.',
        'values' => ['100% client satisfaction', 'Consistent and reliable service', 'Quick management response', 'Officers who convey trust and confidence'],
    ],

    // Training approach (candidbahamas.com/training-3, paraphrased).
    'training' => [
        'Classroom learning, on-the-job training and mentoring',
        'Patrol and patrol techniques',
        'Communication and customer interaction',
        'First aid and CPR',
        'Client-specific training based on a security assessment of each site',
    ],

    // Executive and management team page (verbatim titles, ~2011) and The Tribune (2016). Confirm before launch.
    'leadership' => [
        ['name' => 'Cleveland A. Duncombe', 'role' => 'President & Chief Executive Officer', 'bio' => 'Co-founded Candid in 1997 after eleven years with the Royal Bahamas Police Force, eight of them with CID in Freeport.'],
        ['name' => 'Marcia Duncombe', 'role' => 'Co-founder & Vice-President', 'bio' => 'Co-founded the company in 1997 and serves on the board of directors.'],
    ],
    'management' => [
        ['name' => 'Sean R. Xavier', 'role' => 'Executive Director, Finance & Planning / CFO'],
        ['name' => 'Tasso Sapp', 'role' => 'Senior Manager, Security Operations'],
        ['name' => 'Jacqueline Saunders', 'role' => 'Office / Accounts Manager'],
    ],

    'careers' => [
        // Employment page, verbatim requirements.
        'requirements' => [
            'Be a high school graduate',
            'Have the ability to communicate effectively',
            'Customer service oriented',
            'Strong and commanding personality',
            'Pass a drug screening',
            'Successfully complete an entrance exam',
            'Legally authorised to work in The Bahamas',
        ],
        // Employment page, "We provide". Confirm before launch.
        'benefits' => [
            'Training',
            'Uniforms at no cost to you',
            'Bi-weekly pay by direct deposit',
            'Benefits that exceed the minimum labour standards',
            'Annual bonuses based on individual and company performance',
        ],
    ],

    // Photos in public/img ({key}-{480,960,1920}.webp). Licences in config/photo-credits.php.
    // "ours" = Candid's own photo from candidbahamas.com (small originals: use in cards, never as a hero).
    'photos' => [
        'freeport' => ['w' => 1920, 'h' => 960, 'credit' => 'freeport', 'ours' => false, 'alt' => 'Shopfronts and palms at Port Lucaya Marketplace, Freeport, Grand Bahama'],
        'nassau' => ['w' => 1920, 'h' => 1080, 'credit' => 'nassau', 'ours' => false, 'alt' => 'Nassau Harbour and the Paradise Island bridges, New Providence'],
        'abaco' => ['w' => 1920, 'h' => 1280, 'credit' => 'abaco', 'ours' => false, 'alt' => 'Turquoise water and cays seen from the Hope Town lighthouse, Abaco'],
        'bimini' => ['w' => 1920, 'h' => 1440, 'credit' => 'bimini', 'ours' => false, 'alt' => 'The “Gateway to the Bahamas” arch in Alice Town, Bimini'],
        'airport' => ['w' => 1920, 'h' => 900, 'credit' => 'airport', 'ours' => false, 'alt' => 'Airliner at the gate at Lynden Pindling International Airport, Nassau'],
        'armored-truck' => ['w' => 1920, 'h' => 1280, 'credit' => 'armored-truck', 'ours' => false, 'alt' => 'Armoured cash-in-transit truck on the road'],
        'security-guard' => ['w' => 1920, 'h' => 1316, 'credit' => 'security-guard', 'ours' => false, 'alt' => 'Security officer at an entrance gate at sunset'],
        'cctv' => ['w' => 1920, 'h' => 1440, 'credit' => 'cctv', 'ours' => false, 'alt' => 'Security camera on a building wall'],
        'lifeguard' => ['w' => 1920, 'h' => 1280, 'credit' => 'lifeguard', 'ours' => false, 'alt' => 'Lifeguard tower and warning flags on a beach'],
        'beach' => ['w' => 1920, 'h' => 1440, 'credit' => 'beach', 'ours' => false, 'alt' => 'Taino Beach, Grand Bahama'],
        'bahamas-flag' => ['w' => 1920, 'h' => 1371, 'credit' => 'bahamas-flag', 'ours' => false, 'alt' => 'The Bahamian flag over Nassau Harbour'],
        'candid-armored' => ['w' => 658, 'h' => 496, 'credit' => 'candid-armored', 'ours' => true, 'alt' => 'Candid armoured officers in front of a Candid armoured van'],
        'candid-patrol' => ['w' => 658, 'h' => 496, 'credit' => 'candid-patrol', 'ours' => true, 'alt' => 'Two Candid officers with a Candid mobile patrol car'],
        'candid-training' => ['w' => 600, 'h' => 450, 'credit' => 'candid-training', 'ours' => true, 'alt' => 'Candid officers in a classroom training session'],
    ],

    // First staff account, created by `php artisan db:seed` when both are set.
    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrator'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],
];
