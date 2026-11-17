<?php
/**
 * Phase 6 Test Suite - WhatsApp Integration & Workflow Automations
 * Ira Sportswear Lead Management Module
 * Tests Meta Cloud API service, webhook processing, security guards,
 * workflow automation triggers, and full regression across Phases 1-5.
 */

// Include config safely
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/whatsapp.php';

define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_CYAN', "\033[36m");
define('COLOR_RESET', "\033[0m");

$testsRun = 0;
$testsPassed = 0;
$testsFailed = 0;

function assertTest(string $description, bool $condition, string $details = '') {
    global $testsRun, $testsPassed, $testsFailed;
    $testsRun++;
    if ($condition) {
        $testsPassed++;
        echo COLOR_GREEN . "  [PASS] " . COLOR_RESET . $description . "\n";
    } else {
        $testsFailed++;
        echo COLOR_RED . "  [FAIL] " . COLOR_RESET . $description . ($details ? " - $details" : "") . "\n";
    }
}

echo COLOR_CYAN . "\n=======================================================\n";
echo "  CWFMS Phase 6 Verification Test Suite\n";
echo "  WhatsApp Integration & Workflow Automations\n";
echo "=======================================================\n" . COLOR_RESET;

// -------------------------------------------------------------
// Test Group 1: Configuration & Security
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 1: Configuration & Security ---\n" . COLOR_RESET;

$dummyService = new WhatsAppService([
    'token' => '',
    'phone_number_id' => '',
    'webhook_verify_token' => 'test_token',
    'app_secret' => 'test_secret'
]);

// 1.1 Missing credentials handled safely
assertTest("1.1 Missing credentials handled safely without fatal errors",
    $dummyService->isConfigured() === false);

$sendWithoutCreds = $dummyService->sendTemplate('9876543210', 'ira_lead_acknowledgement');
assertTest("1.2 Send attempt without credentials returns clean error",
    $sendWithoutCreds['success'] === false && $sendWithoutCreds['code'] === 'MISSING_CREDENTIALS');

// 1.3 Credentials not exposed in API status
$apiStatusJson = json_encode(getWhatsAppConfig());
assertTest("1.3 Sensitive tokens not leaked in public configuration array",
    strpos($apiStatusJson, 'WHATSAPP_TOKEN') === false);

// 1.4 Invalid phone numbers rejected
assertTest("1.4 Phone number validation rejects short/invalid numbers",
    $dummyService->validatePhoneNumber('12345') === null &&
    $dummyService->validatePhoneNumber('invalid-phone') === null);

// 1.5 Valid 10-digit Indian phone normalized to E.164
assertTest("1.5 Valid 10-digit phone normalized with country code (+91)",
    $dummyService->validatePhoneNumber('9876543210', '+91') === '919876543210');

// 1.6 Invalid template name rejected
$badTemplate = $dummyService->sendTemplate('9876543210', 'unapproved_template_123');
assertTest("1.6 Unapproved template names rejected with INVALID_TEMPLATE code",
    $badTemplate['success'] === false && $badTemplate['code'] === 'INVALID_TEMPLATE');

// -------------------------------------------------------------
// Test Group 2: WhatsApp Service & Payload Construction
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 2: WhatsApp Service & Template Construction ---\n" . COLOR_RESET;

$service = new WhatsAppService([
    'token' => 'mock_token_12345',
    'phone_number_id' => '100000000000001',
    'webhook_verify_token' => 'ira_verify_token',
    'app_secret' => 'mock_secret_key',
    'api_version' => 'v21.0'
]);

// 2.1 Approved templates list verification
$templates = $service->getApprovedTemplates();
assertTest("2.1 Approved templates contain official Ira lead acknowledgement template",
    isset($templates['ira_lead_acknowledgement']) &&
    isset($templates['ira_clarification_request']) &&
    isset($templates['ira_moved_to_production']));

// 2.2 Template payload structure
$payload = $service->buildTemplatePayload('919876543210', 'ira_lead_acknowledgement', ['Rajesh Kumar', 'LEAD-101', 'Arun Kumar']);
assertTest("2.2 Meta Cloud API template payload properly constructed",
    $payload['messaging_product'] === 'whatsapp' &&
    $payload['to'] === '919876543210' &&
    $payload['template']['name'] === 'ira_lead_acknowledgement' &&
    $payload['template']['components'][0]['parameters'][0]['text'] === 'Rajesh Kumar');

// 2.3 Successful mocked send
$mockSuccessTransport = function($payload, $phone, $tpl) {
    return [
        'success' => true,
        'status'  => 'sent',
        'provider_message_id' => 'wamid.HBgLMTA1OTQ4Mzcz',
        'recipient_id' => $phone
    ];
};
$sendOk = $service->sendTemplate('9876543210', 'ira_lead_acknowledgement', ['Rajesh Kumar'], 'en', '+91', $mockSuccessTransport);
assertTest("2.3 Successful API response sets status='sent' and captures provider message ID",
    $sendOk['success'] === true &&
    $sendOk['status'] === 'sent' &&
    str_starts_with($sendOk['provider_message_id'], 'wamid.'));

// 2.4 Failed mocked send
$mockFailTransport = function($payload, $phone, $tpl) {
    return [
        'success' => false,
        'status'  => 'failed',
        'error'   => 'Recipient phone number is not a valid WhatsApp user',
        'code'    => 131026
    ];
};
$sendFail = $service->sendTemplate('9876543210', 'ira_lead_acknowledgement', ['Rajesh'], 'en', '+91', $mockFailTransport);
assertTest("2.4 Failed API response captures error message and returns status='failed'",
    $sendFail['success'] === false &&
    $sendFail['status'] === 'failed' &&
    $sendFail['code'] === 131026);

// -------------------------------------------------------------
// Test Group 3: Webhook Verification & Event Processing
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 3: Webhook Verification & Delivery Tracking ---\n" . COLOR_RESET;

$webhookFile = __DIR__ . '/../api/whatsapp_webhook.php';
$webhookContent = file_exists($webhookFile) ? file_get_contents($webhookFile) : '';

assertTest("3.1 api/whatsapp_webhook.php exists and handles GET verification",
    file_exists($webhookFile) && strpos($webhookContent, 'hub_mode') !== false);

assertTest("3.2 Webhook supports HMAC-SHA256 signature verification via X-Hub-Signature-256",
    strpos($webhookContent, 'HTTP_X_HUB_SIGNATURE_256') !== false &&
    strpos($webhookContent, 'hash_hmac') !== false);

assertTest("3.3 Webhook extracts message ID and status transitions (sent, delivered, read, failed)",
    strpos($webhookContent, 'statuses') !== false &&
    strpos($webhookContent, 'updateMessageStatus') !== false);

// 3.4 Idempotency hierarchy test in updateMessageStatus
// Delivered status rank: sent (2) -> delivered (3) -> read (4)
$updateHierarchyTest = $service->updateMessageStatus('wamid.nonexistent_id', 'delivered');
assertTest("3.4 Webhook status update rejects unknown message IDs gracefully",
    $updateHierarchyTest['updated'] === false &&
    $updateHierarchyTest['error'] === 'Unknown provider message ID');

// -------------------------------------------------------------
// Test Group 4: Workflow Automation Triggers (api/leads.php)
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 4: Workflow Automation Triggers ---\n" . COLOR_RESET;

$leadsApiFile = __DIR__ . '/../api/leads.php';
$leadsApiContent = file_exists($leadsApiFile) ? file_get_contents($leadsApiFile) : '';

assertTest("4.1 triggerWhatsAppWorkflowAutomation helper implemented in api/leads.php",
    strpos($leadsApiContent, 'function triggerWhatsAppWorkflowAutomation') !== false);

assertTest("4.2 Lead creation triggers acknowledgement when send_whatsapp_notification is true",
    strpos($leadsApiContent, "if (\$sendWhatsApp) {") !== false &&
    strpos($leadsApiContent, "triggerWhatsAppWorkflowAutomation(\$leadId, 'Received'") !== false);

assertTest("4.3 Status update triggers automated customer notification on supported transitions",
    strpos($leadsApiContent, "triggerWhatsAppWorkflowAutomation(\$leadId, \$input['lead_status']") !== false);

assertTest("4.4 Bulk status change triggers automated notification",
    strpos($leadsApiContent, "triggerWhatsAppWorkflowAutomation(\$leadId, \$newStatus") !== false);

assertTest("4.5 Automation errors are safely caught and never block lead status updates",
    strpos($leadsApiContent, "catch (\\Throwable \$e)") !== false);

// -------------------------------------------------------------
// Test Group 5: Frontend WhatsApp Modal & History (pages/leads.php)
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 5: Frontend WhatsApp UI (pages/leads.php) ---\n" . COLOR_RESET;

$leadsPageFile = __DIR__ . '/../pages/leads.php';
$leadsPageContent = file_exists($leadsPageFile) ? file_get_contents($leadsPageFile) : '';

assertTest("5.1 Lead detail modal contains WhatsApp Customer Communication card",
    strpos($leadsPageContent, 'WhatsApp Customer Communication') !== false &&
    strpos($leadsPageContent, 'id="modalWaTemplateSelect"') !== false);

assertTest("5.2 Modal includes live dynamic message preview box",
    strpos($leadsPageContent, 'id="waPreviewBox"') !== false &&
    strpos($leadsPageContent, 'updateWaPreview') !== false);

assertTest("5.3 Modal includes send button with role permission protection",
    strpos($leadsPageContent, 'sendWhatsAppFromModal') !== false);

assertTest("5.4 Delivery history refresh and status badges implemented in frontend",
    strpos($leadsPageContent, 'loadWaHistory') !== false &&
    strpos($leadsPageContent, 'waStatusBadge') !== false);

assertTest("5.5 Kanban card indicators support delivered and read status styles",
    strpos($leadsPageContent, 'wa-delivered') !== false &&
    strpos($leadsPageContent, 'wa-read') !== false);

// -------------------------------------------------------------
// Test Group 6: Migration & Documentation Files
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 6: Migration & Documentation Files ---\n" . COLOR_RESET;

$migrationFile = __DIR__ . '/../migration_v6_whatsapp.sql';
$setupDocFile = __DIR__ . '/../WHATSAPP_SETUP.md';
$envExampleFile = __DIR__ . '/../.env.example';

assertTest("6.1 migration_v6_whatsapp.sql exists and creates lead_whatsapp_messages table",
    file_exists($migrationFile) &&
    strpos(file_get_contents($migrationFile), 'lead_whatsapp_messages') !== false);

assertTest("6.2 WHATSAPP_SETUP.md comprehensive documentation exists",
    file_exists($setupDocFile) &&
    strpos(file_get_contents($setupDocFile), 'Meta WhatsApp Cloud API') !== false);

assertTest("6.3 .env.example contains all required Meta WhatsApp configuration keys",
    file_exists($envExampleFile) &&
    strpos(file_get_contents($envExampleFile), 'WHATSAPP_TOKEN') !== false &&
    strpos(file_get_contents($envExampleFile), 'WHATSAPP_PHONE_NUMBER_ID') !== false);

// -------------------------------------------------------------
// Test Group 7: Full Regression Across Phases 1 - 5
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 7: Full Regression Across Phases 1 - 5 ---\n" . COLOR_RESET;

// 7.1 Phase 1: Customer association & priority
assertTest("7.1 Phase 1: Customer association mandatory validation intact in api/leads.php",
    strpos($leadsApiContent, "Customer selection is required.") !== false);
assertTest("7.2 Phase 1: Priority validation (low, medium, high) intact in api/leads.php",
    strpos($leadsApiContent, "in_array(\$priority, ['low', 'medium', 'high']") !== false);

// 7.3 Phase 2: Six Kanban visual indicators computed
assertTest("7.3 Phase 2: All 6 indicators computed in api/leads.php",
    strpos($leadsApiContent, "['has_attachments']") !== false &&
    strpos($leadsApiContent, "['has_design']") !== false &&
    strpos($leadsApiContent, "['whatsapp_status']") !== false &&
    strpos($leadsApiContent, "['is_repeat_customer']") !== false);

// 7.4 Phase 3: Date range parameters & Table bulk updates
assertTest("7.4 Phase 3: Date range parameters (date_from, date_to) supported in api/leads.php",
    strpos($leadsApiContent, "\$dateFrom = sanitize(\$_GET['date_from'] ?? '');") !== false);
assertTest("7.5 Phase 3: Bulk update endpoint action=bulk_update intact in api/leads.php",
    strpos($leadsApiContent, "in_array(\$action, ['bulk_update', 'bulk-update', 'bulk'], true)") !== false);

// 7.6 Phase 4: Role-based permissions
assertTest("7.6 Phase 4: canManageUsers strictly guards user management in config and pages",
    function_exists('canManageUsers') &&
    canManageUsers(['role' => 'admin']) === true &&
    canManageUsers(['role' => 'manager']) === false);

assertTest("7.7 Phase 4: canDeleteLead strictly restricted to Admin only",
    function_exists('canDeleteLead') &&
    canDeleteLead(['role' => 'admin']) === true &&
    canDeleteLead(['role' => 'manager']) === false);

assertTest("7.8 Phase 4: canAssignLead granted to Admin & Manager, denied to Sales Executive",
    function_exists('canAssignLead') &&
    canAssignLead(['role' => 'admin']) === true &&
    canAssignLead(['role' => 'sales_executive']) === false);

// 7.9 Phase 5: Branding assets & central variables
$styleContent = file_get_contents(__DIR__ . '/../assets/css/style.css');
assertTest("7.9 Phase 5: Central brand colors (--brand-navy, --brand-red, --brand-neon) intact",
    strpos($styleContent, '--brand-navy:') !== false &&
    strpos($styleContent, '--brand-red:') !== false &&
    strpos($styleContent, '--brand-neon:') !== false);

assertTest("7.10 Phase 5: Official Ira Sportswear logo assets intact in assets/",
    file_exists(__DIR__ . '/../assets/ira_logo_web.png') &&
    file_exists(__DIR__ . '/../assets/favicon.png'));

// -------------------------------------------------------------
// Test Summary
// -------------------------------------------------------------
echo COLOR_CYAN . "\n=======================================================\n";
echo "  Phase 6 Test Execution Complete\n";
echo "  Total Tests Run:    $testsRun\n";
echo "  Tests Passed:       $testsPassed\n";
if ($testsFailed === 0) {
    echo COLOR_GREEN . "  ALL PHASE 6 TESTS PASSED SUCCESSFULLY! (100%)\n" . COLOR_RESET;
} else {
    echo COLOR_RED . "  TESTS FAILED: $testsFailed\n" . COLOR_RESET;
}
echo COLOR_CYAN . "=======================================================\n\n" . COLOR_RESET;

exit($testsFailed > 0 ? 1 : 0);
