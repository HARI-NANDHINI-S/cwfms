<?php
// ============================================================
// api/health.php - Database Connection Health Check
// ============================================================
require_once '../includes/config.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

requireApiAuth();

$health = Supabase::testConnection();
$httpCode = $health['http_status'] ?? ($health['connected'] ? 200 : 503);

jsonResponse($health['connected'], $health['message'], $health, $httpCode);
