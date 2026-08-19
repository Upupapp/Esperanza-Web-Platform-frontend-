<?php

// Citizen-facing static/promotional content managed from the Web Admin:
// Banners (Communications > Banners), Privacy Policy, Terms & Conditions,
// and Support contact info (Settings > Site Content). Same category as
// config/esperanza.php — shared display data consumed by Blade views, not
// a database-backed model. Editing these values from the admin screens is
// a frontend preview only, same as the rest of this admin's mock CRUD.

return [

    // -----------------------------------------------------------------
    // Banners — rotating promotional/announcement banners shown at the
    // top of the Citizen Portal dashboard, similar to the promo-banner
    // pattern used in consumer apps like GCash. Only 'Approved' banners
    // within their active date range are shown to citizens.
    // -----------------------------------------------------------------
    'banners' => [
        [
            'id' => 'BNR-2026-001',
            'title' => 'Fiesta ng Esperanza 2026',
            'subtitle' => 'Join the week-long celebration — opening program starts August 14 at the Municipal Plaza.',
            'image' => 'esperanza_festival.jpg',
            'cta_label' => 'View Schedule',
            'cta_route' => 'citizen.events',
            'theme' => 'from-gold-500 to-orange-600',
            'status' => 'Approved',
            'audience' => 'Municipal',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-21',
            'order' => 1,
            'created_by' => 'Corazon Villareal',
            'last_updated' => '2026-07-28',
        ],
        [
            'id' => 'BNR-2026-002',
            'title' => 'Request Your Documents Online',
            'subtitle' => 'Skip the line — apply for Barangay Clearance, Certificate of Residency, and more through Dokyu.',
            'image' => 'rectangle_cityhall.jpg',
            'cta_label' => 'Request Now',
            'cta_route' => 'citizen.document-requests',
            'theme' => 'from-brand-600 to-navy-800',
            'status' => 'Approved',
            'audience' => 'Municipal',
            'start_date' => '2026-06-01',
            'end_date' => '2026-12-31',
            'order' => 2,
            'created_by' => 'Paolo Reyes',
            'last_updated' => '2026-06-01',
        ],
        [
            'id' => 'BNR-2026-003',
            'title' => 'Tulong Assistance Programs Now Open',
            'subtitle' => 'Medical, burial, educational, and livelihood assistance applications are open for Q3 2026.',
            'image' => 'esperanza_community1.jpg',
            'cta_label' => 'Learn More',
            'cta_route' => 'citizen.assistance-requests',
            'theme' => 'from-emerald-600 to-teal-700',
            'status' => 'Approved',
            'audience' => 'Municipal',
            'start_date' => '2026-07-01',
            'end_date' => '2026-09-30',
            'order' => 3,
            'created_by' => 'Corazon Villareal',
            'last_updated' => '2026-07-10',
        ],
        [
            'id' => 'BNR-2026-004',
            'title' => 'Rainy Season Safety Advisory',
            'subtitle' => 'Know your barangay\'s evacuation center and stay updated through Sakuna alerts.',
            'image' => 'rectangle_ocean.jpg',
            'cta_label' => 'Safety Tips',
            'cta_route' => 'citizen.announcements',
            'theme' => 'from-cyan-700 to-navy-900',
            'status' => 'Approved',
            'audience' => 'Municipal',
            'start_date' => '2026-06-01',
            'end_date' => '2026-10-31',
            'order' => 4,
            'created_by' => 'Paolo Reyes',
            'last_updated' => '2026-06-01',
        ],
        [
            'id' => 'BNR-2026-005',
            'title' => 'Founding Anniversary Fun Run — Save the Date',
            'subtitle' => 'Registration opens next month. Early sign-ups get a free event shirt.',
            'image' => 'esperanza-rock.jpg',
            'cta_label' => 'Notify Me',
            'cta_route' => 'citizen.announcements',
            'theme' => 'from-purple-600 to-navy-800',
            'status' => 'Draft',
            'audience' => 'Municipal',
            'start_date' => '2026-09-15',
            'end_date' => '2026-10-05',
            'order' => 5,
            'created_by' => 'Corazon Villareal',
            'last_updated' => '2026-08-05',
        ],
    ],

    // -----------------------------------------------------------------
    // Privacy Policy — structured as sections so the admin editor can
    // add/edit/reorder/remove individual sections rather than one giant
    // text block.
    // -----------------------------------------------------------------
    'privacy_policy' => [
        'title' => 'Privacy Policy',
        'effective_date' => '2026-01-15',
        'last_updated' => '2026-07-01',
        'last_updated_by' => 'Paolo Reyes',
        'intro' => 'The Municipality of Esperanza, Masbate ("the Municipality," "we," "us") is committed to protecting the privacy of every citizen who uses the Esperanza Web Platform and Esperanza Mobile app ("the Platform"). This Privacy Policy explains what information we collect, how we use it, and the rights you have over your personal data under the Data Privacy Act of 2012 (Republic Act No. 10173).',
        'sections' => [
            [
                'heading' => '1. Information We Collect',
                'body' => "We collect information you provide directly when you register for an account or use a service, including your full name, date of birth, sex, civil status, contact number, email address, home address, barangay and sitio/purok, and any supporting documents you upload for a Dokyu or Tulong request (such as valid IDs, certificates, or photos). We also collect information generated through your use of the Platform, such as request reference numbers, status history, and timestamps of your submissions.",
            ],
            [
                'heading' => '2. How We Use Your Information',
                'body' => "Your information is used solely to process your document and assistance requests, verify your identity and residency, maintain accurate constituent records, respond to support inquiries, and send you notifications about your requests and municipal announcements. We do not use your personal data for commercial advertising, and we do not sell your data to any third party.",
            ],
            [
                'heading' => '3. Data Sharing and Disclosure',
                'body' => "Your information is shared only with the specific municipal or barangay office relevant to your request (for example, the Office of the Municipal Civil Registrar for civil registry documents, or MSWDO for assistance programs). We may disclose information when required by law, court order, or a lawful request from a government authority. We do not share your data with private third parties for marketing purposes.",
            ],
            [
                'heading' => '4. Data Retention',
                'body' => "We retain your personal data for as long as your citizen account remains active and for the period required by applicable records-retention laws and LGU administrative guidelines thereafter. Document requests and assistance records are retained as part of the official municipal archive in accordance with the National Archives of the Philippines' retention schedules.",
            ],
            [
                'heading' => '5. Your Rights Under the Data Privacy Act',
                'body' => "As a data subject, you have the right to be informed, the right to access your personal data, the right to object to processing, the right to correct inaccurate data, the right to erasure or blocking under certain conditions, the right to data portability, and the right to file a complaint with the National Privacy Commission. You may exercise these rights by contacting our Data Protection Officer through the Support channels listed on the Platform.",
            ],
            [
                'heading' => '6. Data Security',
                'body' => "We implement reasonable organizational, physical, and technical safeguards to protect your personal data against unauthorized access, alteration, disclosure, or destruction, consistent with the National Privacy Commission's security guidelines for government agencies.",
            ],
            [
                'heading' => '7. Children\'s Information',
                'body' => "Where a resident profile includes information about a minor (for example, as a family member in a household record), such information is collected and processed only for legitimate civil registry and social welfare purposes, with appropriate safeguards, and is never used for any purpose beyond delivering municipal services.",
            ],
            [
                'heading' => '8. Changes to This Policy',
                'body' => "We may update this Privacy Policy from time to time to reflect changes in our practices or legal requirements. The \"Last Updated\" date at the top of this page indicates when the policy was last revised. Continued use of the Platform after changes take effect constitutes acceptance of the revised policy.",
            ],
            [
                'heading' => '9. Contact Us',
                'body' => "For questions, concerns, or requests regarding your personal data, please contact the Municipality of Esperanza's Data Protection Officer through the ICT Office at (056) 333-1099 or support@esperanza.gov.ph, or visit the Municipal Hall, Barangay Poblacion, Esperanza, Masbate.",
            ],
        ],
    ],

    // -----------------------------------------------------------------
    // Terms and Conditions — same structured-section shape as the
    // Privacy Policy above.
    // -----------------------------------------------------------------
    'terms_conditions' => [
        'title' => 'Terms and Conditions',
        'effective_date' => '2026-01-15',
        'last_updated' => '2026-07-01',
        'last_updated_by' => 'Paolo Reyes',
        'intro' => 'These Terms and Conditions ("Terms") govern your access to and use of the Esperanza Web Platform and Esperanza Mobile app ("the Platform"), operated by the Municipality of Esperanza, Masbate. By creating an account or using any service on the Platform, you agree to be bound by these Terms.',
        'sections' => [
            [
                'heading' => '1. Acceptance of Terms',
                'body' => "By registering for a Citizen Portal account or otherwise accessing the Platform, you confirm that you have read, understood, and agree to these Terms and our Privacy Policy. If you do not agree, please do not use the Platform.",
            ],
            [
                'heading' => '2. Eligibility and Account Registration',
                'body' => "The Platform is intended for residents of the Municipality of Esperanza, Masbate. You must provide accurate, current, and complete information during registration and keep your account information updated. You are responsible for maintaining the confidentiality of your login credentials and for all activity that occurs under your account.",
            ],
            [
                'heading' => '3. Use of Services',
                'body' => "The Platform allows you to submit Dokyu (document) and Tulong (assistance) requests, report incidents through Sakuna, view municipal announcements and events, and communicate with LGU personnel regarding your requests. Services are provided subject to verification, applicable fees, and processing timelines set by the responsible municipal or barangay office.",
            ],
            [
                'heading' => '4. User Responsibilities and Prohibited Conduct',
                'body' => "You agree to use the Platform only for lawful purposes and not to: submit false or fraudulent information or documents; impersonate another person; attempt to gain unauthorized access to another citizen's account or LGU systems; upload harmful files or attempt to disrupt the Platform's operation; or use the Platform to harass LGU personnel or other citizens. Violation of these Terms may result in suspension or termination of your account and, where applicable, referral to appropriate authorities.",
            ],
            [
                'heading' => '5. Document and Payment Processing',
                'body' => "Processing times and fees for Dokyu and Tulong requests are set by the responsible municipal or barangay office and are subject to change without prior notice, though we will make reasonable efforts to keep published fees and timelines current. Payment confirmations, once verified, are final; refunds for erroneous or duplicate payments are handled on a case-by-case basis by the Municipal Treasurer's Office.",
            ],
            [
                'heading' => '6. Intellectual Property',
                'body' => "All content on the Platform — including the Esperanza municipal seal, logos, layouts, and original text — is the property of the Municipality of Esperanza and may not be reproduced or used for commercial purposes without written consent, except as necessary for your own personal use of municipal services.",
            ],
            [
                'heading' => '7. Service Availability and Limitations',
                'body' => "We strive to keep the Platform available and accurate, but we do not guarantee uninterrupted access. The Platform may be temporarily unavailable for maintenance, upgrades, or reasons beyond our control. In such cases, citizens may transact in person at the Municipal Hall or their barangay hall.",
            ],
            [
                'heading' => '8. Termination of Access',
                'body' => "We reserve the right to suspend or terminate your account if we reasonably believe you have violated these Terms, provided false information, or misused the Platform. You may also request the deactivation of your own account at any time through Support.",
            ],
            [
                'heading' => '9. Limitation of Liability',
                'body' => "The Municipality of Esperanza is not liable for delays, errors, or service interruptions arising from circumstances beyond its reasonable control, including but not limited to power outages, internet connectivity issues, natural disasters, or third-party payment gateway failures.",
            ],
            [
                'heading' => '10. Governing Law',
                'body' => "These Terms are governed by the laws of the Republic of the Philippines, including the Local Government Code, the Data Privacy Act of 2012, and other applicable national and local issuances.",
            ],
            [
                'heading' => '11. Changes to These Terms',
                'body' => "We may revise these Terms from time to time. Material changes will be reflected by an updated \"Last Updated\" date. Your continued use of the Platform after such changes constitutes your acceptance of the revised Terms.",
            ],
            [
                'heading' => '12. Contact Information',
                'body' => "For questions about these Terms, please contact the ICT Office at (056) 333-1099, support@esperanza.gov.ph, or visit the Municipal Hall, Barangay Poblacion, Esperanza, Masbate.",
            ],
        ],
    ],

    // -----------------------------------------------------------------
    // Support — the contact channels and office-hours block shown on the
    // Citizen Portal's Help & Support page.
    // -----------------------------------------------------------------
    'support' => [
        'intro' => 'Need help with a request or running into an issue? Reach us through any of the channels below, or submit a support ticket.',
        'channels' => [
            ['key' => 'phone', 'icon' => 'phone', 'label' => 'Call the ICT Office', 'label_fil' => 'Tawagan ang ICT Office', 'value' => '(056) 333-1099', 'color' => 'text-brand-600 bg-brand-50'],
            ['key' => 'email', 'icon' => 'mail', 'label' => 'Email Support', 'label_fil' => 'Mag-email sa Suporta', 'value' => 'support@esperanza.gov.ph', 'color' => 'text-purple-600 bg-purple-50'],
            ['key' => 'visit', 'icon' => 'map-pin', 'label' => 'Visit Us', 'label_fil' => 'Bisitahin Kami', 'value' => 'Municipal Hall, Brgy. Poblacion, Esperanza', 'color' => 'text-emerald-600 bg-emerald-50'],
        ],
        'office_hours' => 'Monday – Friday, 8:00 AM – 5:00 PM',
        'office_hours_fil' => 'Lunes – Biyernes, 8:00 AM – 5:00 PM',
        'last_updated' => '2026-06-15',
        'last_updated_by' => 'Paolo Reyes',
    ],
];
