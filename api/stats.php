<?php
// ============================================================
// api/stats.php - Dashboard Statistics
// ============================================================
require_once '../includes/config.php';

header('Content-Type: application/json');

requireApiAuth();

$db = new Supabase(true);
$hasActiveColumn = dbHasColumn('customers', 'is_active');

try {
    $baseFilter = ['is_deleted' => 'eq.false'];

    if ($hasActiveColumn) {
        $total    = $db->count('customers', $baseFilter);
        $active   = $db->count('customers', array_merge($baseFilter, ['is_active' => 'eq.true']));
        $inactive = $db->count('customers', array_merge($baseFilter, ['is_active' => 'eq.false']));
        $today    = $db->count('customers', array_merge($baseFilter, [
            'created_at' => 'gte.' . date('Y-m-d') . 'T00:00:00',
        ]));

        if (!$db->isSuccess($total)) {
            jsonResponse(false, $total['message'] ?? 'Failed to load stats', null, $total['status'] ?: 500);
        }

        $totalCount    = $total['count'] ?? 0;
        $activeCount   = $active['count'] ?? 0;
        $inactiveCount = $inactive['count'] ?? 0;
        $todayCount    = $today['count'] ?? 0;
    } else {
        $all = $db->select('customers', array_merge($baseFilter, ['select' => 'id,created_at']));
        if (!$db->isSuccess($all)) {
            jsonResponse(false, $all['message'] ?? 'Failed to load stats', null, $all['status'] ?: 500);
        }

        $customers = enrichCustomers($db, $all['data'] ?? []);
        $totalCount = count($customers);
        $activeCount = count(array_filter($customers, fn($c) => !empty($c['is_active'])));
        $inactiveCount = $totalCount - $activeCount;
        $todayCount = count(array_filter($customers, function ($c) {
            return !empty($c['created_at']) && strpos($c['created_at'], date('Y-m-d')) === 0;
        }));
    }

    $statesData = $db->select('customers', [
        'is_deleted' => 'eq.false',
        'select'     => 'state',
    ]);

    $countriesData = $db->select('customers', [
        'is_deleted' => 'eq.false',
        'select'     => 'country',
    ]);

    $countryMap = [
        'IN' => 'India', 'IND' => 'India',
        'US' => 'USA', 'USA' => 'USA', 'United States' => 'USA',
        'UAE' => 'UAE', 'United Arab Emirates' => 'UAE',
        'UK' => 'United Kingdom', 'GB' => 'United Kingdom',
    ];
    $uniqueCountries = [];
    foreach ($countriesData['data'] ?? [] as $c) {
        $name = strtoupper(trim($c['country'] ?? 'Unknown'));
        if ($name === '') $name = 'UNKNOWN';
        $norm = $countryMap[$name] ?? $name;
        $uniqueCountries[$norm] = true;
    }

    $statesCount = count(array_unique(array_filter(array_column($statesData['data'] ?? [], 'state'))));

    jsonResponse(true, 'Stats fetched', [
        'total'     => $totalCount,
        'active'    => $activeCount,
        'inactive'  => $inactiveCount,
        'today'     => $todayCount,
        'states'    => $statesCount,
        'countries' => count($uniqueCountries),
    ]);

} catch (Exception $e) {
    jsonResponse(false, 'Failed to load stats: ' . $e->getMessage(), null, 500);
}
