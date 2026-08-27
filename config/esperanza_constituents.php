<?php

/**
 * Esperanza Constituent Registry — deterministic sample data.
 * Consumed by admin.constituents view. All relationships are internally
 * consistent: households ↔ families ↔ individuals share IDs.
 */

// ── Households (5) ────────────────────────────────────────────────────────────
$households = [
    [
        'id'             => 'HH-2026-001',
        'label'          => 'Bacaltos Household',
        'barangay'       => 'Poblacion',
        'sitio'          => 'Purok 2',
        'address'        => '247 Magsaysay Street, Purok 2, Brgy. Poblacion',
        'head_id'        => 'RES-2026-001',
        'head_name'      => 'Maria Fe A. Bacaltos',
        'family_ids'     => ['FAM-2026-001', 'FAM-2026-002'],
        'family_count'   => 2,
        'member_count'   => 9,
        'dwelling_type'  => 'Single Detached',
        'housing_tenure' => 'Owner',
        'electricity'    => true,
        'water'          => true,
        'toilet'         => true,
        'flood_risk'     => 'Low',
        'hazard_zone'    => 'Zone 1 (Safe)',
        'profile_pct'    => 88,
        'verification'   => 'Verified',
        'status'         => 'Active',
        'programs'       => ['4Ps', 'OSCA'],
        'tags'           => ['senior', 'youth'],
        'created'        => '2024-01-15',
        'last_updated'   => '2026-06-12',
    ],
    [
        'id'             => 'HH-2026-002',
        'label'          => 'Santos Household',
        'barangay'       => 'Masbaranon',
        'sitio'          => 'Sitio Malbog',
        'address'        => '18 Rizal Street, Sitio Malbog, Brgy. Masbaranon',
        'head_id'        => 'RES-2026-010',
        'head_name'      => 'Eduardo T. Santos',
        'family_ids'     => ['FAM-2026-003'],
        'family_count'   => 1,
        'member_count'   => 5,
        'dwelling_type'  => 'Single Attached',
        'housing_tenure' => 'Owner',
        'electricity'    => true,
        'water'          => false,
        'toilet'         => true,
        'flood_risk'     => 'Medium',
        'hazard_zone'    => 'Zone 2 (Moderate Risk)',
        'profile_pct'    => 72,
        'verification'   => 'Partially Verified',
        'status'         => 'Active',
        'programs'       => ['4Ps'],
        'tags'           => ['farmer', 'child'],
        'created'        => '2024-03-08',
        'last_updated'   => '2026-05-22',
    ],
    [
        'id'             => 'HH-2026-003',
        'label'          => 'Dela Cruz Household',
        'barangay'       => 'Baras',
        'sitio'          => 'Purok 1',
        'address'        => '12 Bonifacio Street, Purok 1, Brgy. Baras',
        'head_id'        => 'RES-2026-015',
        'head_name'      => 'Rosario P. Dela Cruz',
        'family_ids'     => ['FAM-2026-004', 'FAM-2026-005'],
        'family_count'   => 2,
        'member_count'   => 8,
        'dwelling_type'  => 'Single Detached',
        'housing_tenure' => 'Owner',
        'electricity'    => true,
        'water'          => true,
        'toilet'         => true,
        'flood_risk'     => 'Low',
        'hazard_zone'    => 'Zone 1 (Safe)',
        'profile_pct'    => 85,
        'verification'   => 'Verified',
        'status'         => 'Active',
        'programs'       => ['OSCA', 'PWD Assistance'],
        'tags'           => ['senior', 'pwd', 'infant'],
        'created'        => '2024-02-20',
        'last_updated'   => '2026-07-01',
    ],
    [
        'id'             => 'HH-2026-004',
        'label'          => 'Reyes Household',
        'barangay'       => 'Domorog',
        'sitio'          => 'Sitio Tibaon',
        'address'        => '45 Quezon Avenue, Sitio Tibaon, Brgy. Domorog',
        'head_id'        => 'RES-2026-023',
        'head_name'      => 'Andres B. Reyes',
        'family_ids'     => ['FAM-2026-006'],
        'family_count'   => 1,
        'member_count'   => 4,
        'dwelling_type'  => 'Informal Settler',
        'housing_tenure' => 'Renter',
        'electricity'    => true,
        'water'          => false,
        'toilet'         => false,
        'flood_risk'     => 'High',
        'hazard_zone'    => 'Zone 3 (High Risk)',
        'profile_pct'    => 55,
        'verification'   => 'Partially Verified',
        'status'         => 'Active',
        'programs'       => [],
        'tags'           => ['youth', 'high_risk'],
        'created'        => '2024-07-11',
        'last_updated'   => '2026-04-18',
    ],
    [
        'id'             => 'HH-2026-005',
        'label'          => 'Flores Household',
        'barangay'       => 'Magsaysay',
        'sitio'          => 'Purok 3',
        'address'        => '89 Luna Street, Purok 3, Brgy. Magsaysay',
        'head_id'        => 'RES-2026-027',
        'head_name'      => 'Grace E. Flores',
        'family_ids'     => ['FAM-2026-007'],
        'family_count'   => 1,
        'member_count'   => 3,
        'dwelling_type'  => 'Single Detached',
        'housing_tenure' => 'Owner',
        'electricity'    => true,
        'water'          => true,
        'toilet'         => true,
        'flood_risk'     => 'Low',
        'hazard_zone'    => 'Zone 1 (Safe)',
        'profile_pct'    => 78,
        'verification'   => 'Verified',
        'status'         => 'Active',
        'programs'       => ['Solo Parent', '4Ps'],
        'tags'           => ['solo_parent', 'child'],
        'created'        => '2024-09-03',
        'last_updated'   => '2026-06-28',
    ],
    // ── One-member households for each barangay's sample citizen-portal
    // resident (config/esperanza_citizens.php) — so the account a citizen
    // signs in with is also a findable, real record in the admin registry.
    ['id'=>'HH-2026-101','label'=>'Bautista Household','barangay'=>'Labangtaytay','sitio'=>'Purok 2','address'=>'Purok 2, Barangay Labangtaytay, Esperanza, Masbate','head_id'=>'RES-2026-101','head_name'=>'Ronaldo Panganiban Bautista','family_ids'=>['FAM-2026-101'],'family_count'=>1,'member_count'=>1,'dwelling_type'=>'Single Detached','housing_tenure'=>'Owner','electricity'=>true,'water'=>true,'toilet'=>true,'flood_risk'=>'Low','hazard_zone'=>'Zone 1 (Safe)','profile_pct'=>78,'verification'=>'Partially Verified','status'=>'Active','programs'=>[],'tags'=>[],'created'=>'2015-01-01','last_updated'=>'2026-07-08'],
    ['id'=>'HH-2026-102','label'=>'Ferrer Household','barangay'=>'Agoho','sitio'=>'Purok 1','address'=>'Purok 1, Barangay Agoho, Esperanza, Masbate','head_id'=>'RES-2026-102','head_name'=>'Marites Gonzales Ferrer','family_ids'=>['FAM-2026-102'],'family_count'=>1,'member_count'=>1,'dwelling_type'=>'Single Detached','housing_tenure'=>'Owner','electricity'=>true,'water'=>true,'toilet'=>true,'flood_risk'=>'Low','hazard_zone'=>'Zone 1 (Safe)','profile_pct'=>82,'verification'=>'Verified','status'=>'Active','programs'=>[],'tags'=>['farmer'],'created'=>'2010-01-01','last_updated'=>'2026-07-02'],
    ['id'=>'HH-2026-103','label'=>'Aranas Household','barangay'=>'Almero','sitio'=>'Purok 3','address'=>'Purok 3, Barangay Almero, Esperanza, Masbate','head_id'=>'RES-2026-103','head_name'=>'Joel Villanueva Aranas','family_ids'=>['FAM-2026-103'],'family_count'=>1,'member_count'=>1,'dwelling_type'=>'Single Attached','housing_tenure'=>'Renter','electricity'=>true,'water'=>true,'toilet'=>true,'flood_risk'=>'Low','hazard_zone'=>'Zone 1 (Safe)','profile_pct'=>65,'verification'=>'Partially Verified','status'=>'Active','programs'=>[],'tags'=>[],'created'=>'2018-01-01','last_updated'=>'2026-06-20'],
    ['id'=>'HH-2026-104','label'=>'Bonghanoy Household','barangay'=>'Baras','sitio'=>'Purok 2','address'=>'Purok 2, Barangay Baras, Esperanza, Masbate','head_id'=>'RES-2026-104','head_name'=>'Cristy Pareja Bonghanoy','family_ids'=>['FAM-2026-104'],'family_count'=>1,'member_count'=>1,'dwelling_type'=>'Single Detached','housing_tenure'=>'Owner','electricity'=>true,'water'=>true,'toilet'=>true,'flood_risk'=>'Low','hazard_zone'=>'Zone 1 (Safe)','profile_pct'=>90,'verification'=>'Verified','status'=>'Active','programs'=>[],'tags'=>[],'created'=>'2012-01-01','last_updated'=>'2026-07-15'],
    ['id'=>'HH-2026-105','label'=>'Cabahug Household','barangay'=>'Domorog','sitio'=>'Purok 4','address'=>'Purok 4, Barangay Domorog, Esperanza, Masbate','head_id'=>'RES-2026-105','head_name'=>'Ariel Santos Cabahug','family_ids'=>['FAM-2026-105'],'family_count'=>1,'member_count'=>1,'dwelling_type'=>'Single Detached','housing_tenure'=>'Owner','electricity'=>true,'water'=>false,'toilet'=>true,'flood_risk'=>'Medium','hazard_zone'=>'Zone 2 (Moderate Risk)','profile_pct'=>71,'verification'=>'Partially Verified','status'=>'Active','programs'=>[],'tags'=>[],'created'=>'2008-01-01','last_updated'=>'2026-06-11'],
    ['id'=>'HH-2026-106','label'=>'Dimaano Household','barangay'=>'Guadalupe','sitio'=>'Purok 1','address'=>'Purok 1, Barangay Guadalupe, Esperanza, Masbate','head_id'=>'RES-2026-106','head_name'=>'Lorna Reyes Dimaano','family_ids'=>['FAM-2026-106'],'family_count'=>1,'member_count'=>1,'dwelling_type'=>'Single Detached','housing_tenure'=>'Owner','electricity'=>true,'water'=>true,'toilet'=>true,'flood_risk'=>'Low','hazard_zone'=>'Zone 1 (Safe)','profile_pct'=>88,'verification'=>'Verified','status'=>'Active','programs'=>[],'tags'=>[],'created'=>'2001-01-01','last_updated'=>'2026-07-20'],
    ['id'=>'HH-2026-107','label'=>'Espinosa Household','barangay'=>'Iligan','sitio'=>'Purok 2','address'=>'Purok 2, Barangay Iligan, Esperanza, Masbate','head_id'=>'RES-2026-107','head_name'=>'Bayani Cruz Espinosa','family_ids'=>['FAM-2026-107'],'family_count'=>1,'member_count'=>1,'dwelling_type'=>'Single Attached','housing_tenure'=>'Renter','electricity'=>true,'water'=>true,'toilet'=>true,'flood_risk'=>'Low','hazard_zone'=>'Zone 1 (Safe)','profile_pct'=>60,'verification'=>'Unverified','status'=>'Active','programs'=>[],'tags'=>['farmer'],'created'=>'2019-01-01','last_updated'=>'2026-05-30'],
    ['id'=>'HH-2026-108','label'=>'Galvez Household','barangay'=>'Labrador','sitio'=>'Purok 3','address'=>'Purok 3, Barangay Labrador, Esperanza, Masbate','head_id'=>'RES-2026-108','head_name'=>'Divina Torres Galvez','family_ids'=>['FAM-2026-108'],'family_count'=>1,'member_count'=>1,'dwelling_type'=>'Single Detached','housing_tenure'=>'Owner','electricity'=>true,'water'=>true,'toilet'=>true,'flood_risk'=>'Low','hazard_zone'=>'Zone 1 (Safe)','profile_pct'=>95,'verification'=>'Verified','status'=>'Active','programs'=>[],'tags'=>[],'created'=>'2011-01-01','last_updated'=>'2026-07-18'],
    ['id'=>'HH-2026-109','label'=>'Hilario Household','barangay'=>'Libertad','sitio'=>'Purok 1','address'=>'Purok 1, Barangay Libertad, Esperanza, Masbate','head_id'=>'RES-2026-109','head_name'=>'Ferdinand Ramos Hilario','family_ids'=>['FAM-2026-109'],'family_count'=>1,'member_count'=>1,'dwelling_type'=>'Single Detached','housing_tenure'=>'Owner','electricity'=>true,'water'=>true,'toilet'=>true,'flood_risk'=>'Low','hazard_zone'=>'Zone 1 (Safe)','profile_pct'=>74,'verification'=>'Partially Verified','status'=>'Active','programs'=>[],'tags'=>[],'created'=>'2005-01-01','last_updated'=>'2026-06-25'],
    ['id'=>'HH-2026-110','label'=>'Ibarra Household','barangay'=>'Magsaysay','sitio'=>'Purok 5','address'=>'Purok 5, Barangay Magsaysay, Esperanza, Masbate','head_id'=>'RES-2026-110','head_name'=>'Analiza Bautista Ibarra','family_ids'=>['FAM-2026-110'],'family_count'=>1,'member_count'=>1,'dwelling_type'=>'Single Detached','housing_tenure'=>'Owner','electricity'=>true,'water'=>true,'toilet'=>true,'flood_risk'=>'Low','hazard_zone'=>'Zone 1 (Safe)','profile_pct'=>68,'verification'=>'Partially Verified','status'=>'Active','programs'=>[],'tags'=>[],'created'=>'2016-01-01','last_updated'=>'2026-07-01'],
    ['id'=>'HH-2026-111','label'=>'Jacinto Household','barangay'=>'Masbaranon','sitio'=>'Purok 2','address'=>'Purok 2, Barangay Masbaranon, Esperanza, Masbate','head_id'=>'RES-2026-111','head_name'=>'Romulo Flores Jacinto','family_ids'=>['FAM-2026-111'],'family_count'=>1,'member_count'=>1,'dwelling_type'=>'Single Detached','housing_tenure'=>'Owner','electricity'=>true,'water'=>true,'toilet'=>true,'flood_risk'=>'Low','hazard_zone'=>'Zone 1 (Safe)','profile_pct'=>80,'verification'=>'Verified','status'=>'Active','programs'=>[],'tags'=>[],'created'=>'2013-01-01','last_updated'=>'2026-07-05'],
    ['id'=>'HH-2026-112','label'=>'Kalaw Household','barangay'=>'Poblacion','sitio'=>'Purok 6','address'=>'Purok 6, Barangay Poblacion, Esperanza, Masbate','head_id'=>'RES-2026-112','head_name'=>'Grace Mendoza Kalaw','family_ids'=>['FAM-2026-112'],'family_count'=>1,'member_count'=>1,'dwelling_type'=>'Single Detached','housing_tenure'=>'Owner','electricity'=>true,'water'=>true,'toilet'=>true,'flood_risk'=>'Low','hazard_zone'=>'Zone 1 (Safe)','profile_pct'=>92,'verification'=>'Verified','status'=>'Active','programs'=>[],'tags'=>[],'created'=>'2014-01-01','last_updated'=>'2026-07-22'],
    ['id'=>'HH-2026-113','label'=>'Lazaro Household','barangay'=>'Potingbato','sitio'=>'Purok 1','address'=>'Purok 1, Barangay Potingbato, Esperanza, Masbate','head_id'=>'RES-2026-113','head_name'=>'Danilo Aquino Lazaro','family_ids'=>['FAM-2026-113'],'family_count'=>1,'member_count'=>1,'dwelling_type'=>'Single Detached','housing_tenure'=>'Owner','electricity'=>true,'water'=>true,'toilet'=>true,'flood_risk'=>'Low','hazard_zone'=>'Zone 1 (Safe)','profile_pct'=>76,'verification'=>'Partially Verified','status'=>'Active','programs'=>[],'tags'=>['farmer'],'created'=>'2009-01-01','last_updated'=>'2026-06-30'],
    ['id'=>'HH-2026-114','label'=>'Manalo Household','barangay'=>'Rizal','sitio'=>'Purok 2','address'=>'Purok 2, Barangay Rizal, Esperanza, Masbate','head_id'=>'RES-2026-114','head_name'=>'Precious Domingo Manalo','family_ids'=>['FAM-2026-114'],'family_count'=>1,'member_count'=>1,'dwelling_type'=>'Single Detached','housing_tenure'=>'Owner','electricity'=>true,'water'=>true,'toilet'=>true,'flood_risk'=>'Low','hazard_zone'=>'Zone 1 (Safe)','profile_pct'=>55,'verification'=>'For Validation','status'=>'Active','programs'=>[],'tags'=>['youth'],'created'=>'2005-01-01','last_updated'=>'2026-05-15'],
    ['id'=>'HH-2026-115','label'=>'Nierva Household','barangay'=>'San Roque','sitio'=>'Purok 3','address'=>'Purok 3, Barangay San Roque, Esperanza, Masbate','head_id'=>'RES-2026-115','head_name'=>'Ernesto Salazar Nierva','family_ids'=>['FAM-2026-115'],'family_count'=>1,'member_count'=>1,'dwelling_type'=>'Single Attached','housing_tenure'=>'Renter','electricity'=>true,'water'=>true,'toilet'=>true,'flood_risk'=>'Low','hazard_zone'=>'Zone 1 (Safe)','profile_pct'=>70,'verification'=>'Partially Verified','status'=>'Active','programs'=>[],'tags'=>[],'created'=>'2007-01-01','last_updated'=>'2026-06-08'],
    ['id'=>'HH-2026-116','label'=>'Olivar Household','barangay'=>'Santiago','sitio'=>'Purok 1','address'=>'Purok 1, Barangay Santiago, Esperanza, Masbate','head_id'=>'RES-2026-116','head_name'=>'Josephine Castro Olivar','family_ids'=>['FAM-2026-116'],'family_count'=>1,'member_count'=>1,'dwelling_type'=>'Single Detached','housing_tenure'=>'Owner','electricity'=>true,'water'=>true,'toilet'=>true,'flood_risk'=>'Low','hazard_zone'=>'Zone 1 (Safe)','profile_pct'=>84,'verification'=>'Verified','status'=>'Active','programs'=>[],'tags'=>[],'created'=>'2015-01-01','last_updated'=>'2026-07-12'],
    ['id'=>'HH-2026-117','label'=>'Pascua Household','barangay'=>'Sorosimbajan','sitio'=>'Purok 2','address'=>'Purok 2, Barangay Sorosimbajan, Esperanza, Masbate','head_id'=>'RES-2026-117','head_name'=>'Ramil Garcia Pascua','family_ids'=>['FAM-2026-117'],'family_count'=>1,'member_count'=>1,'dwelling_type'=>'Single Detached','housing_tenure'=>'Owner','electricity'=>true,'water'=>true,'toilet'=>true,'flood_risk'=>'Low','hazard_zone'=>'Zone 1 (Safe)','profile_pct'=>62,'verification'=>'Partially Verified','status'=>'Active','programs'=>[],'tags'=>[],'created'=>'2020-01-01','last_updated'=>'2026-06-02'],
    ['id'=>'HH-2026-118','label'=>'Quimson Household','barangay'=>'Tawad','sitio'=>'Purok 1','address'=>'Purok 1, Barangay Tawad, Esperanza, Masbate','head_id'=>'RES-2026-118','head_name'=>'Liezel Navarro Quimson','family_ids'=>['FAM-2026-118'],'family_count'=>1,'member_count'=>1,'dwelling_type'=>'Single Detached','housing_tenure'=>'Owner','electricity'=>true,'water'=>true,'toilet'=>true,'flood_risk'=>'Low','hazard_zone'=>'Zone 1 (Safe)','profile_pct'=>73,'verification'=>'Partially Verified','status'=>'Active','programs'=>[],'tags'=>[],'created'=>'2017-01-01','last_updated'=>'2026-06-27'],
    ['id'=>'HH-2026-119','label'=>'Robles Household','barangay'=>'Tunga','sitio'=>'Purok 2','address'=>'Purok 2, Barangay Tunga, Esperanza, Masbate','head_id'=>'RES-2026-119','head_name'=>'Dante Marquez Robles','family_ids'=>['FAM-2026-119'],'family_count'=>1,'member_count'=>1,'dwelling_type'=>'Single Detached','housing_tenure'=>'Owner','electricity'=>true,'water'=>true,'toilet'=>true,'flood_risk'=>'Low','hazard_zone'=>'Zone 1 (Safe)','profile_pct'=>87,'verification'=>'Verified','status'=>'Active','programs'=>[],'tags'=>['farmer'],'created'=>'2003-01-01','last_updated'=>'2026-07-10'],
    ['id'=>'HH-2026-120','label'=>'Sarmiento Household','barangay'=>'Villa','sitio'=>'Purok 3','address'=>'Purok 3, Barangay Villa, Esperanza, Masbate','head_id'=>'RES-2026-120','head_name'=>'Cherry Ann Villaflor Sarmiento','family_ids'=>['FAM-2026-120'],'family_count'=>1,'member_count'=>1,'dwelling_type'=>'Single Detached','housing_tenure'=>'Renter','electricity'=>true,'water'=>true,'toilet'=>true,'flood_risk'=>'Low','hazard_zone'=>'Zone 1 (Safe)','profile_pct'=>66,'verification'=>'Partially Verified','status'=>'Active','programs'=>[],'tags'=>['youth'],'created'=>'2021-01-01','last_updated'=>'2026-06-18'],
];

// ── Families (7) ──────────────────────────────────────────────────────────────
$families = [
    [
        'id'           => 'FAM-2026-001',
        'label'        => 'Bacaltos Family',
        'surname'      => 'Bacaltos',
        'family_type'  => 'Nuclear',
        'barangay'     => 'Poblacion',
        'sitio'        => 'Purok 2',
        'household_id' => 'HH-2026-001',
        'head_id'      => 'RES-2026-001',
        'head_name'    => 'Maria Fe A. Bacaltos',
        'member_ids'   => ['RES-2026-001','RES-2026-002','RES-2026-003','RES-2026-004','RES-2026-005'],
        'member_count' => 5,
        'profile_pct'  => 86,
        'verification' => 'Verified',
        'status'       => 'Active',
        'programs'     => ['4Ps', 'OSCA'],
        'notes'        => 'Head is a widow, senior citizen, sole provider for 4 children.',
        'created'      => '2024-01-15',
        'last_updated' => '2026-06-12',
    ],
    [
        'id'           => 'FAM-2026-002',
        'label'        => 'Villanueva Family',
        'surname'      => 'Villanueva',
        'family_type'  => 'Extended',
        'barangay'     => 'Poblacion',
        'sitio'        => 'Purok 2',
        'household_id' => 'HH-2026-001',
        'head_id'      => 'RES-2026-006',
        'head_name'    => 'Carlos T. Villanueva',
        'member_ids'   => ['RES-2026-006','RES-2026-007','RES-2026-008','RES-2026-009'],
        'member_count' => 4,
        'profile_pct'  => 75,
        'verification' => 'Partially Verified',
        'status'       => 'Active',
        'programs'     => [],
        'notes'        => 'Extended relatives of the Bacaltos family sharing the same compound.',
        'created'      => '2024-01-15',
        'last_updated' => '2026-05-08',
    ],
    [
        'id'           => 'FAM-2026-003',
        'label'        => 'Santos Family',
        'surname'      => 'Santos',
        'family_type'  => 'Nuclear',
        'barangay'     => 'Masbaranon',
        'sitio'        => 'Sitio Malbog',
        'household_id' => 'HH-2026-002',
        'head_id'      => 'RES-2026-010',
        'head_name'    => 'Eduardo T. Santos',
        'member_ids'   => ['RES-2026-010','RES-2026-011','RES-2026-012','RES-2026-013'],
        'member_count' => 4,
        'profile_pct'  => 79,
        'verification' => 'Verified',
        'status'       => 'Active',
        'programs'     => ['4Ps'],
        'notes'        => 'Farming family; 2 minor children enrolled in school.',
        'created'      => '2024-03-08',
        'last_updated' => '2026-05-22',
    ],
    [
        'id'           => 'FAM-2026-004',
        'label'        => 'Dela Cruz Family',
        'surname'      => 'Dela Cruz',
        'family_type'  => 'Nuclear',
        'barangay'     => 'Baras',
        'sitio'        => 'Purok 1',
        'household_id' => 'HH-2026-003',
        'head_id'      => 'RES-2026-015',
        'head_name'    => 'Rosario P. Dela Cruz',
        'member_ids'   => ['RES-2026-015','RES-2026-016','RES-2026-017','RES-2026-021'],
        'member_count' => 4,
        'profile_pct'  => 91,
        'verification' => 'Verified',
        'status'       => 'Active',
        'programs'     => ['OSCA', 'PWD Assistance'],
        'notes'        => 'Senior citizen couple; one PWD son living with them.',
        'created'      => '2024-02-20',
        'last_updated' => '2026-07-01',
    ],
    [
        'id'           => 'FAM-2026-005',
        'label'        => 'Dela Cruz-Umali Family',
        'surname'      => 'Dela Cruz-Umali',
        'family_type'  => 'Nuclear',
        'barangay'     => 'Baras',
        'sitio'        => 'Purok 1',
        'household_id' => 'HH-2026-003',
        'head_id'      => 'RES-2026-018',
        'head_name'    => 'Lourdes D. Dela Cruz-Umali',
        'member_ids'   => ['RES-2026-018','RES-2026-019','RES-2026-020','RES-2026-022'],
        'member_count' => 4,
        'profile_pct'  => 76,
        'verification' => 'Partially Verified',
        'status'       => 'Active',
        'programs'     => [],
        'notes'        => 'Daughter of the Dela Cruz seniors; lives in the same compound with husband and 2 young children.',
        'created'      => '2024-04-10',
        'last_updated' => '2026-06-15',
    ],
    [
        'id'           => 'FAM-2026-006',
        'label'        => 'Reyes Family',
        'surname'      => 'Reyes',
        'family_type'  => 'Nuclear',
        'barangay'     => 'Domorog',
        'sitio'        => 'Sitio Tibaon',
        'household_id' => 'HH-2026-004',
        'head_id'      => 'RES-2026-023',
        'head_name'    => 'Andres B. Reyes',
        'member_ids'   => ['RES-2026-023','RES-2026-024','RES-2026-025','RES-2026-026'],
        'member_count' => 4,
        'profile_pct'  => 58,
        'verification' => 'Partially Verified',
        'status'       => 'Active',
        'programs'     => [],
        'notes'        => 'Located in a high flood-risk zone. Two out-of-school youth.',
        'created'      => '2024-07-11',
        'last_updated' => '2026-04-18',
    ],
    [
        'id'           => 'FAM-2026-007',
        'label'        => 'Flores Family',
        'surname'      => 'Flores',
        'family_type'  => 'Single Parent',
        'barangay'     => 'Magsaysay',
        'sitio'        => 'Purok 3',
        'household_id' => 'HH-2026-005',
        'head_id'      => 'RES-2026-027',
        'head_name'    => 'Grace E. Flores',
        'member_ids'   => ['RES-2026-027','RES-2026-028','RES-2026-029'],
        'member_count' => 3,
        'profile_pct'  => 80,
        'verification' => 'Verified',
        'status'       => 'Active',
        'programs'     => ['Solo Parent', '4Ps'],
        'notes'        => 'Solo parent; head is separated, raising 2 minor children.',
        'created'      => '2024-09-03',
        'last_updated' => '2026-06-28',
    ],
    // ── One per barangay's sample citizen-portal resident (see matching
    // household note above) ──────────────────────────────────────────────
    ['id'=>'FAM-2026-101','label'=>'Bautista Family','surname'=>'Bautista','family_type'=>'Single','barangay'=>'Labangtaytay','sitio'=>'Purok 2','household_id'=>'HH-2026-101','head_id'=>'RES-2026-101','head_name'=>'Ronaldo Panganiban Bautista','member_ids'=>['RES-2026-101'],'member_count'=>1,'profile_pct'=>78,'verification'=>'Partially Verified','status'=>'Active','programs'=>[],'notes'=>'Citizen Portal sample resident for Barangay Labangtaytay.','created'=>'2015-01-01','last_updated'=>'2026-07-08'],
    ['id'=>'FAM-2026-102','label'=>'Ferrer Family','surname'=>'Ferrer','family_type'=>'Single','barangay'=>'Agoho','sitio'=>'Purok 1','household_id'=>'HH-2026-102','head_id'=>'RES-2026-102','head_name'=>'Marites Gonzales Ferrer','member_ids'=>['RES-2026-102'],'member_count'=>1,'profile_pct'=>82,'verification'=>'Verified','status'=>'Active','programs'=>[],'notes'=>'Citizen Portal sample resident for Barangay Agoho.','created'=>'2010-01-01','last_updated'=>'2026-07-02'],
    ['id'=>'FAM-2026-103','label'=>'Aranas Family','surname'=>'Aranas','family_type'=>'Single','barangay'=>'Almero','sitio'=>'Purok 3','household_id'=>'HH-2026-103','head_id'=>'RES-2026-103','head_name'=>'Joel Villanueva Aranas','member_ids'=>['RES-2026-103'],'member_count'=>1,'profile_pct'=>65,'verification'=>'Partially Verified','status'=>'Active','programs'=>[],'notes'=>'Citizen Portal sample resident for Barangay Almero.','created'=>'2018-01-01','last_updated'=>'2026-06-20'],
    ['id'=>'FAM-2026-104','label'=>'Bonghanoy Family','surname'=>'Bonghanoy','family_type'=>'Single','barangay'=>'Baras','sitio'=>'Purok 2','household_id'=>'HH-2026-104','head_id'=>'RES-2026-104','head_name'=>'Cristy Pareja Bonghanoy','member_ids'=>['RES-2026-104'],'member_count'=>1,'profile_pct'=>90,'verification'=>'Verified','status'=>'Active','programs'=>[],'notes'=>'Citizen Portal sample resident for Barangay Baras.','created'=>'2012-01-01','last_updated'=>'2026-07-15'],
    ['id'=>'FAM-2026-105','label'=>'Cabahug Family','surname'=>'Cabahug','family_type'=>'Single','barangay'=>'Domorog','sitio'=>'Purok 4','household_id'=>'HH-2026-105','head_id'=>'RES-2026-105','head_name'=>'Ariel Santos Cabahug','member_ids'=>['RES-2026-105'],'member_count'=>1,'profile_pct'=>71,'verification'=>'Partially Verified','status'=>'Active','programs'=>[],'notes'=>'Citizen Portal sample resident for Barangay Domorog.','created'=>'2008-01-01','last_updated'=>'2026-06-11'],
    ['id'=>'FAM-2026-106','label'=>'Dimaano Family','surname'=>'Dimaano','family_type'=>'Single','barangay'=>'Guadalupe','sitio'=>'Purok 1','household_id'=>'HH-2026-106','head_id'=>'RES-2026-106','head_name'=>'Lorna Reyes Dimaano','member_ids'=>['RES-2026-106'],'member_count'=>1,'profile_pct'=>88,'verification'=>'Verified','status'=>'Active','programs'=>[],'notes'=>'Citizen Portal sample resident for Barangay Guadalupe.','created'=>'2001-01-01','last_updated'=>'2026-07-20'],
    ['id'=>'FAM-2026-107','label'=>'Espinosa Family','surname'=>'Espinosa','family_type'=>'Single','barangay'=>'Iligan','sitio'=>'Purok 2','household_id'=>'HH-2026-107','head_id'=>'RES-2026-107','head_name'=>'Bayani Cruz Espinosa','member_ids'=>['RES-2026-107'],'member_count'=>1,'profile_pct'=>60,'verification'=>'Unverified','status'=>'Active','programs'=>[],'notes'=>'Citizen Portal sample resident for Barangay Iligan.','created'=>'2019-01-01','last_updated'=>'2026-05-30'],
    ['id'=>'FAM-2026-108','label'=>'Galvez Family','surname'=>'Galvez','family_type'=>'Single','barangay'=>'Labrador','sitio'=>'Purok 3','household_id'=>'HH-2026-108','head_id'=>'RES-2026-108','head_name'=>'Divina Torres Galvez','member_ids'=>['RES-2026-108'],'member_count'=>1,'profile_pct'=>95,'verification'=>'Verified','status'=>'Active','programs'=>[],'notes'=>'Citizen Portal sample resident for Barangay Labrador.','created'=>'2011-01-01','last_updated'=>'2026-07-18'],
    ['id'=>'FAM-2026-109','label'=>'Hilario Family','surname'=>'Hilario','family_type'=>'Single','barangay'=>'Libertad','sitio'=>'Purok 1','household_id'=>'HH-2026-109','head_id'=>'RES-2026-109','head_name'=>'Ferdinand Ramos Hilario','member_ids'=>['RES-2026-109'],'member_count'=>1,'profile_pct'=>74,'verification'=>'Partially Verified','status'=>'Active','programs'=>[],'notes'=>'Citizen Portal sample resident for Barangay Libertad.','created'=>'2005-01-01','last_updated'=>'2026-06-25'],
    ['id'=>'FAM-2026-110','label'=>'Ibarra Family','surname'=>'Ibarra','family_type'=>'Single','barangay'=>'Magsaysay','sitio'=>'Purok 5','household_id'=>'HH-2026-110','head_id'=>'RES-2026-110','head_name'=>'Analiza Bautista Ibarra','member_ids'=>['RES-2026-110'],'member_count'=>1,'profile_pct'=>68,'verification'=>'Partially Verified','status'=>'Active','programs'=>[],'notes'=>'Citizen Portal sample resident for Barangay Magsaysay.','created'=>'2016-01-01','last_updated'=>'2026-07-01'],
    ['id'=>'FAM-2026-111','label'=>'Jacinto Family','surname'=>'Jacinto','family_type'=>'Single','barangay'=>'Masbaranon','sitio'=>'Purok 2','household_id'=>'HH-2026-111','head_id'=>'RES-2026-111','head_name'=>'Romulo Flores Jacinto','member_ids'=>['RES-2026-111'],'member_count'=>1,'profile_pct'=>80,'verification'=>'Verified','status'=>'Active','programs'=>[],'notes'=>'Citizen Portal sample resident for Barangay Masbaranon.','created'=>'2013-01-01','last_updated'=>'2026-07-05'],
    ['id'=>'FAM-2026-112','label'=>'Kalaw Family','surname'=>'Kalaw','family_type'=>'Single','barangay'=>'Poblacion','sitio'=>'Purok 6','household_id'=>'HH-2026-112','head_id'=>'RES-2026-112','head_name'=>'Grace Mendoza Kalaw','member_ids'=>['RES-2026-112'],'member_count'=>1,'profile_pct'=>92,'verification'=>'Verified','status'=>'Active','programs'=>[],'notes'=>'Citizen Portal sample resident for Barangay Poblacion.','created'=>'2014-01-01','last_updated'=>'2026-07-22'],
    ['id'=>'FAM-2026-113','label'=>'Lazaro Family','surname'=>'Lazaro','family_type'=>'Single','barangay'=>'Potingbato','sitio'=>'Purok 1','household_id'=>'HH-2026-113','head_id'=>'RES-2026-113','head_name'=>'Danilo Aquino Lazaro','member_ids'=>['RES-2026-113'],'member_count'=>1,'profile_pct'=>76,'verification'=>'Partially Verified','status'=>'Active','programs'=>[],'notes'=>'Citizen Portal sample resident for Barangay Potingbato.','created'=>'2009-01-01','last_updated'=>'2026-06-30'],
    ['id'=>'FAM-2026-114','label'=>'Manalo Family','surname'=>'Manalo','family_type'=>'Single','barangay'=>'Rizal','sitio'=>'Purok 2','household_id'=>'HH-2026-114','head_id'=>'RES-2026-114','head_name'=>'Precious Domingo Manalo','member_ids'=>['RES-2026-114'],'member_count'=>1,'profile_pct'=>55,'verification'=>'For Validation','status'=>'Active','programs'=>[],'notes'=>'Citizen Portal sample resident for Barangay Rizal.','created'=>'2005-01-01','last_updated'=>'2026-05-15'],
    ['id'=>'FAM-2026-115','label'=>'Nierva Family','surname'=>'Nierva','family_type'=>'Single','barangay'=>'San Roque','sitio'=>'Purok 3','household_id'=>'HH-2026-115','head_id'=>'RES-2026-115','head_name'=>'Ernesto Salazar Nierva','member_ids'=>['RES-2026-115'],'member_count'=>1,'profile_pct'=>70,'verification'=>'Partially Verified','status'=>'Active','programs'=>[],'notes'=>'Citizen Portal sample resident for Barangay San Roque.','created'=>'2007-01-01','last_updated'=>'2026-06-08'],
    ['id'=>'FAM-2026-116','label'=>'Olivar Family','surname'=>'Olivar','family_type'=>'Single','barangay'=>'Santiago','sitio'=>'Purok 1','household_id'=>'HH-2026-116','head_id'=>'RES-2026-116','head_name'=>'Josephine Castro Olivar','member_ids'=>['RES-2026-116'],'member_count'=>1,'profile_pct'=>84,'verification'=>'Verified','status'=>'Active','programs'=>[],'notes'=>'Citizen Portal sample resident for Barangay Santiago.','created'=>'2015-01-01','last_updated'=>'2026-07-12'],
    ['id'=>'FAM-2026-117','label'=>'Pascua Family','surname'=>'Pascua','family_type'=>'Single','barangay'=>'Sorosimbajan','sitio'=>'Purok 2','household_id'=>'HH-2026-117','head_id'=>'RES-2026-117','head_name'=>'Ramil Garcia Pascua','member_ids'=>['RES-2026-117'],'member_count'=>1,'profile_pct'=>62,'verification'=>'Partially Verified','status'=>'Active','programs'=>[],'notes'=>'Citizen Portal sample resident for Barangay Sorosimbajan.','created'=>'2020-01-01','last_updated'=>'2026-06-02'],
    ['id'=>'FAM-2026-118','label'=>'Quimson Family','surname'=>'Quimson','family_type'=>'Single','barangay'=>'Tawad','sitio'=>'Purok 1','household_id'=>'HH-2026-118','head_id'=>'RES-2026-118','head_name'=>'Liezel Navarro Quimson','member_ids'=>['RES-2026-118'],'member_count'=>1,'profile_pct'=>73,'verification'=>'Partially Verified','status'=>'Active','programs'=>[],'notes'=>'Citizen Portal sample resident for Barangay Tawad.','created'=>'2017-01-01','last_updated'=>'2026-06-27'],
    ['id'=>'FAM-2026-119','label'=>'Robles Family','surname'=>'Robles','family_type'=>'Single','barangay'=>'Tunga','sitio'=>'Purok 2','household_id'=>'HH-2026-119','head_id'=>'RES-2026-119','head_name'=>'Dante Marquez Robles','member_ids'=>['RES-2026-119'],'member_count'=>1,'profile_pct'=>87,'verification'=>'Verified','status'=>'Active','programs'=>[],'notes'=>'Citizen Portal sample resident for Barangay Tunga.','created'=>'2003-01-01','last_updated'=>'2026-07-10'],
    ['id'=>'FAM-2026-120','label'=>'Sarmiento Family','surname'=>'Sarmiento','family_type'=>'Single','barangay'=>'Villa','sitio'=>'Purok 3','household_id'=>'HH-2026-120','head_id'=>'RES-2026-120','head_name'=>'Cherry Ann Villaflor Sarmiento','member_ids'=>['RES-2026-120'],'member_count'=>1,'profile_pct'=>66,'verification'=>'Partially Verified','status'=>'Active','programs'=>[],'notes'=>'Citizen Portal sample resident for Barangay Villa.','created'=>'2021-01-01','last_updated'=>'2026-06-18'],
];

// ── Individuals (31 records) ──────────────────────────────────────────────────
$individuals = [
    // HH-001 / FAM-001 Bacaltos Nuclear
    ['id'=>'RES-2026-004','name'=>'Marco A. Bacaltos','sex'=>'Male','age'=>23,'dob'=>'2002-09-30','civil_status'=>'Single','barangay'=>'Poblacion','sitio'=>'Purok 2','household_id'=>'HH-2026-001','family_id'=>'FAM-2026-001','relation'=>'Son','contact'=>null,'occupation'=>'Unemployed','education'=>'Junior High School Graduate','income_range'=>'None','tags'=>['youth','osy'],'verification'=>'Partially Verified','profile_pct'=>60,'status'=>'Active','created'=>'2024-01-15','last_updated'=>'2025-11-14','emergency'=>['name'=>'Maria Fe A. Bacaltos','contact'=>'0917 342 8810','relation'=>'Mother'],'services'=>[]],
    ['id'=>'RES-2026-005','name'=>'Lucia A. Bacaltos','sex'=>'Female','age'=>19,'dob'=>'2006-02-14','civil_status'=>'Single','barangay'=>'Poblacion','sitio'=>'Purok 2','household_id'=>'HH-2026-001','family_id'=>'FAM-2026-001','relation'=>'Daughter','contact'=>null,'occupation'=>'Student (Senior High)','education'=>'Senior High School (Current)','income_range'=>'None','tags'=>['youth','4ps'],'verification'=>'For Validation','profile_pct'=>45,'status'=>'Active','created'=>'2024-08-20','last_updated'=>'2025-09-10','emergency'=>['name'=>'Maria Fe A. Bacaltos','contact'=>'0917 342 8810','relation'=>'Mother'],'services'=>[]],
    // HH-001 / FAM-002 Villanueva Extended
    ['id'=>'RES-2026-008','name'=>'Gina T. Villanueva','sex'=>'Female','age'=>21,'dob'=>'2004-07-08','civil_status'=>'Single','barangay'=>'Poblacion','sitio'=>'Purok 2','household_id'=>'HH-2026-001','family_id'=>'FAM-2026-002','relation'=>'Daughter','contact'=>null,'occupation'=>'Vendor (Street)','education'=>'Senior High School Graduate','income_range'=>'Below ₱5,000/mo','tags'=>['youth'],'verification'=>'Partially Verified','profile_pct'=>58,'status'=>'Active','created'=>'2024-06-12','last_updated'=>'2025-12-01','emergency'=>['name'=>'Carlos T. Villanueva','contact'=>'0918 445 6672','relation'=>'Father'],'services'=>[]],
    ['id'=>'RES-2026-009','name'=>'Rico T. Villanueva','sex'=>'Male','age'=>17,'dob'=>'2008-10-25','civil_status'=>'Single','barangay'=>'Poblacion','sitio'=>'Purok 2','household_id'=>'HH-2026-001','family_id'=>'FAM-2026-002','relation'=>'Son','contact'=>null,'occupation'=>'Student (Junior High)','education'=>'Junior High School (Current)','income_range'=>'None','tags'=>['youth','child'],'verification'=>'Unverified','profile_pct'=>30,'status'=>'Active','created'=>'2024-06-12','last_updated'=>'2024-11-20','emergency'=>['name'=>'Carlos T. Villanueva','contact'=>'0918 445 6672','relation'=>'Father'],'services'=>[]],
    // HH-002 / FAM-003 Santos Nuclear
    ['id'=>'RES-2026-012','name'=>'Paolo E. Santos','sex'=>'Male','age'=>15,'dob'=>'2010-08-08','civil_status'=>'Single','barangay'=>'Masbaranon','sitio'=>'Sitio Malbog','household_id'=>'HH-2026-002','family_id'=>'FAM-2026-003','relation'=>'Son','contact'=>null,'occupation'=>'Student (Junior High)','education'=>'Junior High School (Current)','income_range'=>'None','tags'=>['youth','child','4ps'],'verification'=>'Unverified','profile_pct'=>40,'status'=>'Active','created'=>'2024-03-08','last_updated'=>'2025-07-14','emergency'=>['name'=>'Eduardo T. Santos','contact'=>'0928 512 4401','relation'=>'Father'],'services'=>[]],
    ['id'=>'RES-2026-013','name'=>'Sophia E. Santos','sex'=>'Female','age'=>11,'dob'=>'2014-12-03','civil_status'=>'Single','barangay'=>'Masbaranon','sitio'=>'Sitio Malbog','household_id'=>'HH-2026-002','family_id'=>'FAM-2026-003','relation'=>'Daughter','contact'=>null,'occupation'=>'Student (Elementary)','education'=>'Elementary (Current)','income_range'=>'None','tags'=>['child','4ps'],'verification'=>'Unverified','profile_pct'=>35,'status'=>'Active','created'=>'2024-03-08','last_updated'=>'2025-06-01','emergency'=>['name'=>'Eduardo T. Santos','contact'=>'0928 512 4401','relation'=>'Father'],'services'=>[]],
    ['id'=>'RES-2026-014','name'=>'Roberto M. Aquino','sex'=>'Male','age'=>30,'dob'=>'1995-03-16','civil_status'=>'Single','barangay'=>'Masbaranon','sitio'=>'Sitio Malbog','household_id'=>'HH-2026-002','family_id'=>null,'relation'=>'Boarder','contact'=>'0906 778 3302','occupation'=>'Informal Worker','education'=>'Junior High School Graduate','income_range'=>'Below ₱5,000/mo','tags'=>[],'verification'=>'Unverified','profile_pct'=>28,'status'=>'Active','created'=>'2025-01-10','last_updated'=>'2025-04-05','emergency'=>['name'=>null,'contact'=>null,'relation'=>null],'services'=>[]],
    // HH-003 / FAM-004 Dela Cruz Nuclear (Senior)
    ['id'=>'RES-2026-021','name'=>'Benjamin R. Dela Cruz','sex'=>'Male','age'=>29,'dob'=>'1996-08-18','civil_status'=>'Single','barangay'=>'Baras','sitio'=>'Purok 1','household_id'=>'HH-2026-003','family_id'=>'FAM-2026-004','relation'=>'Son','contact'=>null,'occupation'=>'Farmer','education'=>'High School Graduate','income_range'=>'Below ₱5,000/mo','tags'=>[],'verification'=>'Unverified','profile_pct'=>32,'status'=>'Active','created'=>'2025-02-04','last_updated'=>'2025-05-01','emergency'=>['name'=>'Rosario P. Dela Cruz','contact'=>'0927 441 0082','relation'=>'Mother'],'services'=>[]],
    // HH-003 / FAM-005 Dela Cruz-Umali
    ['id'=>'RES-2026-019','name'=>'Carlos A. Umali','sex'=>'Male','age'=>34,'dob'=>'1991-11-30','civil_status'=>'Married','barangay'=>'Baras','sitio'=>'Purok 1','household_id'=>'HH-2026-003','family_id'=>'FAM-2026-005','relation'=>'Spouse','contact'=>'0916 830 2201','occupation'=>'Government Employee (LGU)','education'=>'College Graduate (BSBA)','income_range'=>'Middle (₱10,001–₱20,000/mo)','tags'=>[],'verification'=>'Partially Verified','profile_pct'=>65,'status'=>'Active','created'=>'2024-04-10','last_updated'=>'2025-10-08','emergency'=>['name'=>'Lourdes D. Dela Cruz-Umali','contact'=>'0916 830 2200','relation'=>'Spouse'],'services'=>[]],
    ['id'=>'RES-2026-020','name'=>'Sofia L. Umali','sex'=>'Female','age'=>6,'dob'=>'2019-06-12','civil_status'=>'Single','barangay'=>'Baras','sitio'=>'Purok 1','household_id'=>'HH-2026-003','family_id'=>'FAM-2026-005','relation'=>'Daughter','contact'=>null,'occupation'=>'Student (Elementary)','education'=>'Elementary (Current)','income_range'=>'None','tags'=>['child'],'verification'=>'Unverified','profile_pct'=>38,'status'=>'Active','created'=>'2024-07-20','last_updated'=>'2025-03-10','emergency'=>['name'=>'Lourdes D. Dela Cruz-Umali','contact'=>'0916 830 2200','relation'=>'Mother'],'services'=>[]],
    ['id'=>'RES-2026-022','name'=>'Margarita L. Umali','sex'=>'Female','age'=>3,'dob'=>'2022-11-05','civil_status'=>'Single','barangay'=>'Baras','sitio'=>'Purok 1','household_id'=>'HH-2026-003','family_id'=>'FAM-2026-005','relation'=>'Daughter','contact'=>null,'occupation'=>null,'education'=>'Pre-School (Current)','income_range'=>'None','tags'=>['child','infant'],'verification'=>'Unverified','profile_pct'=>20,'status'=>'Active','created'=>'2025-05-15','last_updated'=>'2025-07-22','emergency'=>['name'=>'Lourdes D. Dela Cruz-Umali','contact'=>'0916 830 2200','relation'=>'Mother'],'services'=>[]],
    // HH-004 / FAM-006 Reyes Nuclear
    ['id'=>'RES-2026-023','name'=>'Andres B. Reyes','sex'=>'Male','age'=>45,'dob'=>'1980-12-01','civil_status'=>'Married','barangay'=>'Domorog','sitio'=>'Sitio Tibaon','household_id'=>'HH-2026-004','family_id'=>'FAM-2026-006','relation'=>'Head','contact'=>'0915 678 9901','occupation'=>'Fisherman','education'=>'Elementary Graduate','income_range'=>'Low (₱5,001–₱10,000/mo)','tags'=>['high_risk'],'verification'=>'Partially Verified','profile_pct'=>68,'status'=>'Active','created'=>'2024-07-11','last_updated'=>'2026-04-18','emergency'=>['name'=>'Marilou G. Reyes','contact'=>'0915 678 9902','relation'=>'Spouse'],'services'=>[['date'=>'2025-11-04','type'=>'Certificate of Indigency','status'=>'Completed']]],
    ['id'=>'RES-2026-024','name'=>'Marilou G. Reyes','sex'=>'Female','age'=>42,'dob'=>'1983-05-17','civil_status'=>'Married','barangay'=>'Domorog','sitio'=>'Sitio Tibaon','household_id'=>'HH-2026-004','family_id'=>'FAM-2026-006','relation'=>'Spouse','contact'=>'0915 678 9902','occupation'=>'Housewife','education'=>'Elementary Graduate','income_range'=>'None','tags'=>[],'verification'=>'Partially Verified','profile_pct'=>65,'status'=>'Active','created'=>'2024-07-11','last_updated'=>'2026-04-18','emergency'=>['name'=>'Andres B. Reyes','contact'=>'0915 678 9901','relation'=>'Spouse'],'services'=>[]],
    ['id'=>'RES-2026-025','name'=>'Nico B. Reyes','sex'=>'Male','age'=>19,'dob'=>'2006-09-14','civil_status'=>'Single','barangay'=>'Domorog','sitio'=>'Sitio Tibaon','household_id'=>'HH-2026-004','family_id'=>'FAM-2026-006','relation'=>'Son','contact'=>null,'occupation'=>'Unemployed','education'=>'Junior High School Graduate','income_range'=>'None','tags'=>['youth','osy'],'verification'=>'Unverified','profile_pct'=>35,'status'=>'Active','created'=>'2024-07-11','last_updated'=>'2025-01-08','emergency'=>['name'=>'Andres B. Reyes','contact'=>'0915 678 9901','relation'=>'Father'],'services'=>[]],
    ['id'=>'RES-2026-026','name'=>'Claudine B. Reyes','sex'=>'Female','age'=>16,'dob'=>'2009-03-28','civil_status'=>'Single','barangay'=>'Domorog','sitio'=>'Sitio Tibaon','household_id'=>'HH-2026-004','family_id'=>'FAM-2026-006','relation'=>'Daughter','contact'=>null,'occupation'=>'Student (Senior High)','education'=>'Senior High School (Current)','income_range'=>'None','tags'=>['youth','child'],'verification'=>'Unverified','profile_pct'=>30,'status'=>'Active','created'=>'2024-07-11','last_updated'=>'2024-12-05','emergency'=>['name'=>'Andres B. Reyes','contact'=>'0915 678 9901','relation'=>'Father'],'services'=>[]],
    // HH-005 / FAM-007 Flores Single Parent
    ['id'=>'RES-2026-028','name'=>'Miguel A. Flores','sex'=>'Male','age'=>13,'dob'=>'2012-11-18','civil_status'=>'Single','barangay'=>'Magsaysay','sitio'=>'Purok 3','household_id'=>'HH-2026-005','family_id'=>'FAM-2026-007','relation'=>'Son','contact'=>null,'occupation'=>'Student (Junior High)','education'=>'Junior High School (Current)','income_range'=>'None','tags'=>['youth','child','4ps'],'verification'=>'Unverified','profile_pct'=>40,'status'=>'Active','created'=>'2024-09-03','last_updated'=>'2025-08-12','emergency'=>['name'=>'Grace E. Flores','contact'=>'0922 301 6605','relation'=>'Mother'],'services'=>[]],
    ['id'=>'RES-2026-029','name'=>'Amara P. Flores','sex'=>'Female','age'=>10,'dob'=>'2015-07-25','civil_status'=>'Single','barangay'=>'Magsaysay','sitio'=>'Purok 3','household_id'=>'HH-2026-005','family_id'=>'FAM-2026-007','relation'=>'Daughter','contact'=>null,'occupation'=>'Student (Elementary)','education'=>'Elementary (Current)','income_range'=>'None','tags'=>['child','4ps'],'verification'=>'Unverified','profile_pct'=>35,'status'=>'Active','created'=>'2024-09-03','last_updated'=>'2025-06-20','emergency'=>['name'=>'Grace E. Flores','contact'=>'0922 301 6605','relation'=>'Mother'],'services'=>[]],
    // Edge-case / Data Quality records
    ['id'=>'RES-2026-030','name'=>'Dario M. Gonzales','sex'=>'Male','age'=>38,'dob'=>'1987-09-22','civil_status'=>'Married','barangay'=>'Agoho','sitio'=>'Purok 1','household_id'=>null,'family_id'=>null,'relation'=>null,'contact'=>'0933 220 8841','occupation'=>'Fisherman','education'=>'Elementary Graduate','income_range'=>'Low (₱5,001–₱10,000/mo)','tags'=>[],'verification'=>'Partially Verified','profile_pct'=>45,'status'=>'Active','created'=>'2025-03-15','last_updated'=>'2025-09-01','emergency'=>['name'=>null,'contact'=>null,'relation'=>null],'services'=>[]],
    ['id'=>'RES-2026-031','name'=>'Felipa P. Santos','sex'=>'Female','age'=>68,'dob'=>'1957-04-10','civil_status'=>'Widowed','barangay'=>'Masbaranon','sitio'=>'Sitio Malbog','household_id'=>null,'family_id'=>null,'relation'=>null,'contact'=>null,'occupation'=>'Retired','education'=>'Elementary Graduate','income_range'=>'Below ₱5,000/mo','tags'=>['senior'],'verification'=>'Unverified','profile_pct'=>22,'status'=>'Under Review','created'=>'2025-11-20','last_updated'=>'2026-01-14','emergency'=>['name'=>null,'contact'=>null,'relation'=>null],'services'=>[]],
    // ── Citizen Portal sample residents (one per barangay) — kept in sync
    // with config/esperanza_citizens.php so each barangay's demo login
    // account is also a real, findable record here in the admin registry.
    ['id'=>'RES-2026-101','citizen_account_id'=>'ESP-RES-2024-1102','name'=>'Ronaldo Panganiban Bautista','sex'=>'Male','age'=>35,'dob'=>'1990-08-22','civil_status'=>'Married','barangay'=>'Labangtaytay','sitio'=>'Purok 2','household_id'=>'HH-2026-101','family_id'=>'FAM-2026-101','relation'=>'Head','contact'=>'0918 224 5567','occupation'=>'Fisherman','education'=>'Elementary Graduate','income_range'=>'Low (₱5,001–₱10,000/mo)','tags'=>[],'verification'=>'Partially Verified','profile_pct'=>78,'status'=>'Active','created'=>'2015-01-01','last_updated'=>'2026-07-08','emergency'=>['name'=>null,'contact'=>null,'relation'=>null],'services'=>[['date'=>'2026-04-18','type'=>"Fisherfolk Registration (FishR) Renewal",'status'=>'Completed']],
        // His citizen account (ESP-RES-2024-1102) is 'Pending Review', i.e.
        // Unverified access tier in the mobile app -- Unverified accounts
        // cannot reach Dokyu/Tulong, so no document/assistance request could
        // have legitimately existed for him. Previously carried a fabricated
        // DR-2026-2241 here; removed to match document-requests.blade.php.
        'document_requests'=>[],
        'assistance_requests'=>[],
        'incident_reports'=>[]],
    // Flagship demo record — deliberately the most fully-populated profile in
    // the registry (every tab, every section) so the complete Constituent
    // Profile experience can be showcased end-to-end on one resident.
    ['id'=>'RES-2026-104','citizen_account_id'=>'ESP-RES-2024-1044','name'=>'Cristy Pareja Bonghanoy','sex'=>'Female','age'=>25,'dob'=>'2001-03-15','civil_status'=>'Single','barangay'=>'Baras','sitio'=>'Purok 2','address'=>'Purok 2, Barangay Baras, Esperanza, Masbate','household_id'=>'HH-2026-104','family_id'=>'FAM-2026-104','relation'=>'Head','contact'=>'0919 502 7734','occupation'=>'Student','education'=>'Senior High School Graduate','income_range'=>'None',
        // Family background — "Pareja" (Cristy's own middle name) is her
        // mother's maiden surname per standard Filipino naming convention,
        // and "Bonghanoy" (her surname) is inherited from her father — so
        // these are derived from her own already-established full name
        // rather than invented from nothing. Consumed everywhere a Dokyu/
        // Tulong form asks for father's/mother's name (see $citizenBio in
        // admin/document-requests.blade.php and admin/assistance-requests.blade.php)
        // so every one of her requests shows the same family identity
        // instead of a different randomly-seeded name per form.
        'father_name'=>'Ramon Bonghanoy','mother_name'=>'Corazon Bonghanoy','mother_maiden_name'=>'Corazon Pareja',
        'tags'=>['4ps'],'verification'=>'Verified','profile_pct'=>90,'status'=>'Active','created'=>'2012-01-01','last_updated'=>'2026-07-15',
        'emergency'=>['name'=>'Roberto Pareja','contact'=>'0919 502 7735','relation'=>'Brother'],
        'services'=>[
            ['date'=>'2026-03-22','type'=>'Certificate of Good Moral Character','status'=>'Completed'],
            ['date'=>'2025-08-30','type'=>'Voter Registration Update','status'=>'Completed'],
        ],
        // Reset to Submitted (see the matching reset in
        // admin/document-requests.blade.php's own $requests array) so a
        // live demo can walk through the mobile-submits-to-admin-sees-it
        // flow from a genuinely fresh state, using Cristy's own account.
        'document_requests'=>[
            ['ref'=>'DR-2026-2233','type'=>'Barangay Business Clearance','office'=>'Barangay Hall (Brgy. Baras)','purpose'=>'Market stall permit renewal','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'DR-2026-2255','type'=>'Barangay Clearance','office'=>'Barangay Hall (Brgy. Baras)','purpose'=>'For local transaction and identification purposes','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'DR-2026-2312','type'=>'Barangay Certification (General Purpose)','office'=>'Barangay Hall (Brgy. Baras)','purpose'=>'For submission as a requirement for a local government transaction','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            // Full-catalog demo set mirrored 1:1 (same refs) with
            // admin/document-requests.blade.php's own $requests array —
            // every remaining Dokyu type, freshly Submitted where Cristy's
            // real profile plausibly qualifies and Rejected with the
            // specific eligibility reason where it clearly doesn't.
            ['ref'=>'DR-2026-2350','type'=>'Cedula','office'=>"Treasurer's Office",'purpose'=>'For submission as a government transaction requirement','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'DR-2026-2331','type'=>'Certificate of Residency','office'=>'Office of the Municipal Civil Registrar','purpose'=>'For submission to a requesting government agency','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'DR-2026-2332','type'=>'Certificate of Indigency','office'=>'MSWDO','purpose'=>'For scholarship and financial assistance application requirement','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'DR-2026-2333','type'=>'Business Permit (New)','office'=>'BPLO','purpose'=>'New business permit application','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'DR-2026-2334','type'=>'Real Property Tax Clearance','office'=>"Treasurer's Office",'purpose'=>'Real property tax clearance for a property transaction','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'DR-2026-2335','type'=>'Certificate of Live Birth (Certified Copy)','office'=>'Office of the Municipal Civil Registrar','purpose'=>'For submission as a scholarship and school enrollment requirement','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'DR-2026-2336','type'=>'First Time Jobseeker Certificate (RA 11261)','office'=>'Barangay Hall (Brgy. Baras)','purpose'=>'For local job application requirement','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'DR-2026-2337','type'=>'Application for Marriage License','office'=>'Office of the Municipal Civil Registrar','purpose'=>'Application for marriage license','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'DR-2026-2338','type'=>'Certified Copy of Marriage Certificate','office'=>'Office of the Municipal Civil Registrar','purpose'=>'For submission as proof of civil status','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'DR-2026-2339','type'=>'Delayed Registration of Birth','office'=>'Office of the Municipal Civil Registrar','purpose'=>'Delayed registration of birth','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'DR-2026-2340','type'=>'Delayed Registration of Death','office'=>'Office of the Municipal Civil Registrar','purpose'=>"Delayed registration of a deceased grandparent's death for civil registry purposes",'submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'DR-2026-2341','type'=>'Barangay Certification (Late Registration)','office'=>'Barangay Hall (Brgy. Baras)','purpose'=>"Supporting certification for a family member's late birth registration",'submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'DR-2026-2342','type'=>'Barangay Certification (Registration of Death)','office'=>'Barangay Hall (Brgy. Baras)','purpose'=>"Barangay-level supporting certification for a deceased grandparent's death registration",'submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'DR-2026-2343','type'=>'Pet Registration','office'=>'Municipal Agriculture Office','purpose'=>'Registration of household pet dog for the municipal pet registry','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'DR-2026-2344','type'=>'Locational Clearance','office'=>'Municipal Planning & Development Office (MPDO)','purpose'=>'Locational clearance for a proposed structure','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'DR-2026-2345','type'=>'Senior Citizen ID','office'=>'OSCA','purpose'=>'Senior Citizen ID / OSCA membership application','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
        ],
        // Solo Parent Cash Assistance removed — now an 18-year-old applying
        // for the Educational Assistance (Scholarship) program instead, kept
        // freshly Submitted for the same live-demo purpose as the Dokyu
        // requests above; see admin/assistance-requests.blade.php's own
        // mirrored $requests entry.
        'assistance_requests'=>[
            ['ref'=>'AR-2026-0163','type'=>'Financial Assistance (AICS)','office'=>'MSWDO','purpose'=>'Livelihood capital support','submitted'=>'May 18, 2026','status'=>'Completed'],
            ['ref'=>'AR-2026-0224','type'=>'Educational Assistance','office'=>"MSWDO / Mayor's Office",'purpose'=>'Scholarship program application','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            // Full-catalog demo set mirrored 1:1 (same refs) with
            // admin/assistance-requests.blade.php's own $requests array —
            // every remaining Tulong program, freshly Submitted where
            // Cristy's real profile plausibly qualifies and Rejected with
            // the specific eligibility reason where it clearly doesn't.
            ['ref'=>'AR-2026-0230','type'=>'Medical Assistance (AICS)','office'=>'MSWDO','purpose'=>'Financial assistance for dental treatment expenses','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'AR-2026-0231','type'=>'Burial Assistance (AICS)','office'=>'MSWDO','purpose'=>'Burial assistance for a deceased grandparent','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'AR-2026-0232','type'=>'Food / Relief Assistance','office'=>'MDRRMO','purpose'=>'Relief assistance following recent heavy rains affecting the household','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'AR-2026-0233','type'=>'Social Pension (Indigent Senior Citizen)','office'=>'OSCA','purpose'=>'Social pension benefit application','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'AR-2026-0234','type'=>'Solo Parent Cash Assistance','office'=>'MSWDO','purpose'=>'Solo parent cash assistance application','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'AR-2026-0235','type'=>'PWD Registration (PRPWD)','office'=>'PDAO','purpose'=>'PWD ID / PRPWD registration application','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'AR-2026-0236','type'=>'TUPAD Emergency Employment','office'=>'MPESO','purpose'=>'Application for short-term emergency employment during school break','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'AR-2026-0237','type'=>'TESDA Skills Training Registration','office'=>'MPESO','purpose'=>'Registration for Bookkeeping NC III skills training program','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
            ['ref'=>'AR-2026-0238','type'=>'ERPAT Program Registration','office'=>'MSWDO','purpose'=>'ERPAT program registration','submitted'=>'Aug 24, 2026','status'=>'Submitted'],
        ],
        'incident_reports'=>[
            ['ref'=>'INC-2026-0085','type'=>'Market Fire (Nearby Stall)','date'=>'Jun 30, 2026','location'=>'Baras Public Market, Stall Row C','status'=>'Resolved','office'=>'BFP Esperanza','source'=>'Citizen Portal','description'=>'Fire broke out at a neighboring stall around closing time. My stall was undamaged but stock had to be evacuated. Reporting for the market fire log.'],
        ]],
    ['id'=>'RES-2026-106','citizen_account_id'=>'ESP-RES-2024-1056','name'=>'Lorna Reyes Dimaano','sex'=>'Female','age'=>47,'dob'=>'1979-07-21','civil_status'=>'Widowed','barangay'=>'Guadalupe','sitio'=>'Purok 1','household_id'=>'HH-2026-106','family_id'=>'FAM-2026-106','relation'=>'Head','contact'=>'0917 809 1265','occupation'=>'Sari-Sari Store Owner','education'=>'High School Graduate','income_range'=>'Low (₱5,001–₱10,000/mo)','tags'=>[],'verification'=>'Verified','profile_pct'=>88,'status'=>'Active','created'=>'2001-01-01','last_updated'=>'2026-07-20','emergency'=>['name'=>null,'contact'=>null,'relation'=>null],'services'=>[['date'=>'2026-02-27','type'=>'Barangay Business Clearance','status'=>'Completed']],
        'document_requests'=>[['ref'=>'DR-2026-2295','type'=>'Certificate of Widowhood','office'=>'Civil Registrar','purpose'=>'Pag-IBIG death-benefit claim requirement','submitted'=>'Jul 24, 2026','status'=>'Approved']],
        'assistance_requests'=>[],'incident_reports'=>[]],
    ['id'=>'RES-2026-108','citizen_account_id'=>'ESP-RES-2024-1071','name'=>'Divina Torres Galvez','sex'=>'Female','age'=>39,'dob'=>'1986-10-12','civil_status'=>'Married','barangay'=>'Labrador','sitio'=>'Purok 3','household_id'=>'HH-2026-108','family_id'=>'FAM-2026-108','relation'=>'Head','contact'=>'0918 992 5501','occupation'=>'Public School Teacher','education'=>'College Graduate (BEEd)','income_range'=>'Middle (₱10,001–₱20,000/mo)','tags'=>[],'verification'=>'Verified','profile_pct'=>95,'status'=>'Active','created'=>'2011-01-01','last_updated'=>'2026-07-18','emergency'=>['name'=>null,'contact'=>null,'relation'=>null],'services'=>[['date'=>'2026-06-10','type'=>'Certificate of Employment','status'=>'Completed']],
        'document_requests'=>[['ref'=>'DR-2026-2301','type'=>'Certificate of Good Moral Character','office'=>'Barangay Hall (Brgy. Labrador)','purpose'=>'DepEd plantilla application requirement','submitted'=>'Jul 8, 2026','status'=>'Released']],
        'assistance_requests'=>[],'incident_reports'=>[]],
    ['id'=>'RES-2026-110','citizen_account_id'=>'ESP-RES-2024-1145','name'=>'Analiza Bautista Ibarra','sex'=>'Female','age'=>35,'dob'=>'1991-05-03','civil_status'=>'Married','barangay'=>'Magsaysay','sitio'=>'Purok 5','household_id'=>'HH-2026-110','family_id'=>'FAM-2026-110','relation'=>'Head','contact'=>'0917 664 2278','occupation'=>'OFW (on home leave)','education'=>'College Graduate (BSHM)','income_range'=>'Middle (₱10,001–₱20,000/mo)','tags'=>[],'verification'=>'Partially Verified','profile_pct'=>68,'status'=>'Active','created'=>'2016-01-01','last_updated'=>'2026-07-01','emergency'=>['name'=>null,'contact'=>null,'relation'=>null],'services'=>[],
        'document_requests'=>[['ref'=>'DR-2026-2219','type'=>'Certificate of Residency','office'=>'Civil Registrar','purpose'=>'OFW documentation','submitted'=>'Jun 15, 2026','status'=>'Completed']],
        'assistance_requests'=>[['ref'=>'AR-2026-0148','type'=>'Social Pension (Indigent Senior Citizen)','office'=>'OSCA','purpose'=>'On behalf of elderly parent','submitted'=>'Jun 2, 2026','status'=>'Processing']],
        'incident_reports'=>[]],
    // Ported from the Esperanza Mobile app's own seeded duplicate-registration
    // demo (unverifiedDuplicateAccountA/B in mock_catalog.dart) — a second,
    // distinct duplicate pattern from Grace Mendoza Kalaw's above: there, an
    // already-Verified identity gets impersonated by a stranger; here, the
    // SAME honest person (Teodoro) simply registered twice, 16 days apart,
    // most likely forgetting the first attempt, and neither registration has
    // reached Verified. Linked to the earlier of the two accounts
    // (ESP-RES-2026-2101, Jul 3, 2026); the second attempt
    // (ESP-RES-2026-2102, t.villaflor92@email.com, Jul 19, 2026) is narrated
    // in the flag below rather than modeled as a second linked account, same
    // convention Grace's own flag already uses for its "separate registration".
    ['id'=>'RES-2026-121','citizen_account_id'=>'ESP-RES-2026-2101','name'=>'Teodoro Villaflor','sex'=>'Male','age'=>34,'dob'=>'1992-05-14','civil_status'=>'Single','barangay'=>'Libertad','sitio'=>'Purok 3','household_id'=>'HH-2026-121','family_id'=>'FAM-2026-121','relation'=>'Head','contact'=>'0918 442 1190','occupation'=>'Tricycle Driver','education'=>'High School Graduate','income_range'=>'Below ₱5,000/mo','tags'=>[],'verification'=>'Unverified','profile_pct'=>48,'status'=>'Active','created'=>'2018-01-01','last_updated'=>'2026-07-19','emergency'=>['name'=>null,'contact'=>null,'relation'=>null],'services'=>[],
        'document_requests'=>[],'assistance_requests'=>[],'incident_reports'=>[],
        'identity_flags'=>[
            [
                'type'=>'Duplicate Identity Signal',
                'severity'=>'Review Recommended',
                'summary'=>'This resident appears to have submitted two separate Citizen Portal registrations under the same identity — both currently Pending Review, filed 16 days apart (Jul 3 and Jul 19, 2026) under teodoro.villaflor@email.com and t.villaflor92@email.com. Same name, mobile number, barangay, and submitted ID, which reads as the resident forgetting an earlier attempt rather than impersonation.',
                'recommendation'=>'Confirm directly with the resident which registration to keep; merge or cancel the other once confirmed. Both registrations remain restricted from Dokyu/Tulong access until this is resolved.',
                'detected'=>'Jul 19, 2026',
            ],
        ]],
    // Ten fully-verified residents backed by real supplied photo+ID pairs —
    // see esperanza_citizens.php's own note above ESP-RES-2025-1001..1010
    // for the asset-pairing convention. Names/DOB/sex are read directly off
    // each citizen's own paired PhilSys ID card image.
    ['id'=>'RES-2026-122','citizen_account_id'=>'ESP-RES-2025-1001','name'=>'Juan Miguel Santos Dela Cruz','sex'=>'Male','age'=>28,'dob'=>'1998-07-15','civil_status'=>'Single','barangay'=>'Poblacion','sitio'=>'Purok 1','household_id'=>'HH-2026-122','family_id'=>'FAM-2026-122','relation'=>'Head','contact'=>'0917 220 3301','occupation'=>'Municipal Office Clerk','education'=>'College Graduate (BSBA)','income_range'=>'Middle (₱10,001–₱20,000/mo)','tags'=>[],'verification'=>'Verified','profile_pct'=>88,'status'=>'Active','created'=>'2012-01-01','last_updated'=>'2025-02-14','emergency'=>['name'=>null,'contact'=>null,'relation'=>null],'services'=>[['date'=>'2025-11-04','type'=>'Certificate of Employment','status'=>'Completed']],
        'document_requests'=>[
            ['ref'=>'DR-2026-2320','type'=>'Certificate of Employment','office'=>'Municipal Human Resource Office','purpose'=>'Bank loan requirement','submitted'=>'Nov 4, 2025','status'=>'Completed'],
            ['ref'=>'DR-2026-2321','type'=>'Barangay Clearance','office'=>'Barangay Hall (Brgy. Poblacion)','purpose'=>'For local transaction and identification purposes','submitted'=>'Jul 15, 2026','status'=>'Released'],
        ],
        'assistance_requests'=>[],'incident_reports'=>[]],
    ['id'=>'RES-2026-123','citizen_account_id'=>'ESP-RES-2025-1002','name'=>'Maria Angela Dimaculangan Reyes','sex'=>'Female','age'=>29,'dob'=>'1997-03-22','civil_status'=>'Single','barangay'=>'Agoho','sitio'=>'Purok 2','household_id'=>'HH-2026-123','family_id'=>'FAM-2026-123','relation'=>'Head','contact'=>'0918 336 4412','occupation'=>'Public School Teacher','education'=>'College Graduate (BEEd)','income_range'=>'Middle (₱10,001–₱20,000/mo)','tags'=>[],'verification'=>'Verified','profile_pct'=>91,'status'=>'Active','created'=>'2009-01-01','last_updated'=>'2025-04-03','emergency'=>['name'=>null,'contact'=>null,'relation'=>null],'services'=>[['date'=>'2025-09-12','type'=>'Certificate of Employment','status'=>'Completed']],
        'document_requests'=>[
            ['ref'=>'DR-2026-2322','type'=>'Certificate of Employment','office'=>'Schools Division Office','purpose'=>'GSIS loan requirement','submitted'=>'Sep 12, 2025','status'=>'Released'],
        ],
        'assistance_requests'=>[],'incident_reports'=>[]],
    ['id'=>'RES-2026-124','citizen_account_id'=>'ESP-RES-2025-1003','name'=>'Christian Paolo Santos De Leon','sex'=>'Male','age'=>30,'dob'=>'1995-11-09','civil_status'=>'Married','barangay'=>'Almero','sitio'=>'Purok 4','household_id'=>'HH-2026-124','family_id'=>'FAM-2026-124','relation'=>'Head','contact'=>'0919 442 5523','occupation'=>'Tricycle Driver','education'=>'High School Graduate','income_range'=>'Below ₱5,000/mo','tags'=>[],'verification'=>'Verified','profile_pct'=>85,'status'=>'Active','created'=>'2006-01-01','last_updated'=>'2025-01-20','emergency'=>['name'=>null,'contact'=>null,'relation'=>null],'services'=>[['date'=>'2025-01-20','type'=>'Tricycle Franchise (TODA) Renewal','status'=>'Completed']],
        'document_requests'=>[
            ['ref'=>'DR-2026-2323','type'=>'Tricycle Franchise (TODA) Renewal','office'=>'Barangay Hall (Brgy. Almero)','purpose'=>'Annual franchise renewal','submitted'=>'Jan 20, 2025','status'=>'Completed'],
        ],
        'assistance_requests'=>[
            ['ref'=>'AR-2026-0220','type'=>'Livelihood Assistance (Tools & Equipment)','office'=>'MSWDO','purpose'=>'Replacement sidecar parts after an accident','submitted'=>'Aug 5, 2026','status'=>'Processing'],
        ],
        'incident_reports'=>[]],
    ['id'=>'RES-2026-125','citizen_account_id'=>'ESP-RES-2025-1004','name'=>'Katrina Mae Garcia Villanueva','sex'=>'Female','age'=>30,'dob'=>'1996-05-30','civil_status'=>'Single','barangay'=>'Baras','sitio'=>'Purok 3','household_id'=>'HH-2026-125','family_id'=>'FAM-2026-125','relation'=>'Head','contact'=>'0920 558 6634','occupation'=>'Nurse (Rural Health Unit)','education'=>'College Graduate (BSN)','income_range'=>'Middle (₱10,001–₱20,000/mo)','tags'=>[],'verification'=>'Verified','profile_pct'=>93,'status'=>'Active','created'=>'2011-01-01','last_updated'=>'2025-06-11','emergency'=>['name'=>null,'contact'=>null,'relation'=>null],'services'=>[['date'=>'2025-06-11','type'=>'Certificate of Residency','status'=>'Completed']],
        'document_requests'=>[
            ['ref'=>'DR-2026-2324','type'=>'Certificate of Residency','office'=>'Civil Registrar','purpose'=>'PRC license renewal requirement','submitted'=>'Jun 11, 2025','status'=>'Released'],
        ],
        'assistance_requests'=>[],'incident_reports'=>[]],
    ['id'=>'RES-2026-126','citizen_account_id'=>'ESP-RES-2025-1005','name'=>'Rafael John Villanueva Mendoza','sex'=>'Male','age'=>31,'dob'=>'1994-09-12','civil_status'=>'Married','barangay'=>'Domorog','sitio'=>'Purok 1','household_id'=>'HH-2026-126','family_id'=>'FAM-2026-126','relation'=>'Head','contact'=>'0906 664 7745','occupation'=>'Farmer','education'=>'Elementary Graduate','income_range'=>'Low (₱5,001–₱10,000/mo)','tags'=>['farmer'],'verification'=>'Verified','profile_pct'=>80,'status'=>'Active','created'=>'2004-01-01','last_updated'=>'2024-08-28','emergency'=>['name'=>null,'contact'=>null,'relation'=>null],'services'=>[['date'=>'2024-08-28','type'=>'DA Farmer Registration Renewal','status'=>'Completed']],
        'document_requests'=>[
            ['ref'=>'DR-2026-2325','type'=>'DA Farmer Registration Renewal','office'=>'Municipal Agriculture Office','purpose'=>'Annual RSBSA renewal','submitted'=>'Aug 28, 2024','status'=>'Completed'],
        ],
        'assistance_requests'=>[
            ['ref'=>'AR-2026-0221','type'=>'Agricultural Assistance (Seeds & Fertilizer)','office'=>'Municipal Agriculture Office','purpose'=>'Planting season input support','submitted'=>'Apr 2, 2026','status'=>'Completed'],
        ],
        'incident_reports'=>[]],
    ['id'=>'RES-2026-127','citizen_account_id'=>'ESP-RES-2025-1006','name'=>'Janine Nicole Bautista Alcantara','sex'=>'Female','age'=>28,'dob'=>'1998-01-08','civil_status'=>'Single','barangay'=>'Guadalupe','sitio'=>'Purok 2','household_id'=>'HH-2026-127','family_id'=>'FAM-2026-127','relation'=>'Head','contact'=>'0908 770 8856','occupation'=>'Call Center Agent (Work-from-home)','education'=>'College Graduate (BSIT)','income_range'=>'Middle (₱10,001–₱20,000/mo)','tags'=>[],'verification'=>'Verified','profile_pct'=>87,'status'=>'Active','created'=>'2013-01-01','last_updated'=>'2024-10-05','emergency'=>['name'=>null,'contact'=>null,'relation'=>null],'services'=>[['date'=>'2024-10-05','type'=>'Barangay Business Clearance (Home-based)','status'=>'Completed']],
        'document_requests'=>[
            ['ref'=>'DR-2026-2326','type'=>'Barangay Business Clearance (Home-based)','office'=>'Barangay Hall (Brgy. Guadalupe)','purpose'=>'Freelance/home-based work registration','submitted'=>'Oct 5, 2024','status'=>'Released'],
        ],
        'assistance_requests'=>[],'incident_reports'=>[]],
    ['id'=>'RES-2026-128','citizen_account_id'=>'ESP-RES-2025-1007','name'=>'Mark Anthony Cruz Ramos','sex'=>'Male','age'=>33,'dob'=>'1993-06-18','civil_status'=>'Married','barangay'=>'Iligan','sitio'=>'Purok 1','household_id'=>'HH-2026-128','family_id'=>'FAM-2026-128','relation'=>'Head','contact'=>'0916 881 9967','occupation'=>'Fisherman','education'=>'High School Graduate','income_range'=>'Low (₱5,001–₱10,000/mo)','tags'=>[],'verification'=>'Verified','profile_pct'=>78,'status'=>'Active','created'=>'2003-01-01','last_updated'=>'2025-03-17','emergency'=>['name'=>null,'contact'=>null,'relation'=>null],'services'=>[['date'=>'2025-03-17','type'=>"Fisherfolk Registration (FishR) Renewal",'status'=>'Completed']],
        'document_requests'=>[
            ['ref'=>'DR-2026-2327','type'=>"Fisherfolk Registration (FishR) Renewal",'office'=>'Municipal Agriculture Office','purpose'=>'Annual FishR renewal','submitted'=>'Mar 17, 2025','status'=>'Completed'],
        ],
        'assistance_requests'=>[],
        'incident_reports'=>[
            ['ref'=>'INC-2026-0090','type'=>'Sea Rescue','date'=>'Jun 9, 2026','location'=>'Coastal waters off Brgy. Iligan','status'=>'Resolved','office'=>'MDRRMO / PCG Auxiliary','source'=>'Citizen Portal','description'=>'Boat drifted after engine trouble roughly 3km offshore during choppy conditions. Requested rescue via the Citizen Portal; towed back safely by MDRRMO/PCG Auxiliary.'],
        ]],
    ['id'=>'RES-2026-129','citizen_account_id'=>'ESP-RES-2025-1008','name'=>'Rizza Mae Tan Labrador','sex'=>'Female','age'=>28,'dob'=>'1997-12-25','civil_status'=>'Single','barangay'=>'Magsaysay','sitio'=>'Purok 4','household_id'=>'HH-2026-129','family_id'=>'FAM-2026-129','relation'=>'Head','contact'=>'0922 992 0078','occupation'=>'Sari-Sari Store Owner','education'=>'High School Graduate','income_range'=>'Low (₱5,001–₱10,000/mo)','tags'=>[],'verification'=>'Verified','profile_pct'=>89,'status'=>'Active','created'=>'2010-01-01','last_updated'=>'2025-05-09','emergency'=>['name'=>null,'contact'=>null,'relation'=>null],'services'=>[['date'=>'2025-05-09','type'=>'Barangay Business Clearance','status'=>'Completed']],
        'document_requests'=>[
            ['ref'=>'DR-2026-2328','type'=>'Barangay Business Clearance','office'=>'Barangay Hall (Brgy. Magsaysay)','purpose'=>'Sari-sari store permit renewal','submitted'=>'May 9, 2025','status'=>'Released'],
        ],
        'assistance_requests'=>[],'incident_reports'=>[]],
    ['id'=>'RES-2026-130','citizen_account_id'=>'ESP-RES-2025-1009','name'=>'Michael John Valdez Torres','sex'=>'Male','age'=>31,'dob'=>'1995-08-03','civil_status'=>'Single','barangay'=>'Rizal','sitio'=>'Purok 2','household_id'=>'HH-2026-130','family_id'=>'FAM-2026-130','relation'=>'Head','contact'=>'0926 003 1189','occupation'=>'Carpenter','education'=>'Vocational Graduate','income_range'=>'Low (₱5,001–₱10,000/mo)','tags'=>[],'verification'=>'Verified','profile_pct'=>82,'status'=>'Active','created'=>'2008-01-01','last_updated'=>'2024-11-22','emergency'=>['name'=>null,'contact'=>null,'relation'=>null],'services'=>[['date'=>'2024-11-22','type'=>'Certificate of Employment','status'=>'Completed']],
        'document_requests'=>[
            ['ref'=>'DR-2026-2329','type'=>'Certificate of Employment','office'=>'Barangay Hall (Brgy. Rizal)','purpose'=>'Contractor accreditation requirement','submitted'=>'Nov 22, 2024','status'=>'Completed'],
        ],
        'assistance_requests'=>[
            ['ref'=>'AR-2026-0222','type'=>'Livelihood Assistance (Tools & Equipment)','office'=>'MSWDO','purpose'=>'Replacement power tools for carpentry work','submitted'=>'Feb 10, 2026','status'=>'Completed'],
        ],
        'incident_reports'=>[]],
    ['id'=>'RES-2026-131','citizen_account_id'=>'ESP-RES-2025-1010','name'=>'Shaira Louise Mendoza Paguio','sex'=>'Female','age'=>29,'dob'=>'1996-10-14','civil_status'=>'Single','barangay'=>'Santiago','sitio'=>'Purok 3','household_id'=>'HH-2026-131','family_id'=>'FAM-2026-131','relation'=>'Head','contact'=>'0928 114 2290','occupation'=>'Bookkeeper (Cooperative)','education'=>'College Graduate (BSA)','income_range'=>'Middle (₱10,001–₱20,000/mo)','tags'=>[],'verification'=>'Verified','profile_pct'=>90,'status'=>'Active','created'=>'2014-01-01','last_updated'=>'2025-07-30','emergency'=>['name'=>null,'contact'=>null,'relation'=>null],'services'=>[['date'=>'2025-07-30','type'=>'Certificate of Residency','status'=>'Completed']],
        'document_requests'=>[
            ['ref'=>'DR-2026-2330','type'=>'Certificate of Residency','office'=>'Civil Registrar','purpose'=>'Cooperative membership requirement','submitted'=>'Jul 30, 2025','status'=>'Released'],
        ],
        'assistance_requests'=>[
            ['ref'=>'AR-2026-0223','type'=>'Educational Assistance (Continuing Studies)','office'=>"MSWDO / Mayor's Office",'purpose'=>'Units toward a CPA review program','submitted'=>'Jul 28, 2026','status'=>'Processing'],
        ],
        'incident_reports'=>[]],
];

// ── Barangay coverage (deterministic, sorted by pct desc) ─────────────────────
$barangayCoverage = collect([
    ['name'=>'Poblacion',    'residents'=>1842,'profiled'=>1842,'hh'=>482,'hh_profiled'=>482,'encoder'=>'Rowena Aguilar',     'last_visit'=>'2026-07-10','status'=>'Complete'],
    ['name'=>'Masbaranon',   'residents'=>1620,'profiled'=>1523,'hh'=>428,'hh_profiled'=>404,'encoder'=>'Bienvenido Salazar', 'last_visit'=>'2026-06-28','status'=>'In Progress'],
    ['name'=>'Baras',        'residents'=> 980,'profiled'=> 862,'hh'=>258,'hh_profiled'=>228,'encoder'=>'Dexter Villamor',    'last_visit'=>'2026-06-15','status'=>'In Progress'],
    ['name'=>'Iligan',       'residents'=> 860,'profiled'=> 754,'hh'=>226,'hh_profiled'=>199,'encoder'=>'Ferdinand Cortez',   'last_visit'=>'2026-05-20','status'=>'In Progress'],
    ['name'=>'Santiago',     'residents'=>1240,'profiled'=>1054,'hh'=>326,'hh_profiled'=>278,'encoder'=>'Dexter Villamor',    'last_visit'=>'2026-06-03','status'=>'In Progress'],
    ['name'=>'Magsaysay',    'residents'=> 940,'profiled'=> 769,'hh'=>246,'hh_profiled'=>202,'encoder'=>'Rowena Aguilar',     'last_visit'=>'2026-05-14','status'=>'In Progress'],
    ['name'=>'Domorog',      'residents'=> 760,'profiled'=> 610,'hh'=>200,'hh_profiled'=>162,'encoder'=>'Ferdinand Cortez',   'last_visit'=>'2026-04-22','status'=>'Needs Follow-up'],
    ['name'=>'San Roque',    'residents'=> 640,'profiled'=> 493,'hh'=>168,'hh_profiled'=>130,'encoder'=>'Angeline Mercado',   'last_visit'=>'2026-05-02','status'=>'In Progress'],
    ['name'=>'Rizal',        'residents'=> 820,'profiled'=> 623,'hh'=>216,'hh_profiled'=>166,'encoder'=>'Bienvenido Salazar', 'last_visit'=>'2026-04-10','status'=>'In Progress'],
    ['name'=>'Libertad',     'residents'=> 720,'profiled'=> 540,'hh'=>190,'hh_profiled'=>143,'encoder'=>'Angeline Mercado',   'last_visit'=>'2026-05-28','status'=>'In Progress'],
    ['name'=>'Guadalupe',    'residents'=> 860,'profiled'=> 637,'hh'=>226,'hh_profiled'=>168,'encoder'=>'Ferdinand Cortez',   'last_visit'=>'2026-04-05','status'=>'In Progress'],
    ['name'=>'Potingbato',   'residents'=> 680,'profiled'=> 496,'hh'=>178,'hh_profiled'=>131,'encoder'=>'Rowena Aguilar',     'last_visit'=>'2026-05-08','status'=>'In Progress'],
    ['name'=>'Villa',        'residents'=> 740,'profiled'=> 525,'hh'=>194,'hh_profiled'=>138,'encoder'=>'Dexter Villamor',    'last_visit'=>'2026-03-22','status'=>'Needs Follow-up'],
    ['name'=>'Agoho',        'residents'=>1080,'profiled'=> 756,'hh'=>284,'hh_profiled'=>200,'encoder'=>'Bienvenido Salazar', 'last_visit'=>'2026-04-18','status'=>'In Progress'],
    ['name'=>'Sorosimbajan', 'residents'=> 540,'profiled'=> 367,'hh'=>142,'hh_profiled'=> 97,'encoder'=>'Ferdinand Cortez',   'last_visit'=>'2026-03-10','status'=>'Needs Follow-up'],
    ['name'=>'Labrador',     'residents'=> 560,'profiled'=> 374,'hh'=>146,'hh_profiled'=> 98,'encoder'=>'Angeline Mercado',   'last_visit'=>'2026-03-05','status'=>'Needs Follow-up'],
    ['name'=>'Almero',       'residents'=> 480,'profiled'=> 302,'hh'=>126,'hh_profiled'=> 80,'encoder'=>'Rowena Aguilar',     'last_visit'=>'2026-02-14','status'=>'Needs Follow-up'],
    ['name'=>'Tunga',        'residents'=> 680,'profiled'=> 408,'hh'=>178,'hh_profiled'=>107,'encoder'=>'Dexter Villamor',    'last_visit'=>'2026-02-28','status'=>'Needs Follow-up'],
    ['name'=>'Labangtaytay', 'residents'=> 440,'profiled'=> 253,'hh'=>116,'hh_profiled'=> 68,'encoder'=>'Bienvenido Salazar', 'last_visit'=>'2026-01-22','status'=>'Needs Follow-up'],
    ['name'=>'Tawad',        'residents'=> 460,'profiled'=> 224,'hh'=>120,'hh_profiled'=> 63,'encoder'=>'Angeline Mercado',   'last_visit'=>'2025-12-08','status'=>'Not Started (Re-visit Required)'],
])->map(function ($b) {
    $b['pct']    = $b['residents'] > 0 ? (int) round($b['profiled'] / $b['residents'] * 100) : 0;
    $b['hh_pct'] = $b['hh']       > 0 ? (int) round($b['hh_profiled'] / $b['hh'] * 100)     : 0;
    return $b;
})->sortByDesc('pct')->values()->all();

// ── Data quality issues (12) ──────────────────────────────────────────────────
$dataQualityIssues = [
    ['id'=>'DQI-2026-001','type'=>'Orphaned Record',    'severity'=>'High',    'barangay'=>'Agoho',     'record_id'=>'RES-2026-030','record_name'=>'Dario M. Gonzales',          'description'=>'Resident has no linked household or family. Created 2025-03-15 — link was never completed during field encoding.','assigned_to'=>'Bienvenido Salazar','created'=>'2025-09-15','status'=>'Open',      'merge_candidate'=>null],
    ['id'=>'DQI-2026-002','type'=>'Duplicate Suspected','severity'=>'Critical','barangay'=>'Masbaranon','record_id'=>'RES-2026-031','record_name'=>'Felipa P. Santos',           'description'=>'May be a duplicate of a registered resident in the Santos household. Same surname, barangay, and approximate age.','assigned_to'=>'Rowena Aguilar',    'created'=>'2025-12-01','status'=>'In Review','merge_candidate'=>['record_id'=>'RES-2026-011','name'=>'Consuelo L. Santos','confidence'=>62]],
    ['id'=>'DQI-2026-003','type'=>'Incomplete Profile', 'severity'=>'Medium',  'barangay'=>'Domorog',   'record_id'=>'HH-2026-004', 'record_name'=>'Reyes Household',            'description'=>'Household profile below 60%. Missing: toilet facility, water source, flood evacuation plan. Zone 3 (High Risk) — completeness is critical.','assigned_to'=>'Ferdinand Cortez', 'created'=>'2026-02-10','status'=>'Open',      'merge_candidate'=>null],
    ['id'=>'DQI-2026-004','type'=>'Missing Documents',  'severity'=>'High',    'barangay'=>'Poblacion', 'record_id'=>'RES-2026-005','record_name'=>'Lucia A. Bacaltos',          'description'=>'For-validation status — PSA Birth Certificate and school ID not submitted despite multiple notices. Profile at 45%.','assigned_to'=>'Angeline Mercado',  'created'=>'2025-10-20','status'=>'Open',      'merge_candidate'=>null],
    ['id'=>'DQI-2026-005','type'=>'Missing Documents',  'severity'=>'Medium',  'barangay'=>'Poblacion', 'record_id'=>'RES-2026-009','record_name'=>'Rico T. Villanueva',         'description'=>'Unverified minor — no PSA Birth Certificate or PhilSys ID submitted. Family notified twice.','assigned_to'=>'Angeline Mercado',  'created'=>'2025-08-14','status'=>'Open',      'merge_candidate'=>null],
    ['id'=>'DQI-2026-006','type'=>'Stale Record',       'severity'=>'Medium',  'barangay'=>'Masbaranon','record_id'=>'RES-2026-014','record_name'=>'Roberto M. Aquino',          'description'=>'Boarder record not updated in over 12 months. No contact number verified. May have left the area.','assigned_to'=>'Bienvenido Salazar','created'=>'2026-04-05','status'=>'Open',      'merge_candidate'=>null],
    ['id'=>'DQI-2026-007','type'=>'Orphaned Record',    'severity'=>'High',    'barangay'=>'Masbaranon','record_id'=>'RES-2026-031','record_name'=>'Felipa P. Santos',           'description'=>'No household or family link present. Created during walk-in registration at barangay hall.','assigned_to'=>'Rowena Aguilar',    'created'=>'2025-11-20','status'=>'In Review','merge_candidate'=>null],
    ['id'=>'DQI-2026-008','type'=>'Incomplete Profile', 'severity'=>'Low',     'barangay'=>'Baras',     'record_id'=>'RES-2026-021','record_name'=>'Benjamin R. Dela Cruz',      'description'=>'Profile at 32% completion. Recently added — contact, income, and education fields not yet encoded.','assigned_to'=>'Dexter Villamor',   'created'=>'2025-05-10','status'=>'Open',      'merge_candidate'=>null],
    ['id'=>'DQI-2026-009','type'=>'Missing Documents',  'severity'=>'Medium',  'barangay'=>'Baras',     'record_id'=>'RES-2026-019','record_name'=>'Carlos A. Umali',            'description'=>'Partially verified — government-issued ID not on file. Listed as LGU employee but no ID submitted.','assigned_to'=>'Dexter Villamor',   'created'=>'2025-10-15','status'=>'Open',      'merge_candidate'=>null],
    ['id'=>'DQI-2026-010','type'=>'Incomplete Profile', 'severity'=>'Low',     'barangay'=>'Baras',     'record_id'=>'RES-2026-022','record_name'=>'Margarita L. Umali',         'description'=>'Infant record at 20% completion. PSA birth registration may not yet be processed.','assigned_to'=>'Dexter Villamor',   'created'=>'2025-07-25','status'=>'Open',      'merge_candidate'=>null],
    ['id'=>'DQI-2026-011','type'=>'Stale Record',       'severity'=>'Medium',  'barangay'=>'Domorog',   'record_id'=>'RES-2026-025','record_name'=>'Nico B. Reyes',              'description'=>'Not updated in 18 months. Tagged as OSY — may have migrated to another municipality.','assigned_to'=>'Ferdinand Cortez', 'created'=>'2026-01-12','status'=>'Open',      'merge_candidate'=>null],
    ['id'=>'DQI-2026-012','type'=>'Address Mismatch',   'severity'=>'Low',     'barangay'=>'Magsaysay', 'record_id'=>'HH-2026-005', 'record_name'=>'Flores Household',           'description'=>'System shows Purok 2 but 4Ps monitoring report lists Purok 3. Needs barangay captain confirmation.','assigned_to'=>'Rowena Aguilar',    'created'=>'2026-06-14','status'=>'Open',      'merge_candidate'=>null],
];

// ── Municipal summary (deterministic) ─────────────────────────────────────────
$municipalStats = [
    'residents'      => '17,842',
    'households'     => '4,918',
    'families'       => '6,204',
    'verified'       => '13,894',
    'pending'        => '1,856',
    'new_this_month' => '142',
    'issues'         => count($dataQualityIssues),
];

// ── Per-barangay headline stats (deterministic) ───────────────────────────────
$barangayStats = [
    'Poblacion'    => ['residents'=>'1,842','households'=>'482','families'=>'618','verified'=>'1,842','pending'=>'0',   'new_this_month'=>'18','issues'=>2],
    'Masbaranon'   => ['residents'=>'1,620','households'=>'428','families'=>'542','verified'=>'1,288','pending'=>'232','new_this_month'=>'12','issues'=>3],
    'Baras'        => ['residents'=>'980',  'households'=>'258','families'=>'326','verified'=>'796',  'pending'=>'148','new_this_month'=>'8', 'issues'=>4],
    'Iligan'       => ['residents'=>'860',  'households'=>'226','families'=>'284','verified'=>'690',  'pending'=>'120','new_this_month'=>'6', 'issues'=>1],
    'Santiago'     => ['residents'=>'1,240','households'=>'326','families'=>'412','verified'=>'980',  'pending'=>'188','new_this_month'=>'10','issues'=>2],
    'Magsaysay'    => ['residents'=>'940',  'households'=>'246','families'=>'308','verified'=>'722',  'pending'=>'152','new_this_month'=>'7', 'issues'=>1],
    'Domorog'      => ['residents'=>'760',  'households'=>'200','families'=>'252','verified'=>'566',  'pending'=>'142','new_this_month'=>'5', 'issues'=>2],
    'San Roque'    => ['residents'=>'640',  'households'=>'168','families'=>'210','verified'=>'466',  'pending'=>'112','new_this_month'=>'4', 'issues'=>0],
    'Rizal'        => ['residents'=>'820',  'households'=>'216','families'=>'272','verified'=>'582',  'pending'=>'148','new_this_month'=>'6', 'issues'=>1],
    'Libertad'     => ['residents'=>'720',  'households'=>'190','families'=>'238','verified'=>'498',  'pending'=>'138','new_this_month'=>'5', 'issues'=>0],
    'Guadalupe'    => ['residents'=>'860',  'households'=>'226','families'=>'284','verified'=>'584',  'pending'=>'168','new_this_month'=>'7', 'issues'=>1],
    'Potingbato'   => ['residents'=>'680',  'households'=>'178','families'=>'224','verified'=>'446',  'pending'=>'132','new_this_month'=>'5', 'issues'=>0],
    'Villa'        => ['residents'=>'740',  'households'=>'194','families'=>'244','verified'=>'470',  'pending'=>'150','new_this_month'=>'6', 'issues'=>1],
    'Agoho'        => ['residents'=>'1,080','households'=>'284','families'=>'354','verified'=>'702',  'pending'=>'228','new_this_month'=>'9', 'issues'=>1],
    'Sorosimbajan' => ['residents'=>'540',  'households'=>'142','families'=>'178','verified'=>'326',  'pending'=>'118','new_this_month'=>'4', 'issues'=>0],
    'Labrador'     => ['residents'=>'560',  'households'=>'146','families'=>'184','verified'=>'320',  'pending'=>'128','new_this_month'=>'4', 'issues'=>0],
    'Almero'       => ['residents'=>'480',  'households'=>'126','families'=>'158','verified'=>'264',  'pending'=>'120','new_this_month'=>'3', 'issues'=>0],
    'Tunga'        => ['residents'=>'680',  'households'=>'178','families'=>'224','verified'=>'350',  'pending'=>'196','new_this_month'=>'5', 'issues'=>0],
    'Labangtaytay' => ['residents'=>'440',  'households'=>'116','families'=>'146','verified'=>'220',  'pending'=>'128','new_this_month'=>'3', 'issues'=>0],
    'Tawad'        => ['residents'=>'460',  'households'=>'120','families'=>'150','verified'=>'208',  'pending'=>'160','new_this_month'=>'3', 'issues'=>0],
];

return compact('households','families','individuals','barangayCoverage','dataQualityIssues','municipalStats','barangayStats');
