<?php
// ============================================================
// api/activity.php - Customer Activity Log
// ============================================================
require_once '../includes/config.php';

header('Content-Type: application/json');

requireApiAuth();

$db     = new Supabase(true);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

if ($method === 'GET' && $action === 'list') {
    $customerId = $_GET['customer_id'] ?? '';
    $params = [
        'order'  => 'created_at.desc',
        'select' => '*',
        'limit'  => '50',
    ];

    if ($customerId) {
        $params['customer_id'] = 'eq.' . sanitize($customerId);
    }

    $result = $db->select('activity_log', $params);
    if (!$db->isSuccess($result)) {
        $result = $db->select('activity_logs', $params);
    }

    if ($result['status'] >= 200 && $result['status'] < 300) {
        jsonResponse(true, 'Activity log fetched', $result['data'] ?? []);
    } else {
        jsonResponse(false, 'Failed to fetch activity log', null, 500);
    }
}

jsonResponse(false, 'Invalid request', null, 400);
