<?php
/**
 * WhatsApp Cloud API Webhook Endpoint
 * Meta Graph API Webhook Handler for Ira Sportswear Lead Management
 * Supports hub.mode verification and real-time status delivery tracking (sent, delivered, read, failed).
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/whatsapp.php';

$method = $_SERVER['REQUEST_METHOD'];
$config = getWhatsAppConfig();
$waService = new WhatsAppService($config);

// ============================================================
// 1. Meta Webhook Verification (GET)
// ============================================================
if ($method === 'GET') {
    $hubMode        = $_GET['hub_mode'] ?? ($_GET['hub.mode'] ?? '');
    $hubVerifyToken = $_GET['hub_verify_token'] ?? ($_GET['hub.verify_token'] ?? '');
    $hubChallenge   = $_GET['hub_challenge'] ?? ($_GET['hub.challenge'] ?? '');

    $expectedToken  = $config['webhook_verify_token'] ?? '';

    if ($hubMode === 'subscribe' && !empty($expectedToken) && hash_equals($expectedToken, $hubVerifyToken)) {
        header('Content-Type: text/plain');
        http_response_code(200);
        echo $hubChallenge;
        exit;
    }

    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Webhook verification failed: Invalid verify token']);
    exit;
}

// ============================================================
// 2. Incoming Event Processing (POST)
// ============================================================
if ($method === 'POST') {
    $rawBody = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

    // Fail-closed signature verification:
    // Requires WHATSAPP_APP_SECRET to be configured and strictly validates Meta's X-Hub-Signature-256
    $appSecret = $config['app_secret'] ?? '';
    if (empty($appSecret)) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Webhook security configuration error: WHATSAPP_APP_SECRET is not configured.']);
        exit;
    }

    if (empty($signature)) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Missing X-Hub-Signature-256 header.']);
        exit;
    }

    $expectedSig = 'sha256=' . hash_hmac('sha256', $rawBody, $appSecret);
    if (!hash_equals($expectedSig, $signature)) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid webhook signature.']);
        exit;
    }

    $payload = json_decode($rawBody, true);
    if (!is_array($payload)) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid JSON payload']);
        exit;
    }

    // Process Meta WhatsApp status updates
    $processedCount = 0;
    if (!empty($payload['entry']) && is_array($payload['entry'])) {
        foreach ($payload['entry'] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                $statuses = $value['statuses'] ?? [];

                foreach ($statuses as $st) {
                    $msgId  = $st['id'] ?? '';
                    $status = $st['status'] ?? ''; // sent, delivered, read, failed

                    $errMessage = null;
                    if (!empty($st['errors']) && is_array($st['errors'])) {
                        $errMessage = $st['errors'][0]['title'] ?? ($st['errors'][0]['message'] ?? 'Delivery failed');
                    }

                    if ($msgId && $status) {
                        $waService->updateMessageStatus($msgId, $status, $errMessage);
                        $processedCount++;
                    }
                }
            }
        }
    }

    // Acknowledge receipt to Meta immediately with HTTP 200
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode([
        'status'    => 'EVENT_RECEIVED',
        'processed' => $processedCount
    ]);
    exit;
}

http_response_code(405);
header('Content-Type: application/json');
echo json_encode(['error' => 'Method not allowed']);
exit;
