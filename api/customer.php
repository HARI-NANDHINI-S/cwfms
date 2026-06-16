<?php
// ============================================================
// api/customer.php - Single Customer Fetch + Search API
// ============================================================
require_once '../includes/config.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

requireApiAuth();

$db     = new Supabase(true);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'get';

// ============================================================
// GET: Single customer by ID
// ============================================================
if ($method === 'GET' && $action === 'get') {
    $id = sanitize($_GET['id'] ?? '');
    if (!$id) jsonResponse(false, 'Customer ID required', null, 400);

    $result = $db->select('customers', [
        'id'         => 'eq.' . $id,
        'is_deleted' => 'eq.false',
        'select'     => '*',
    ]);

    if ($result['status'] === 200 && !empty($result['data'])) {
        jsonResponse(true, 'Customer found', $result['data'][0]);
    } else {
        jsonResponse(false, 'Customer not found', null, 404);
    }
}

// ============================================================
// GET: Search customers (live search)
// ============================================================
if ($method === 'GET' && $action === 'search') {
    $q       = sanitize($_GET['q'] ?? '');
    $limit   = min(20, (int)($_GET['limit'] ?? 10));

    if (strlen($q) < 2) {
        jsonResponse(true, 'Query too short', []);
    }

    // Fetch all and filter (Supabase REST doesn't support ILIKE across multiple columns easily)
    $result = $db->select('customers', [
        'is_deleted' => 'eq.false',
        'select'     => 'id,name,team_academy_name,email,country_code,mobile_number,city,state,country,is_active,profile_score',
        'order'      => 'name.asc',
        'limit'      => '200',
    ]);

    if ($result['status'] !== 200) {
        jsonResponse(false, 'Search failed', null, 500);
    }

    $all      = $result['data'] ?? [];
    $q_lower  = strtolower($q);
    $filtered = array_filter($all, function ($c) use ($q_lower) {
        return str_contains(strtolower($c['name'] ?? ''), $q_lower)
            || str_contains(strtolower($c['team_academy_name'] ?? ''), $q_lower)
            || str_contains($c['mobile_number'] ?? '', $q_lower)
            || str_contains(strtolower($c['city'] ?? ''), $q_lower)
            || str_contains(strtolower($c['email'] ?? ''), $q_lower);
    });

    jsonResponse(true, 'Search results', array_values(array_slice($filtered, 0, $limit)));
}

// ============================================================
// GET: Export customers
// ============================================================
if ($method === 'GET' && $action === 'export') {
    $state   = sanitize($_GET['state'] ?? '');
    $country = sanitize($_GET['country'] ?? '');

    $params = [
        'is_deleted' => 'eq.false',
        'order'      => 'name.asc',
        'select'     => '*',
    ];
    if ($state)   $params['state']   = 'eq.' . $state;
    if ($country) $params['country'] = 'eq.' . $country;

    $result = $db->select('customers', $params);

    if ($result['status'] !== 200) {
        jsonResponse(false, 'Export failed', null, 500);
    }

    jsonResponse(true, 'Export data', $result['data'] ?? []);
}

// ============================================================
// POST: Bulk check duplicates
// ============================================================
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'check_duplicate') {
        $mobile      = sanitize($input['mobile_number'] ?? '');
        $countryCode = sanitize($input['country_code'] ?? '');
        $excludeId   = sanitize($input['exclude_id'] ?? '');

        $params = [
            'mobile_number' => 'eq.' . $mobile,
            'country_code'  => 'eq.' . $countryCode,
            'is_deleted'    => 'eq.false',
        ];
        if ($excludeId) $params['id'] = 'neq.' . $excludeId;

        $result = $db->select('customers', $params);
        $isDupe = $result['status'] === 200 && !empty($result['data']);

        jsonResponse(true, $isDupe ? 'Duplicate found' : 'No duplicate', ['is_duplicate' => $isDupe]);
    }
}

// ============================================================
// DELETE: Delete customer (Soft Delete)
// ============================================================
if ($method === 'DELETE' && $action === 'delete') {
    if (!hasPermission('delete')) {
        jsonResponse(false, 'Permission denied', null, 403);
    }

    $id = sanitize($_GET['id'] ?? '');
    if (!$id) jsonResponse(false, 'Customer ID required', null, 400);

    // Verify customer exists and is not already deleted
    $check = $db->select('customers', [
        'id'         => 'eq.' . $id,
        'is_deleted' => 'eq.false',
    ]);

    if ($check['status'] !== 200 || empty($check['data'])) {
        jsonResponse(false, 'Customer not found', null, 404);
    }

    $user = getCurrentUser();

    // Perform soft delete
    $result = $db->update('customers', [
        'is_deleted' => true,
        'is_active'  => false,
        'deleted_by' => $user['name'],
        'deleted_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ], ['id' => 'eq.' . $id]);

    if ($db->isSuccess($result)) {
        $db->logActivity($id, 'deleted', null, $user['name'], 'Customer soft deleted');
        jsonResponse(true, 'Customer deleted successfully');
    } else {
        jsonResponse(false, $result['message'] ?? 'Failed to delete customer', null, 500);
    }
}

jsonResponse(false, 'Invalid request', null, 405);
