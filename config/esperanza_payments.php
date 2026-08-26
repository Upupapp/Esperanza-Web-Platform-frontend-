<?php

// Dokyu/Tulong payment records — the single source of truth for
// admin/payments.blade.php and admin/papeles.blade.php alike (the latter
// surfaces a citizen's own receipts as "Resibo" documents), so a payment
// only ever exists in one place instead of two blade files each keeping
// their own copy that could drift apart.
return [
    ['ref' => 'DR-2026-2210', 'citizen' => 'Maria Fe Bacaltos', 'barangay' => 'Poblacion', 'type' => 'Dokyu', 'item' => 'Cedula (Community Tax Certificate)', 'amount' => '₱32.00', 'method' => null, 'receipt' => null, 'status' => 'Under Review', 'date' => 'Jul 8, 2026'],
    ['ref' => 'DR-2026-2214', 'citizen' => 'Rosemarie Tan', 'barangay' => 'Baras', 'type' => 'Dokyu', 'item' => 'Business Permit (New)', 'amount' => '₱1,250.00', 'method' => 'PayMaya', 'receipt' => 'PayMaya Receipt.png', 'status' => 'Pending Review', 'date' => 'Jul 10, 2026'],
    ['ref' => 'DR-2026-2187', 'citizen' => 'Maria Fe Bacaltos', 'barangay' => 'Poblacion', 'type' => 'Dokyu', 'item' => 'Barangay Clearance', 'amount' => '₱50.00', 'method' => 'Payment in Center', 'receipt' => 'OR-88214.jpg', 'status' => 'Approved', 'date' => 'Jul 3, 2026'],
    ['ref' => 'DR-2026-2154', 'citizen' => 'Corazon Villareal', 'barangay' => 'Iligan', 'type' => 'Dokyu', 'item' => 'Certificate of Residency', 'amount' => '₱50.00', 'method' => 'GCash', 'receipt' => 'GCash Receipt.jpg', 'status' => 'Approved', 'date' => 'Jun 29, 2026'],
    ['ref' => 'DR-2026-1655', 'citizen' => 'Rosemarie Tan', 'barangay' => 'Baras', 'type' => 'Dokyu', 'item' => 'Business Permit Renewal', 'amount' => '₱980.00', 'method' => 'GCash', 'receipt' => 'GCash Receipt (blurred).jpg', 'status' => 'Rejected', 'date' => 'May 12, 2026'],
    ['ref' => 'DR-2026-1490', 'citizen' => 'Michael Bacus', 'barangay' => 'Iligan', 'type' => 'Dokyu', 'item' => 'Real Property Tax Clearance', 'amount' => '₱100.00', 'method' => 'GCash', 'receipt' => 'GCash Receipt 2.jpg', 'status' => 'Approved', 'date' => 'Apr 20, 2026'],
    ['ref' => 'AR-2026-0037', 'citizen' => 'Josefina Marbella', 'barangay' => 'Baras', 'type' => 'Tulong', 'item' => 'Social Pension — Documentary Fee', 'amount' => '₱100.00', 'method' => 'Payment in Center', 'receipt' => 'OR-77031.jpg', 'status' => 'Approved', 'date' => 'Feb 10, 2026'],
    // Cristy Bonghanoy — every fee-bearing Dokyu type on her own account has
    // its fee already settled (mobile lets a citizen pay at submission,
    // before LGU staff even opens the request — see request status staying
    // Submitted in admin/document-requests.blade.php), each reflected in
    // that request's own process modal via the shared
    // components/admin/payment-receipt.blade.php, not just here. Three
    // types are free per the mobile catalog (Certificate of Indigency,
    // First Time Jobseeker Certificate, Senior Citizen ID / OSCA
    // membership) and deliberately have no payment record — nothing was
    // ever due.
    //
    // 'receipt' filenames follow the Esperanza Mobile app's own actual
    // export naming exactly — see receiptFilename() in
    // lib/utils/receipt_export.dart of the Esperanza-Mobile-App source:
    // `Esperanza_<Method>_<Service>_<RequestRef>.png`, where Method is
    // GCash/Maya/Onsite (the app's own internal ReceiptType labels — Maya,
    // not the admin-side "PayMaya" display label; Onsite, not "Payment in
    // Center") and both Service and RequestRef are run through the app's
    // own sanitize() (every run of non-alphanumeric characters collapsed to
    // a single underscore), which is why parenthesized service names below
    // end in a trailing underscore — that's the real app's own output, not
    // a typo.
    ['ref' => 'DR-2026-2233', 'citizen' => 'Cristy Pareja Bonghanoy', 'barangay' => 'Baras', 'type' => 'Dokyu', 'item' => 'Barangay Business Clearance', 'amount' => '₱100.00', 'method' => 'GCash', 'receipt' => 'Esperanza_GCash_Barangay_Business_Clearance_DR_2026_2233.png', 'status' => 'Approved', 'date' => 'Aug 24, 2026'],
    ['ref' => 'DR-2026-2255', 'citizen' => 'Cristy Pareja Bonghanoy', 'barangay' => 'Baras', 'type' => 'Dokyu', 'item' => 'Barangay Clearance', 'amount' => '₱50.00', 'method' => 'PayMaya', 'receipt' => 'Esperanza_Maya_Barangay_Clearance_DR_2026_2255.png', 'status' => 'Approved', 'date' => 'Aug 24, 2026'],
    ['ref' => 'DR-2026-2312', 'citizen' => 'Cristy Pareja Bonghanoy', 'barangay' => 'Baras', 'type' => 'Dokyu', 'item' => 'Barangay Certification (General Purpose)', 'amount' => '₱50.00', 'method' => 'Payment in Center', 'receipt' => 'Esperanza_Onsite_Barangay_Certification_General_Purpose_DR_2026_2312.png', 'status' => 'Approved', 'date' => 'Aug 24, 2026'],
    ['ref' => 'DR-2026-2350', 'citizen' => 'Cristy Pareja Bonghanoy', 'barangay' => 'Baras', 'type' => 'Dokyu', 'item' => 'Cedula (Community Tax Certificate)', 'amount' => '₱5.00', 'method' => 'GCash', 'receipt' => 'Esperanza_GCash_Cedula_Community_Tax_Certificate_DR_2026_2350.png', 'status' => 'Approved', 'date' => 'Aug 24, 2026'],
    ['ref' => 'DR-2026-2331', 'citizen' => 'Cristy Pareja Bonghanoy', 'barangay' => 'Baras', 'type' => 'Dokyu', 'item' => 'Certificate of Residency', 'amount' => '₱50.00', 'method' => 'PayMaya', 'receipt' => 'Esperanza_Maya_Certificate_of_Residency_DR_2026_2331.png', 'status' => 'Approved', 'date' => 'Aug 24, 2026'],
    ['ref' => 'DR-2026-2333', 'citizen' => 'Cristy Pareja Bonghanoy', 'barangay' => 'Baras', 'type' => 'Dokyu', 'item' => 'Business Permit (New)', 'amount' => '₱500.00', 'method' => 'Payment in Center', 'receipt' => 'Esperanza_Onsite_Business_Permit_New_DR_2026_2333.png', 'status' => 'Approved', 'date' => 'Aug 24, 2026'],
    ['ref' => 'DR-2026-2334', 'citizen' => 'Cristy Pareja Bonghanoy', 'barangay' => 'Baras', 'type' => 'Dokyu', 'item' => 'Real Property Tax Clearance', 'amount' => '₱100.00', 'method' => 'GCash', 'receipt' => 'Esperanza_GCash_Real_Property_Tax_Clearance_DR_2026_2334.png', 'status' => 'Approved', 'date' => 'Aug 24, 2026'],
    ['ref' => 'DR-2026-2335', 'citizen' => 'Cristy Pareja Bonghanoy', 'barangay' => 'Baras', 'type' => 'Dokyu', 'item' => 'Certificate of Live Birth (Certified Copy)', 'amount' => '₱155.00', 'method' => 'PayMaya', 'receipt' => 'Esperanza_Maya_Certificate_of_Live_Birth_Certified_Copy_DR_2026_2335.png', 'status' => 'Approved', 'date' => 'Aug 24, 2026'],
    ['ref' => 'DR-2026-2337', 'citizen' => 'Cristy Pareja Bonghanoy', 'barangay' => 'Baras', 'type' => 'Dokyu', 'item' => 'Application for Marriage License', 'amount' => '₱300.00', 'method' => 'Payment in Center', 'receipt' => 'Esperanza_Onsite_Application_for_Marriage_License_DR_2026_2337.png', 'status' => 'Approved', 'date' => 'Aug 24, 2026'],
    ['ref' => 'DR-2026-2338', 'citizen' => 'Cristy Pareja Bonghanoy', 'barangay' => 'Baras', 'type' => 'Dokyu', 'item' => 'Certified Copy of Marriage Certificate', 'amount' => '₱155.00', 'method' => 'GCash', 'receipt' => 'Esperanza_GCash_Certified_Copy_of_Marriage_Certificate_DR_2026_2338.png', 'status' => 'Approved', 'date' => 'Aug 24, 2026'],
    ['ref' => 'DR-2026-2339', 'citizen' => 'Cristy Pareja Bonghanoy', 'barangay' => 'Baras', 'type' => 'Dokyu', 'item' => 'Delayed Registration of Birth', 'amount' => '₱200.00', 'method' => 'PayMaya', 'receipt' => 'Esperanza_Maya_Delayed_Registration_of_Birth_DR_2026_2339.png', 'status' => 'Approved', 'date' => 'Aug 24, 2026'],
    ['ref' => 'DR-2026-2340', 'citizen' => 'Cristy Pareja Bonghanoy', 'barangay' => 'Baras', 'type' => 'Dokyu', 'item' => 'Delayed Registration of Death', 'amount' => '₱200.00', 'method' => 'Payment in Center', 'receipt' => 'Esperanza_Onsite_Delayed_Registration_of_Death_DR_2026_2340.png', 'status' => 'Approved', 'date' => 'Aug 24, 2026'],
    ['ref' => 'DR-2026-2341', 'citizen' => 'Cristy Pareja Bonghanoy', 'barangay' => 'Baras', 'type' => 'Dokyu', 'item' => 'Barangay Certification for Late Registration of Birth', 'amount' => '₱50.00', 'method' => 'GCash', 'receipt' => 'Esperanza_GCash_Barangay_Certification_for_Late_Registration_of_Birth_DR_2026_2341.png', 'status' => 'Approved', 'date' => 'Aug 24, 2026'],
    ['ref' => 'DR-2026-2342', 'citizen' => 'Cristy Pareja Bonghanoy', 'barangay' => 'Baras', 'type' => 'Dokyu', 'item' => 'Barangay Certification for Registration of Death', 'amount' => '₱50.00', 'method' => 'PayMaya', 'receipt' => 'Esperanza_Maya_Barangay_Certification_for_Registration_of_Death_DR_2026_2342.png', 'status' => 'Approved', 'date' => 'Aug 24, 2026'],
    ['ref' => 'DR-2026-2343', 'citizen' => 'Cristy Pareja Bonghanoy', 'barangay' => 'Baras', 'type' => 'Dokyu', 'item' => 'Pet Registration', 'amount' => '₱50.00', 'method' => 'Payment in Center', 'receipt' => 'Esperanza_Onsite_Pet_Registration_DR_2026_2343.png', 'status' => 'Approved', 'date' => 'Aug 24, 2026'],
    ['ref' => 'DR-2026-2344', 'citizen' => 'Cristy Pareja Bonghanoy', 'barangay' => 'Baras', 'type' => 'Dokyu', 'item' => 'Locational Clearance', 'amount' => '₱500.00', 'method' => 'GCash', 'receipt' => 'Esperanza_GCash_Locational_Clearance_DR_2026_2344.png', 'status' => 'Approved', 'date' => 'Aug 24, 2026'],
];
