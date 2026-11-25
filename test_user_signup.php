<?php
require_once __DIR__.'/includes/config.php';

// Restricted to authenticated administrators
if (!isset($_SESSION['user']) || !canManageUsers($_SESSION['user'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Permission denied: Administrator session required.']);
    exit;
}

$apiUrl = SUPABASE_URL . '/auth/v1/signup';
$payload = [
    'email' => 'test.user@example.com',
    'password' => 'TestPassword123!'
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'apikey: ' . SUPABASE_ANON_KEY
]);

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

header('Content-Type: application/json');
if ($err) {
    echo json_encode(['success' => false, 'error' => $err]);
} else {
    echo $response;
}
?>
