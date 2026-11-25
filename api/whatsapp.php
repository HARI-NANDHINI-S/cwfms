<?php
/**
 * WhatsApp Controller API
 * Endpoints for frontend message triggers, templates inspection, and message history
 * Ira Sportswear Lead Management Module
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/whatsapp.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$currentUser = getCurrentUser();
$currentUserId = $currentUser['id'] ?? ($_SESSION['user']['id'] ?? null);

$waService = new WhatsAppService();

// ============================================================
// 1. GET Requests: Templates & Message History
// ============================================================
if ($method === 'GET') {
    // 1.1 List approved templates
    if ($action === 'templates') {
        jsonResponse(true, 'Approved templates retrieved', array_values($waService->getApprovedTemplates()));
    }

    // 1.2 Fetch message history for a specific lead
    if ($action === 'history') {
        requireApiAuth();
        $leadId = normalizeUuidValue($_GET['lead_id'] ?? '');
        if (empty($leadId)) {
            jsonResponse(false, 'Lead ID is required', null, 400);
        }

        // Check lead existence and permission
        $db = new Supabase(true);
        $leadRes = $db->select('leads', ['id' => 'eq.' . $leadId]);
        if (!$db->isSuccess($leadRes) || empty($leadRes['data'])) {
            jsonResponse(false, 'Lead not found', null, 404);
        }
        $lead = $leadRes['data'][0];

        // Permission check: Sales Executive can only view their own leads
        if (!canEditLeadRecord($lead, $currentUser)) {
            jsonResponse(false, 'Permission denied. You can only view WhatsApp history for leads assigned to you.', null, 403);
        }

        $history = $waService->getMessageHistory($leadId);
        jsonResponse(true, 'Message history fetched', $history);
    }

    // 1.3 Service status
    if ($action === 'status') {
        jsonResponse(true, 'WhatsApp service status', [
            'configured'          => $waService->isConfigured(),
            'automations_enabled' => getWhatsAppConfig()['automations_enabled'] ?? true,
            'api_version'         => getWhatsAppConfig()['api_version'] ?? 'v21.0'
        ]);
    }

    jsonResponse(false, 'Invalid action', null, 400);
}

// ============================================================
// 2. POST Requests: Send Manual WhatsApp Message
// ============================================================
if ($method === 'POST') {
    requireApiAuth();

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $leadId = normalizeUuidValue($input['lead_id'] ?? '');
    $templateName = sanitize($input['template_name'] ?? '');
    $parameters = is_array($input['parameters'] ?? null) ? $input['parameters'] : [];

    if (empty($leadId)) {
        jsonResponse(false, 'Lead ID is required', null, 400);
    }
    if (empty($templateName)) {
        jsonResponse(false, 'Template name is required', null, 400);
    }

    $db = new Supabase(true);
    $leadRes = $db->select('leads', ['id' => 'eq.' . $leadId]);
    if (!$db->isSuccess($leadRes) || empty($leadRes['data'])) {
        jsonResponse(false, 'Lead not found', null, 404);
    }
    $lead = $leadRes['data'][0];

    // Role-based authorization:
    // Sales Executive can only send messages for leads assigned to them
    if (!canEditLeadRecord($lead, $currentUser)) {
        jsonResponse(false, 'Permission denied. Sales Executives can only send WhatsApp messages to leads assigned to them.', null, 403);
    }

    // Get customer contact details
    $customerId = $lead['customer_id'] ?? null;
    if (empty($customerId)) {
        jsonResponse(false, 'Lead has no associated customer record', null, 422);
    }

    $custRes = $db->select('customers', ['id' => 'eq.' . $customerId]);
    if (!$db->isSuccess($custRes) || empty($custRes['data'])) {
        jsonResponse(false, 'Associated customer record not found', null, 404);
    }
    $customer = $custRes['data'][0];

    $phone = $customer['mobile_number'] ?? '';
    $countryCode = $customer['country_code'] ?? '+91';

    // Auto-populate missing template parameters if empty
    $templates = $waService->getApprovedTemplates();
    if (!isset($templates[$templateName])) {
        jsonResponse(false, "Unknown template '{$templateName}'", null, 422);
    }

    if (empty($parameters)) {
        if ($templateName === 'ira_lead_acknowledgement') {
            $repName = $currentUser['name'] ?? 'Ira Sales Team';
            $parameters = [$customer['name'] ?? 'Customer', substr($leadId, 0, 8), $repName];
        } elseif ($templateName === 'ira_clarification_request') {
            $parameters = [$customer['name'] ?? 'Customer', substr($leadId, 0, 8), 'Custom artwork & sizing details'];
        } elseif ($templateName === 'ira_order_sheet_update') {
            $parameters = [$customer['name'] ?? 'Customer', $lead['sport'] ?? 'Sportswear', 'prepared and under verification'];
        } elseif ($templateName === 'ira_order_approved') {
            $parameters = [$customer['name'] ?? 'Customer', substr($leadId, 0, 8)];
        } elseif ($templateName === 'ira_moved_to_production') {
            $parameters = [$customer['name'] ?? 'Customer', $lead['sport'] ?? 'Sportswear', $customer['team_academy_name'] ?? ($customer['name'] ?? 'Team')];
        }
    }

    // Attempt sending message
    $sendResult = $waService->sendTemplate($phone, $templateName, $parameters, 'en', $countryCode);

    // Record the attempt in database
    $validPhone = $waService->validatePhoneNumber($phone, $countryCode) ?? $phone;
    $logResult = $waService->logMessageAttempt(
        $leadId,
        $customerId,
        $validPhone,
        $templateName,
        $parameters,
        $sendResult,
        $currentUserId,
        'manual'
    );

    if ($sendResult['success']) {
        jsonResponse(true, 'WhatsApp message sent successfully', [
            'status'              => 'sent',
            'provider_message_id' => $sendResult['provider_message_id'] ?? null,
            'recipient_phone'     => $validPhone,
            'template_name'       => $templateName
        ]);
    } else {
        jsonResponse(false, 'Failed to send WhatsApp message: ' . ($sendResult['error'] ?? 'Unknown error'), [
            'status' => 'failed',
            'error'  => $sendResult['error'] ?? 'Provider error'
        ], 422);
    }
}

jsonResponse(false, 'Method not allowed', null, 405);
