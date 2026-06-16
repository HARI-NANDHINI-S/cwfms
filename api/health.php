<?php
// ============================================================
// api/health.php - Database Connection Health Check
// ============================================================
require_once '../includes/config.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

requireApiAuth();

$health = Supabase::testConnection();

if ($health['connected']) {
    jsonResponse(true, $health['message'], $health);
}

jsonResponse(false, $health['message'], $health, 503);
