<?php
// ============================================================
// api/chart-data.php - Dashboard Chart Data (Optimized)
// ============================================================
require_once '../includes/config.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-cache, no-store, must-revalidate');

requireApiAuth();

$db = new Supabase(true);

try {
    // Fetch all customers (select only needed fields)
    $result = $db->select('customers', [
        'select'     => 'country,state,created_at',
        'is_deleted' => 'eq.false',
        'limit'      => '10000',
    ]);

    if (!$db->isSuccess($result)) {
        jsonResponse(false, $result['message'] ?? 'Failed to load chart data', null, $result['status'] ?: 500);
    }

    $customers = $result['data'] ?? [];

    // Normalization Map
    $countryMap = [
        'IN' => 'India', 'IND' => 'India',
        'US' => 'USA', 'USA' => 'USA', 'United States' => 'USA',
        'UAE' => 'UAE', 'United Arab Emirates' => 'UAE',
        'UK' => 'United Kingdom', 'GB' => 'United Kingdom',
    ];

    // Customers by Country
    $byCountry = [];
    foreach ($customers as $c) {
        $country = trim($c['country'] ?? 'Unknown');
        if ($country === '' || $country === null) $country = 'Unknown';
        
        $norm = $countryMap[strtoupper($country)] ?? $country;
        $byCountry[$norm] = ($byCountry[$norm] ?? 0) + 1;
    }
    arsort($byCountry);

    // Customers by State (top 10)
    $byState = [];
    foreach ($customers as $c) {
        $state = trim($c['state'] ?? 'Unknown');
        if ($state === '') $state = 'Unknown';
        $byState[$state] = ($byState[$state] ?? 0) + 1;
    }
    arsort($byState);
    $byState = array_slice($byState, 0, 10, true);

    // Customers by Month (last 6 months)
    $byMonth = [];
    for ($i = 5; $i >= 0; $i--) {
        $monthKey   = date('Y-m', strtotime("-$i months"));
        $monthLabel = date('M Y', strtotime("-$i months"));
        $byMonth[$monthKey] = ['label' => $monthLabel, 'count' => 0];
    }
    foreach ($customers as $c) {
        if (!empty($c['created_at'])) {
            $mk = date('Y-m', strtotime($c['created_at']));
            if (isset($byMonth[$mk])) {
                $byMonth[$mk]['count']++;
            }
        }
    }

    jsonResponse(true, 'Chart data fetched', [
        'by_country' => [
            'labels' => array_keys($byCountry),
            'values' => array_values($byCountry),
        ],
        'by_state' => [
            'labels' => array_keys($byState),
            'values' => array_values($byState),
        ],
        'by_month' => [
            'labels' => array_column(array_values($byMonth), 'label'),
            'values' => array_column(array_values($byMonth), 'count'),
        ],
        'total' => count($customers),
    ]);

} catch (Exception $e) {
    jsonResponse(false, 'Failed to load chart data: ' . $e->getMessage(), null, 500);
}
