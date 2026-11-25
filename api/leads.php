<?php
require_once '../includes/config.php';
require_once '../includes/whatsapp.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

requireApiAuth();

$db = new Supabase(true);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$currentUser = getCurrentUser();
$currentUserId = $_SESSION['user']['id'] ?? null;

/**
 * Triggers automated WhatsApp customer notification on lead workflow event.
 * Failures in notification delivery must never disrupt or prevent lead status update.
 */
function triggerWhatsAppWorkflowAutomation(string $leadId, string $newStatus, ?string $oldStatus = null, $currentUser = null): void {
    try {
        $waConfig = getWhatsAppConfig();
        if (empty($waConfig['automations_enabled'])) {
            return;
        }

        $stages = $waConfig['automation_stages'] ?? [];
        if (!in_array($newStatus, $stages, true)) {
            return;
        }

        $templateMap = [
            'Received'               => 'ira_lead_acknowledgement',
            'Awaiting Clarification' => 'ira_clarification_request',
            'Order Sheet Prepared'   => 'ira_order_sheet_update',
            'Approved'               => 'ira_order_approved',
            'Moved to Production'    => 'ira_moved_to_production',
        ];

        $template = $templateMap[$newStatus] ?? null;
        if (!$template) return;

        $db = new Supabase(true);
        $leadRes = $db->select('leads', ['id' => 'eq.' . $leadId]);
        if (!$db->isSuccess($leadRes) || empty($leadRes['data'])) return;
        $lead = $leadRes['data'][0];

        $custId = $lead['customer_id'] ?? null;
        if (!$custId) return;

        $custRes = $db->select('customers', ['id' => 'eq.' . $custId]);
        if (!$db->isSuccess($custRes) || empty($custRes['data'])) return;
        $cust = $custRes['data'][0];

        $phone = $cust['mobile_number'] ?? '';
        $countryCode = $cust['country_code'] ?? '+91';

        $waService = new WhatsAppService($waConfig);
        $validPhone = $waService->validatePhoneNumber($phone, $countryCode);
        if (!$validPhone) return;

        $customerName = $cust['name'] ?? 'Customer';
        $refNo = substr($leadId, 0, 8);
        $repName = $currentUser['name'] ?? 'Ira Sales Team';
        $sport = $lead['sport'] ?? 'Sportswear';
        $academy = $cust['team_academy_name'] ?? ($cust['name'] ?? 'Team');

        $params = [];
        if ($template === 'ira_lead_acknowledgement') {
            $params = [$customerName, $refNo, $repName];
        } elseif ($template === 'ira_clarification_request') {
            $params = [$customerName, $refNo, 'Design specifications & player sizes'];
        } elseif ($template === 'ira_order_sheet_update') {
            $params = [$customerName, $sport, 'prepared'];
        } elseif ($template === 'ira_order_approved') {
            $params = [$customerName, $refNo];
        } elseif ($template === 'ira_moved_to_production') {
            $params = [$customerName, $sport, $academy];
        }

        $res = $waService->sendTemplate($validPhone, $template, $params, 'en', $countryCode);
        $userId = $currentUser['id'] ?? ($_SESSION['user']['id'] ?? null);
        $waService->logMessageAttempt($leadId, $custId, $validPhone, $template, $params, $res, $userId, 'automated');
    } catch (\Throwable $e) {
        error_log("WhatsApp automation error for lead {$leadId}: " . $e->getMessage());
    }
}

function leadUploadDir() {
    $dir = __DIR__ . '/../uploads/leads';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    return $dir;
}

function savedFileMeta($file): array {
    if (!isset($file) || !is_array($file) || empty($file['name'])) {
        return [];
    }

    if (isset($file['error']) && $file['error'] !== UPLOAD_ERR_OK && $file['error'] !== UPLOAD_ERR_NO_FILE) {
        return ['error' => 'File upload error (code ' . $file['error'] . ')'];
    }

    // Path traversal and null byte check
    if (strpos($file['name'], "\0") !== false || strpos($file['name'], '..') !== false) {
        return ['error' => 'Security violation: invalid filename or path traversal detected.'];
    }

    // Dangerous double extension check (e.g. payload.php.png)
    if (preg_match('/\.(php[0-9]?|phtml|phar|sh|bash|exe|pl|py|cgi|asp|aspx|jsp|svg|js|html?)\./i', $file['name'])) {
        return ['error' => 'Security violation: dangerous double file extension detected.'];
    }

    $allowed = ['pdf', 'ai', 'eps', 'png', 'jpg', 'jpeg', 'zip', 'xlsx', 'xls', 'csv'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        return ['error' => 'Invalid file type. Allowed formats: PDF, AI, EPS, PNG, JPG, ZIP, XLSX, XLS, CSV.'];
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        return ['error' => 'File exceeds the maximum allowed size of 10 MB.'];
    }

    // Binary MIME type inspection (magic byte check)
    if (function_exists('finfo_open') && !empty($file['tmp_name']) && file_exists($file['tmp_name'])) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = strtolower(trim((string)finfo_file($finfo, $file['tmp_name'])));
        finfo_close($finfo);

        $allowedMimes = [
            'pdf'  => ['application/pdf', 'application/x-pdf'],
            'png'  => ['image/png'],
            'jpg'  => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'zip'  => ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
            'xls'  => ['application/vnd.ms-excel', 'application/msexcel', 'application/x-msexcel', 'application/octet-stream'],
            'csv'  => ['text/csv', 'text/plain', 'application/csv', 'text/x-csv'],
            'ai'   => ['application/postscript', 'application/illustrator', 'application/pdf', 'application/octet-stream'],
            'eps'  => ['application/postscript', 'image/x-eps', 'image/eps', 'application/octet-stream'],
        ];

        $disallowedMimes = [
            'text/x-php', 'application/x-php', 'application/x-httpd-php',
            'application/x-executable', 'application/x-sh', 'application/x-bat',
            'application/x-msdownload', 'application/javascript', 'text/html'
        ];

        if (in_array($detectedMime, $disallowedMimes, true)) {
            return ['error' => 'Security violation: uploaded file contains executable or script content.'];
        }

        if (isset($allowedMimes[$ext]) && !in_array($detectedMime, $allowedMimes[$ext], true)) {
            return ['error' => "File content does not match the expected type for extension .{$ext} (detected: {$detectedMime})."];
        }
    }

    $safeName = preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($file['name']));
    $storedFilename = time() . '_' . bin2hex(random_bytes(6)) . '_' . $safeName;
    $target = leadUploadDir() . '/' . $storedFilename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return ['error' => 'File upload could not be saved to disk.'];
    }

    return ['name' => $safeName, 'path' => 'uploads/leads/' . $storedFilename, 'type' => $ext, 'size' => $file['size']];
}

function buildUserMap(): array {
    $db = new Supabase(true);
    $result = $db->select('user_roles', [
        'select' => 'id,user_name,user_email,role,is_active',
        'order' => 'user_name.asc',
    ]);
    $users = $result['data'] ?? [];
    $map = [];
    foreach ($users as $u) {
        $map[$u['id']] = $u;
    }
    return $map;
}

function addLeadActivity($leadId, $action, $details, $changedBy, $oldStatus = null, $newStatus = null, $customerId = null) {
    $db = new Supabase(true);
    $db->insert('lead_activity_log', [
        'lead_id' => $leadId,
        'action' => $action,
        'details' => $details,
        'old_status' => $oldStatus,
        'new_status' => $newStatus,
        'changed_by' => $changedBy,
    ]);

    // Bidirectional sync to customer activity log if customer_id is present
    try {
        if (!$customerId && $leadId) {
            $leadRes = $db->select('leads', ['id' => 'eq.' . $leadId, 'select' => 'customer_id']);
            if (!empty($leadRes['data'][0]['customer_id'])) {
                $customerId = $leadRes['data'][0]['customer_id'];
            }
        }
        if ($customerId) {
            $shortId = substr($leadId, 0, 8);
            $user = getCurrentUser();
            $userName = $user['name'] ?? ($changedBy ?: 'System');
            $db->logActivity(
                $customerId,
                'lead_event',
                ['lead_id' => $leadId, 'action' => $action, 'status' => $newStatus ?? $oldStatus],
                $userName,
                "Lead #{$shortId} - {$action}: {$details}"
            );
        }
    } catch (\Throwable $e) {
        error_log("Failed to sync customer activity log: " . $e->getMessage());
    }
}

function canEditLead($lead, $userRole, $userId): bool {
    return canEditLeadRecord($lead, ['role' => $userRole, 'id' => $userId]);
}

if ($method === 'GET' && $action === 'get') {
    $leadId = normalizeUuidValue($_GET['id'] ?? '');
    if (!$leadId) jsonResponse(false, 'Lead ID required', null, 400);

    $result = $db->select('leads', ['id' => 'eq.' . $leadId, 'is_deleted' => 'eq.false', 'select' => '*']);
    if ($result['status'] !== 200 || empty($result['data'])) {
        jsonResponse(false, 'Lead not found', null, 404);
    }

    $lead = $result['data'][0];
    $userRole = normalizeRole($currentUser['role'] ?? 'staff');
    if ($userRole === 'sales_executive' && ($lead['assigned_to'] ?? '') !== $currentUserId) {
        jsonResponse(false, 'Permission denied. Sales Executives can only access leads assigned to them.', null, 403);
    }

    $lead['products_interested'] = is_string($lead['products_interested'] ?? null) ? json_decode($lead['products_interested'], true) ?: [] : ($lead['products_interested'] ?? []);
    $lead['custom_design_file'] = is_string($lead['custom_design_file'] ?? null) ? json_decode($lead['custom_design_file'], true) ?: [] : ($lead['custom_design_file'] ?? []);
    $lead['player_list_file'] = is_string($lead['player_list_file'] ?? null) ? json_decode($lead['player_list_file'], true) ?: [] : ($lead['player_list_file'] ?? []);

    $lead['assigned_user'] = (new Supabase(true))->select('user_roles', ['id' => 'eq.' . ($lead['assigned_to'] ?? ''), 'select' => 'id,user_name,user_email,role'])->data[0] ?? null;
    $lead['customer'] = (new Supabase(true))->select('customers', ['id' => 'eq.' . ($lead['customer_id'] ?? ''), 'select' => 'id,name,team_academy_name,email,mobile_number,city,state,country'])->data[0] ?? null;
    $lead['priority'] = strtolower($lead['priority'] ?? 'medium');

    $customFiles = $lead['custom_design_file'] ?? [];
    $playerFiles = $lead['player_list_file'] ?? [];
    $lead['has_attachments'] = (!empty($customFiles) && is_array($customFiles) && count($customFiles) > 0) ||
                               (!empty($playerFiles) && is_array($playerFiles) && count($playerFiles) > 0);
    $lead['has_design'] = (!empty($customFiles) && is_array($customFiles) && count($customFiles) > 0) ||
                          (!empty($lead['design_link'])) ||
                          (!empty($lead['submission_id']));
    $lead['whatsapp_status'] = $lead['whatsapp_status'] ?? (!empty($lead['send_whatsapp_notification']) ? 'requested' : 'not_requested');

    $isRepeat = false;
    if (!empty($lead['customer_id'])) {
        $countResult = (new Supabase(true))->count('leads', ['customer_id' => 'eq.' . $lead['customer_id'], 'is_deleted' => 'eq.false']);
        $isRepeat = ($countResult['count'] ?? 0) > 1;
    }
    $lead['is_repeat_customer'] = $isRepeat;

    // Phase 4: Explicit permission capabilities for authenticated user on this lead
    $lead['can_edit'] = canEditLeadRecord($lead, $currentUser);
    $lead['can_change_status'] = canChangeLeadStatus($lead, $currentUser);
    $lead['can_assign'] = canAssignLead($currentUser);
    $lead['can_delete'] = canDeleteLead($currentUser);

    jsonResponse(true, 'Lead fetched', $lead);
}

if ($method === 'GET' && in_array($action, ['attachment', 'download', 'preview'], true)) {
    $leadId = normalizeUuidValue($_GET['lead_id'] ?? $_GET['id'] ?? '');
    if (!$leadId) {
        jsonResponse(false, 'Lead ID required', null, 400);
    }

    $leadRes = $db->select('leads', ['id' => 'eq.' . $leadId, 'is_deleted' => 'eq.false']);
    if ($leadRes['status'] !== 200 || empty($leadRes['data'])) {
        jsonResponse(false, 'Lead not found', null, 404);
    }

    $lead = $leadRes['data'][0];
    $userRole = normalizeRole($currentUser['role'] ?? 'staff');
    if ($userRole === 'sales_executive' && ($lead['assigned_to'] ?? '') !== $currentUserId) {
        jsonResponse(false, 'Permission denied. Sales Executives can only access attachments for leads assigned to them.', null, 403);
    }

    $fileType = sanitize($_GET['type'] ?? 'design');
    $index = isset($_GET['index']) ? (int)$_GET['index'] : 0;
    $rawField = ($fileType === 'roster' || $fileType === 'player_list')
        ? ($lead['player_list_file'] ?? [])
        : ($lead['custom_design_file'] ?? []);

    $files = is_string($rawField) ? (json_decode($rawField, true) ?: []) : (is_array($rawField) ? $rawField : []);
    if (empty($files)) {
        jsonResponse(false, 'No attachments found for this lead.', null, 404);
    }

    $targetMeta = null;
    $reqFile = basename(sanitize($_GET['file'] ?? ''));
    if (!empty($reqFile)) {
        foreach ($files as $f) {
            if (($f['name'] ?? '') === $reqFile || basename($f['path'] ?? '') === $reqFile) {
                $targetMeta = $f;
                break;
            }
        }
    }
    if (!$targetMeta && isset($files[$index])) {
        $targetMeta = $files[$index];
    }
    if (!$targetMeta || empty($targetMeta['path'])) {
        jsonResponse(false, 'Requested attachment not found.', null, 404);
    }

    $uploadDir = leadUploadDir();
    $uploadBase = rtrim(realpath($uploadDir) ?: $uploadDir, '\\/') . DIRECTORY_SEPARATOR;
    $storedFilename = basename($targetMeta['path']);

    if (strpos($storedFilename, '..') !== false || strpos($storedFilename, "\0") !== false) {
        jsonResponse(false, 'Security violation: invalid file path.', null, 403);
    }

    $fullPath = $uploadDir . '/' . $storedFilename;
    $resolvedPath = realpath($fullPath);

    if (!$resolvedPath || !file_exists($resolvedPath) || !is_file($resolvedPath)) {
        jsonResponse(false, 'Attachment file not found on disk.', null, 404);
    }

    // Verify resolved path is strictly within the uploads directory
    if ($uploadBase && !str_starts_with($resolvedPath, $uploadBase)) {
        jsonResponse(false, 'Security violation: directory traversal detected.', null, 403);
    }

    $ext = strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION));
    $allowed = ['pdf', 'ai', 'eps', 'png', 'jpg', 'jpeg', 'zip', 'xlsx', 'xls', 'csv'];
    if (!in_array($ext, $allowed, true)) {
        jsonResponse(false, 'Access to this file type is forbidden.', null, 403);
    }

    $mimeTypes = [
        'pdf'  => 'application/pdf',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'zip'  => 'application/zip',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xls'  => 'application/vnd.ms-excel',
        'csv'  => 'text/csv; charset=UTF-8',
        'ai'   => 'application/postscript',
        'eps'  => 'application/postscript',
    ];
    $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';
    $downloadName = preg_replace('/[^A-Za-z0-9_.-]/', '_', $targetMeta['name'] ?? $storedFilename);

    $isInline = in_array($ext, ['pdf', 'png', 'jpg', 'jpeg'], true) && ($action === 'preview' || !isset($_GET['download']));
    $disposition = ($isInline ? 'inline' : 'attachment') . '; filename="' . $downloadName . '"';

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: ' . $contentType);
    header('Content-Disposition: ' . $disposition);
    header('Content-Length: ' . filesize($resolvedPath));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-transform, max-age=3600');
    header('Pragma: private');

    readfile($resolvedPath);
    exit;
}

if ($method === 'GET' && in_array($action, ['list', ''], true)) {
    $params = ['is_deleted' => 'eq.false', 'select' => '*', 'order' => 'enquiry_date.desc'];
    $assignedTo = normalizeUuidValue($_GET['assigned_to'] ?? '');
    $sport = sanitize($_GET['sport'] ?? '');
    $leadSource = sanitize($_GET['lead_source'] ?? '');
    $leadStatus = sanitize($_GET['lead_status'] ?? '');
    $dateFrom = sanitize($_GET['date_from'] ?? '');
    $dateTo = sanitize($_GET['date_to'] ?? '');

    if (!empty($dateFrom)) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) || !strtotime($dateFrom)) {
            jsonResponse(false, 'Invalid From Date format. Expected YYYY-MM-DD.', null, 422);
        }
    }
    if (!empty($dateTo)) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) || !strtotime($dateTo)) {
            jsonResponse(false, 'Invalid To Date format. Expected YYYY-MM-DD.', null, 422);
        }
    }
    if (!empty($dateFrom) && !empty($dateTo) && $dateFrom > $dateTo) {
        jsonResponse(false, 'From Date cannot be later than To Date.', null, 422);
    }

    $userRole = normalizeRole($currentUser['role'] ?? 'staff');
    if ($userRole === 'sales_executive') {
        $params['assigned_to'] = 'eq.' . $currentUserId;
    } elseif (!empty($assignedTo)) {
        $params['assigned_to'] = 'eq.' . $assignedTo;
    }

    if (!empty($sport)) $params['sport'] = 'eq.' . $sport;
    if (!empty($leadSource)) $params['lead_source'] = 'eq.' . $leadSource;
    if (!empty($leadStatus)) $params['lead_status'] = 'eq.' . $leadStatus;

    if (!empty($dateFrom) && !empty($dateTo)) {
        $params['and'] = '(enquiry_date.gte.' . $dateFrom . ',enquiry_date.lte.' . $dateTo . ')';
    } elseif (!empty($dateFrom)) {
        $params['enquiry_date'] = 'gte.' . $dateFrom;
    } elseif (!empty($dateTo)) {
        $params['enquiry_date'] = 'lte.' . $dateTo;
    }

    $result = $db->select('leads', $params);
    if ($result['status'] !== 200) {
        jsonResponse(false, 'Failed to load leads', null, 500);
    }

    $users = buildUserMap();
    $customers = [];
    $customersResult = (new Supabase(true))->select('customers', ['select' => 'id,name,team_academy_name', 'is_deleted' => 'eq.false']);
    if ($customersResult['status'] === 200) {
        foreach ($customersResult['data'] ?? [] as $c) {
            $customers[$c['id']] = $c;
        }
    }

    // Batch query customer lead frequencies to identify repeat customers
    $customerLeadCounts = [];
    $allLeadCustomers = (new Supabase(true))->select('leads', ['is_deleted' => 'eq.false', 'select' => 'customer_id']);
    if ($allLeadCustomers['status'] === 200 && is_array($allLeadCustomers['data'] ?? null)) {
        foreach ($allLeadCustomers['data'] as $lc) {
            $cid = $lc['customer_id'] ?? null;
            if ($cid) {
                $customerLeadCounts[$cid] = ($customerLeadCounts[$cid] ?? 0) + 1;
            }
        }
    }

    $rows = $result['data'] ?? [];

    // In-memory date filter safety net
    if (!empty($dateFrom)) {
        $rows = array_values(array_filter($rows, fn($r) => !empty($r['enquiry_date']) && $r['enquiry_date'] >= $dateFrom));
    }
    if (!empty($dateTo)) {
        $rows = array_values(array_filter($rows, fn($r) => !empty($r['enquiry_date']) && $r['enquiry_date'] <= $dateTo));
    }
    foreach ($rows as &$lead) {
        $lead['assigned_user'] = $users[$lead['assigned_to']] ?? null;
        $lead['customer'] = $customers[$lead['customer_id']] ?? null;
        $lead['priority'] = strtolower($lead['priority'] ?? 'medium');
        if (is_string($lead['products_interested'] ?? null)) {
            $lead['products_interested'] = json_decode($lead['products_interested'], true) ?: [];
        }
        if (is_string($lead['custom_design_file'] ?? null)) {
            $lead['custom_design_file'] = json_decode($lead['custom_design_file'], true) ?: [];
        }
        if (is_string($lead['player_list_file'] ?? null)) {
            $lead['player_list_file'] = json_decode($lead['player_list_file'], true) ?: [];
        }

        $customFiles = $lead['custom_design_file'] ?? [];
        $playerFiles = $lead['player_list_file'] ?? [];
        $lead['has_attachments'] = (!empty($customFiles) && is_array($customFiles) && count($customFiles) > 0) ||
                                   (!empty($playerFiles) && is_array($playerFiles) && count($playerFiles) > 0);
        $lead['has_design'] = (!empty($customFiles) && is_array($customFiles) && count($customFiles) > 0) ||
                              (!empty($lead['design_link'])) ||
                              (!empty($lead['submission_id']));
        $lead['whatsapp_status'] = $lead['whatsapp_status'] ?? (!empty($lead['send_whatsapp_notification']) ? 'requested' : 'not_requested');
        $lead['is_repeat_customer'] = !empty($lead['customer_id']) && (($customerLeadCounts[$lead['customer_id']] ?? 0) > 1);
    }
    unset($lead);

    jsonResponse(true, 'Leads fetched', $rows);
}

if ($method === 'GET' && $action === 'calendar') {
    $rows = [];
    $params = ['is_deleted' => 'eq.false', 'select' => 'id,customer_id,lead_status,assigned_to,enquiry_date,lead_source,sport', 'order' => 'enquiry_date.asc'];
    $assignedTo = normalizeUuidValue($_GET['assigned_to'] ?? '');
    $sport = sanitize($_GET['sport'] ?? '');
    $leadSource = sanitize($_GET['lead_source'] ?? '');
    $dateFrom = sanitize($_GET['date_from'] ?? '');
    $dateTo = sanitize($_GET['date_to'] ?? '');

    if (!empty($dateFrom)) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) || !strtotime($dateFrom)) {
            jsonResponse(false, 'Invalid From Date format. Expected YYYY-MM-DD.', null, 422);
        }
    }
    if (!empty($dateTo)) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) || !strtotime($dateTo)) {
            jsonResponse(false, 'Invalid To Date format. Expected YYYY-MM-DD.', null, 422);
        }
    }
    if (!empty($dateFrom) && !empty($dateTo) && $dateFrom > $dateTo) {
        jsonResponse(false, 'From Date cannot be later than To Date.', null, 422);
    }

    $userRole = normalizeRole($currentUser['role'] ?? 'staff');
    if ($userRole === 'sales_executive') {
        $params['assigned_to'] = 'eq.' . $currentUserId;
    } elseif (!empty($assignedTo)) {
        $params['assigned_to'] = 'eq.' . $assignedTo;
    }

    if (!empty($sport)) $params['sport'] = 'eq.' . $sport;
    if (!empty($leadSource)) $params['lead_source'] = 'eq.' . $leadSource;

    if (!empty($dateFrom) && !empty($dateTo)) {
        $params['and'] = '(enquiry_date.gte.' . $dateFrom . ',enquiry_date.lte.' . $dateTo . ')';
    } elseif (!empty($dateFrom)) {
        $params['enquiry_date'] = 'gte.' . $dateFrom;
    } elseif (!empty($dateTo)) {
        $params['enquiry_date'] = 'lte.' . $dateTo;
    }

    $listResult = $db->select('leads', $params);
    if ($listResult['status'] === 200) {
        $rawRows = $listResult['data'] ?? [];
        if (!empty($dateFrom)) {
            $rawRows = array_values(array_filter($rawRows, fn($r) => !empty($r['enquiry_date']) && $r['enquiry_date'] >= $dateFrom));
        }
        if (!empty($dateTo)) {
            $rawRows = array_values(array_filter($rawRows, fn($r) => !empty($r['enquiry_date']) && $r['enquiry_date'] <= $dateTo));
        }

        $customers = [];
        $customersResult = (new Supabase(true))->select('customers', ['select' => 'id,name', 'is_deleted' => 'eq.false']);
        if ($customersResult['status'] === 200) {
            foreach ($customersResult['data'] ?? [] as $c) $customers[$c['id']] = $c;
        }
        $users = buildUserMap();
        foreach ($rawRows as $lead) {
            $lead['customer'] = $customers[$lead['customer_id']] ?? null;
            $lead['assigned_user'] = $users[$lead['assigned_to']] ?? null;
            $rows[] = $lead;
        }
    }
    jsonResponse(true, 'Calendar data', $rows);
}

if ($method === 'GET' && $action === 'sales-users') {
    $result = $db->select('user_roles', [
        'select' => 'id,user_name,user_email,role,is_active',
        'is_active' => 'eq.true',
        'order' => 'user_name.asc',
    ]);
    if ($result['status'] !== 200) {
        jsonResponse(false, 'Failed to load sales users', null, 500);
    }
    jsonResponse(true, 'Sales users fetched', $result['data'] ?? []);
}

if ($method === 'GET' && in_array($action, ['customers-list', 'customer-list'], true)) {
    $result = $db->select('customers', [
        'select' => 'id,name,team_academy_name,mobile_number,country_code,email,city,state',
        'is_deleted' => 'eq.false',
        'order' => 'name.asc',
    ]);
    if ($result['status'] !== 200) {
        jsonResponse(false, 'Failed to load customers list', null, 500);
    }
    jsonResponse(true, 'Customers list fetched', $result['data'] ?? []);
}

if ($method === 'GET' && $action === 'stats') {
    $result = $db->select('leads', ['select' => 'lead_status', 'is_deleted' => 'eq.false']);
    $rows = $result['data'] ?? [];
    $stats = [
        'total' => count($rows),
        'received' => 0,
        'analysis' => 0,
        'awaiting_clarification' => 0,
        'order_sheet_prepared' => 0,
        'verified' => 0,
        'awaiting_approval' => 0,
        'approved' => 0,
        'awaiting_payment' => 0,
        'moved_to_production' => 0,
        'lost' => 0,
    ];
    foreach ($rows as $row) {
        $statusKey = strtolower(str_replace(' ', '_', trim((string)($row['lead_status'] ?? ''))));
        if (isset($stats[$statusKey])) {
            $stats[$statusKey]++;
        }
    }
    jsonResponse(true, 'Lead stats', $stats);
}

if ($method === 'GET' && $action === 'activity') {
    $leadId = normalizeUuidValue($_GET['lead_id'] ?? '');
    if (!$leadId) jsonResponse(false, 'Lead ID required', null, 400);

    $userRole = normalizeRole($currentUser['role'] ?? 'staff');
    if ($userRole === 'sales_executive') {
        $leadCheck = $db->select('leads', ['id' => 'eq.' . $leadId, 'select' => 'assigned_to']);
        if (empty($leadCheck['data']) || ($leadCheck['data'][0]['assigned_to'] ?? '') !== $currentUserId) {
            jsonResponse(false, 'Permission denied. Sales Executives can only view activity for leads assigned to them.', null, 403);
        }
    }

    $result = $db->select('lead_activity_log', [
        'lead_id' => 'eq.' . $leadId,
        'order' => 'created_at.desc',
    ]);
    if ($result['status'] !== 200) {
        jsonResponse(false, 'Failed to load activity logs', null, 500);
    }

    $users = buildUserMap();
    foreach ($result['data'] ?? [] as &$item) {
        $item['changed_by_user'] = $users[$item['changed_by']] ?? null;
    }
    unset($item);
    jsonResponse(true, 'Lead activity fetched', $result['data'] ?? []);
}

if ($method === 'POST' && $action === 'create') {
    $input = $_POST;
    $leadSource = sanitize($input['lead_source'] ?? '');
    $enquiryDate = sanitize($input['enquiry_date'] ?? '');
    $sports = $input['sport'] ?? '';
    $designSource = sanitize($input['design_source'] ?? '');
    $customerId = normalizeUuidValue($input['customer_id'] ?? '');
    $assignedTo = sanitize($input['assigned_to'] ?? '');
    $leadStatus = sanitize($input['lead_status'] ?? 'Received');
    $notes = sanitize($input['notes'] ?? '');
    $sendWhatsApp = !empty($input['send_whatsapp_notification']);
    $rawPriority = sanitize($input['priority'] ?? 'medium');
    $priority = strtolower(trim($rawPriority ?: 'medium'));

    $products = $input['products_interested'] ?? [];
    if (!is_array($products)) {
        $products = json_decode((string)$products, true) ?: [];
    }

    if (empty($customerId)) {
        jsonResponse(false, 'Customer selection is required.', null, 422);
    }
    $custCheck = $db->select('customers', ['id' => 'eq.' . $customerId, 'is_deleted' => 'eq.false', 'select' => 'id,name,team_academy_name']);
    if (!$db->isSuccess($custCheck) || empty($custCheck['data'])) {
        jsonResponse(false, 'Selected customer does not exist or has been deleted.', null, 404);
    }
    if (!in_array($priority, ['low', 'medium', 'high'], true)) {
        jsonResponse(false, 'Invalid priority level. Allowed values: low, medium, high.', null, 422);
    }

    if (!in_array($leadSource, ['Kit Builder', 'Website', 'WhatsApp', 'Email', 'Phone', 'Direct', 'Referral'], true)) {
        jsonResponse(false, 'Invalid lead source.', null, 400);
    }
    if (empty($enquiryDate)) {
        jsonResponse(false, 'Enquiry date is required.', null, 400);
    }
    if (empty($products)) {
        jsonResponse(false, 'Select at least one product.', null, 400);
    }
    if (empty($sports)) {
        jsonResponse(false, 'Sport is required.', null, 400);
    }
    if (!in_array($designSource, ['Kit Builder', 'Design Link', 'Custom Design'], true)) {
        jsonResponse(false, 'Design source is required.', null, 400);
    }

    if (isSalesExecutive($currentUser)) {
        // Sales Executive: Cannot assign leads to others (PDF Page 8: Assign = No)
        if (!empty($assignedTo) && $assignedTo !== $currentUserId) {
            jsonResponse(false, 'Permission denied. Sales Executives cannot assign leads to other representatives.', null, 403);
        }
        $assignedTo = $currentUserId;
    } else {
        if (empty($assignedTo)) {
            jsonResponse(false, 'Assigned user is required.', null, 400);
        }
        $userCheck = $db->select('user_roles', ['id' => 'eq.' . $assignedTo, 'is_active' => 'eq.true', 'select' => 'id,user_name,role']);
        if (!$db->isSuccess($userCheck) || empty($userCheck['data'])) {
            jsonResponse(false, 'Assigned user does not exist or is inactive.', null, 404);
        }
    }

    $submissionId = sanitize($input['submission_id'] ?? '');
    $designLink = sanitize($input['design_link'] ?? '');
    if ($designSource === 'Kit Builder' && empty($submissionId)) {
        jsonResponse(false, 'Submission ID is required when Kit Builder is selected.', null, 400);
    }
    if ($designSource === 'Design Link') {
        if (empty($designLink) || !filter_var($designLink, FILTER_VALIDATE_URL)) {
            jsonResponse(false, 'A valid Design Link is required when Design Link is selected.', null, 400);
        }
    }

    $customDesignMeta = [];
    if ($designSource === 'Custom Design') {
        $customDesignPayload = $_FILES['custom_design_file'] ?? [];
        if (empty($customDesignPayload['name'])) {
            jsonResponse(false, 'Custom design file is required when Custom Design is selected.', null, 400);
        }
        $meta = [];
        if (is_array($customDesignPayload['name'])) {
            foreach ($customDesignPayload['name'] as $i => $name) {
                if (empty($name)) continue;
                $file = [
                    'name' => $customDesignPayload['name'][$i],
                    'type' => $customDesignPayload['type'][$i],
                    'tmp_name' => $customDesignPayload['tmp_name'][$i],
                    'error' => $customDesignPayload['error'][$i],
                    'size' => $customDesignPayload['size'][$i],
                ];
                $saved = savedFileMeta($file);
                if (isset($saved['error'])) {
                    jsonResponse(false, $saved['error'], null, 400);
                }
                $meta[] = $saved;
            }
        } else {
            $saved = savedFileMeta($customDesignPayload);
            if (isset($saved['error'])) {
                jsonResponse(false, $saved['error'], null, 400);
            }
            $meta[] = $saved;
        }
        $customDesignMeta = $meta;
    }

    $playerRoster = sanitize($input['player_roster_received'] ?? '');
    $playerListMeta = [];
    if ($playerRoster === 'Received') {
        $playerFile = $_FILES['player_list_file'] ?? [];
        if (empty($playerFile['name'])) {
            jsonResponse(false, 'Player list file is required when roster is received.', null, 400);
        }
        if (is_array($playerFile['name'])) {
            foreach ($playerFile['name'] as $i => $name) {
                if (empty($name)) continue;
                $file = [
                    'name' => $playerFile['name'][$i],
                    'type' => $playerFile['type'][$i],
                    'tmp_name' => $playerFile['tmp_name'][$i],
                    'error' => $playerFile['error'][$i],
                    'size' => $playerFile['size'][$i],
                ];
                $saved = savedFileMeta($file);
                if (isset($saved['error'])) jsonResponse(false, $saved['error'], null, 400);
                $playerListMeta[] = $saved;
            }
        } else {
            $saved = savedFileMeta($playerFile);
            if (isset($saved['error'])) jsonResponse(false, $saved['error'], null, 400);
            $playerListMeta[] = $saved;
        }
    }

    $payload = [
        'customer_id' => $customerId,
        'priority' => $priority,
        'lead_source' => $leadSource,
        'enquiry_date' => $enquiryDate,
        'products_interested' => json_encode($products),
        'sport' => $sports,
        'design_source' => $designSource,
        'submission_id' => $submissionId ?: null,
        'design_link' => $designLink ?: null,
        'custom_design_file' => json_encode($customDesignMeta),
        'player_roster_received' => $playerRoster,
        'player_list_file' => json_encode($playerListMeta),
        'notes' => $notes,
        'send_whatsapp_notification' => $sendWhatsApp ? true : false,
        'whatsapp_status' => $sendWhatsApp ? 'requested' : 'not_requested',
        'assigned_to' => $assignedTo,
        'lead_status' => $leadStatus,
        'is_deleted' => false,
    ];

    $insert = $db->insert('leads', $payload);
    if (!$db->isSuccess($insert)) {
        jsonResponse(false, $insert['message'] ?? 'Failed to create lead', null, 500);
    }

    $leadId = $insert['data'][0]['id'] ?? null;
    if ($leadId) {
        addLeadActivity($leadId, 'Lead Created', 'Lead created by ' . ($currentUser['name'] ?? 'system'), $currentUserId, null, $leadStatus);
        if ($sendWhatsApp) {
            triggerWhatsAppWorkflowAutomation($leadId, 'Received', null, $currentUser);
        }
    }
    jsonResponse(true, 'Lead created successfully', $insert['data'][0] ?? null, 201);
}

if ($method === 'POST' && in_array($action, ['bulk_update', 'bulk-update', 'bulk'], true)) {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (!is_array($input) || empty($input)) {
        $input = $_POST;
    }

    $ids = $input['ids'] ?? [];
    if (is_string($ids)) {
        $decoded = json_decode($ids, true);
        $ids = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $ids)));
    }
    if (!is_array($ids) || empty($ids)) {
        jsonResponse(false, 'No leads selected for bulk update.', null, 400);
    }

    $cleanIds = [];
    foreach ($ids as $id) {
        $cleanId = normalizeUuidValue((string)$id);
        if ($cleanId) {
            $cleanIds[] = $cleanId;
        }
    }
    $cleanIds = array_values(array_unique($cleanIds));
    if (empty($cleanIds)) {
        jsonResponse(false, 'No valid lead IDs provided.', null, 400);
    }

    $operation = sanitize($input['operation'] ?? '');
    if (!in_array($operation, ['status', 'assign', 'delete', 'status_update', 'assign_user', 'bulk_delete'], true)) {
        jsonResponse(false, 'Invalid bulk operation. Allowed: status, assign, delete.', null, 422);
    }
    if ($operation === 'status_update') $operation = 'status';
    if ($operation === 'assign_user') $operation = 'assign';
    if ($operation === 'bulk_delete') $operation = 'delete';

    $userRole = strtolower($currentUser['role'] ?? 'staff');

    // Fetch existing records for validation and permission check
    $existingLeads = [];
    foreach ($cleanIds as $id) {
        $res = $db->select('leads', ['id' => 'eq.' . $id, 'is_deleted' => 'eq.false', 'select' => 'id,lead_status,assigned_to']);
        if ($res['status'] === 200 && !empty($res['data'])) {
            $existingLeads[$id] = $res['data'][0];
        }
    }

    if (empty($existingLeads)) {
        jsonResponse(false, 'None of the selected leads were found or they are already deleted.', null, 404);
    }

    $timestamp = date('Y-m-d H:i:s');
    $updatedCount = 0;

    if ($operation === 'delete') {
        if (!canDeleteLead($currentUser)) {
            jsonResponse(false, 'Permission denied. Only administrators can delete leads.', null, 403);
        }

        foreach ($existingLeads as $leadId => $lead) {
            $res = $db->update('leads', ['is_deleted' => true, 'updated_at' => $timestamp], ['id' => 'eq.' . $leadId]);
            if ($db->isSuccess($res)) {
                $updatedCount++;
                addLeadActivity($leadId, 'Lead Deleted', 'Bulk soft-deleted by ' . ($currentUser['name'] ?? 'Admin'), $currentUserId, $lead['lead_status'], null);
            }
        }

        jsonResponse(true, "Successfully deleted {$updatedCount} leads.", [
            'updated_count' => $updatedCount,
            'operation' => 'delete'
        ]);
    }

    if ($operation === 'assign') {
        if (!canAssignLead($currentUser)) {
            jsonResponse(false, 'Permission denied. Only administrators and sales managers can bulk assign leads.', null, 403);
        }

        $assignedTo = normalizeUuidValue($input['assigned_to'] ?? '');
        if (empty($assignedTo)) {
            jsonResponse(false, 'A valid sales user must be selected for bulk assignment.', null, 422);
        }

        $userCheck = $db->select('user_roles', ['id' => 'eq.' . $assignedTo, 'is_active' => 'eq.true', 'select' => 'id,user_name,role']);
        if (!$db->isSuccess($userCheck) || empty($userCheck['data'])) {
            jsonResponse(false, 'Assigned user does not exist or is inactive.', null, 404);
        }
        $targetUser = $userCheck['data'][0];

        foreach ($existingLeads as $leadId => $lead) {
            $res = $db->update('leads', ['assigned_to' => $assignedTo, 'updated_at' => $timestamp], ['id' => 'eq.' . $leadId]);
            if ($db->isSuccess($res)) {
                $updatedCount++;
                addLeadActivity($leadId, 'Lead Reassigned', 'Bulk reassigned to ' . $targetUser['user_name'] . ' by ' . ($currentUser['name'] ?? 'Manager'), $currentUserId, $lead['lead_status'], $lead['lead_status']);
            }
        }

        jsonResponse(true, "Successfully assigned {$updatedCount} leads to {$targetUser['user_name']}.", [
            'updated_count' => $updatedCount,
            'operation' => 'assign',
            'assigned_to' => $assignedTo
        ]);
    }

    if ($operation === 'status') {
        $allowedStatuses = [
            'Received', 'Analysis', 'Awaiting Clarification', 'Order Sheet Prepared',
            'Verified', 'Awaiting Approval', 'Approved', 'Awaiting Payment',
            'Moved to Production', 'Lost'
        ];

        $newStatus = sanitize($input['lead_status'] ?? '');
        if (!in_array($newStatus, $allowedStatuses, true)) {
            jsonResponse(false, 'Invalid lead status value.', null, 422);
        }

        $unauthorizedCount = 0;
        foreach ($existingLeads as $leadId => $lead) {
            if (!canChangeLeadStatus($lead, $currentUser)) {
                $unauthorizedCount++;
            }
        }

        if ($unauthorizedCount > 0 && !canAssignLead($currentUser)) {
            jsonResponse(false, 'Permission denied. You can only update status on leads assigned to you.', null, 403);
        }

        foreach ($existingLeads as $leadId => $lead) {
            if (!canChangeLeadStatus($lead, $currentUser)) {
                continue;
            }
            $res = $db->update('leads', ['lead_status' => $newStatus, 'updated_at' => $timestamp], ['id' => 'eq.' . $leadId]);
            if ($db->isSuccess($res)) {
                $updatedCount++;
                addLeadActivity($leadId, 'Lead Status Changed', 'Bulk status update to ' . $newStatus . ' by ' . ($currentUser['name'] ?? 'User'), $currentUserId, $lead['lead_status'], $newStatus);
                triggerWhatsAppWorkflowAutomation($leadId, $newStatus, $lead['lead_status'], $currentUser);
            }
        }

        jsonResponse(true, "Successfully updated status of {$updatedCount} leads to {$newStatus}.", [
            'updated_count' => $updatedCount,
            'operation' => 'status',
            'lead_status' => $newStatus
        ]);
    }
}

if ($method === 'PUT' || $method === 'PATCH') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $leadId = normalizeUuidValue($input['id'] ?? $_GET['id'] ?? '');
    if (empty($leadId)) jsonResponse(false, 'Lead ID required', null, 400);

    $existing = $db->select('leads', ['id' => 'eq.' . $leadId, 'select' => '*']);
    if ($existing['status'] !== 200 || empty($existing['data'])) {
        jsonResponse(false, 'Lead not found', null, 404);
    }
    $lead = $existing['data'][0];

    // Record-level permission check: Sales Executive can only edit leads assigned to them
    if (!canEditLeadRecord($lead, $currentUser)) {
        jsonResponse(false, 'Permission denied. Sales Executives can only edit leads assigned to them.', null, 403);
    }

    $update = [];
    foreach (['lead_source', 'enquiry_date', 'sport', 'design_source', 'submission_id', 'design_link', 'notes', 'send_whatsapp_notification', 'player_roster_received'] as $field) {
        if (array_key_exists($field, $input)) {
            $update[$field] = $field === 'send_whatsapp_notification' ? (bool)($input[$field] ?? false) : sanitize((string)($input[$field] ?? ''));
        }
    }

    // Role-check on lead assignment
    if (array_key_exists('assigned_to', $input)) {
        $newAssignedTo = normalizeUuidValue($input['assigned_to'] ?? '');
        if ($newAssignedTo !== ($lead['assigned_to'] ?? '')) {
            if (!canAssignLead($currentUser)) {
                jsonResponse(false, 'Permission denied. Sales Executives cannot assign or reassign leads.', null, 403);
            }
            if (!empty($newAssignedTo)) {
                $userCheck = $db->select('user_roles', ['id' => 'eq.' . $newAssignedTo, 'is_active' => 'eq.true', 'select' => 'id,user_name,role']);
                if (!$db->isSuccess($userCheck) || empty($userCheck['data'])) {
                    jsonResponse(false, 'Assigned user does not exist or is inactive.', null, 404);
                }
            }
            $update['assigned_to'] = $newAssignedTo;
        }
    }

    // Role-check on lead status transition
    if (array_key_exists('lead_status', $input)) {
        $newStatus = sanitize((string)($input['lead_status'] ?? ''));
        if ($newStatus !== $lead['lead_status']) {
            if (!canChangeLeadStatus($lead, $currentUser)) {
                jsonResponse(false, 'Permission denied. Sales Executives can only update status on leads assigned to them.', null, 403);
            }
            $allowedStatuses = [
                'Received', 'Analysis', 'Awaiting Clarification', 'Order Sheet Prepared',
                'Verified', 'Awaiting Approval', 'Approved', 'Awaiting Payment',
                'Moved to Production', 'Lost'
            ];
            if (!in_array($newStatus, $allowedStatuses, true)) {
                jsonResponse(false, 'Invalid lead status value.', null, 422);
            }
            $update['lead_status'] = $newStatus;
        }
    }

    if (array_key_exists('customer_id', $input)) {
        $newCustomerId = normalizeUuidValue($input['customer_id'] ?? '');
        if (empty($newCustomerId)) {
            jsonResponse(false, 'Customer selection is required.', null, 422);
        }
        $custCheck = $db->select('customers', ['id' => 'eq.' . $newCustomerId, 'is_deleted' => 'eq.false', 'select' => 'id,name,team_academy_name']);
        if (!$db->isSuccess($custCheck) || empty($custCheck['data'])) {
            jsonResponse(false, 'Selected customer does not exist or has been deleted.', null, 404);
        }
        $update['customer_id'] = $newCustomerId;
    }

    if (array_key_exists('priority', $input)) {
        $rawPriority = sanitize($input['priority'] ?? '');
        $newPriority = strtolower(trim($rawPriority));
        if (!in_array($newPriority, ['low', 'medium', 'high'], true)) {
            jsonResponse(false, 'Invalid priority level. Allowed values: low, medium, high.', null, 422);
        }
        $update['priority'] = $newPriority;
    }

    if (array_key_exists('products_interested', $input)) {
        $products = $input['products_interested'];
        if (!is_array($products)) {
            $products = json_decode((string)$products, true) ?: [];
        }
        $update['products_interested'] = json_encode($products);
    }

    if (array_key_exists('custom_design_file', $input) && !empty($input['custom_design_file'])) {
        $update['custom_design_file'] = json_encode($input['custom_design_file']);
    }
    if (array_key_exists('player_list_file', $input) && !empty($input['player_list_file'])) {
        $update['player_list_file'] = json_encode($input['player_list_file']);
    }

    if (!empty($update)) {
        $timestamp = date('Y-m-d H:i:s');
        $update['updated_at'] = $timestamp;
        $result = $db->update('leads', $update, ['id' => 'eq.' . $leadId]);
        if (!$db->isSuccess($result)) {
            jsonResponse(false, $result['message'] ?? 'Failed to update lead', null, 500);
        }

        if (!empty($input['lead_status']) && $input['lead_status'] !== $lead['lead_status']) {
            addLeadActivity($leadId, 'Lead Status Changed', 'Status changed from ' . $lead['lead_status'] . ' to ' . $input['lead_status'], $currentUserId, $lead['lead_status'], $input['lead_status']);
            triggerWhatsAppWorkflowAutomation($leadId, $input['lead_status'], $lead['lead_status'], $currentUser);
        }
        if (!empty($update['priority']) && $update['priority'] !== strtolower($lead['priority'] ?? 'medium')) {
            addLeadActivity($leadId, 'Priority Changed', 'Priority changed from ' . ($lead['priority'] ?? 'medium') . ' to ' . $update['priority'], $currentUserId, $lead['lead_status'], $lead['lead_status']);
        }
        if (!empty($update['customer_id']) && $update['customer_id'] !== ($lead['customer_id'] ?? '')) {
            addLeadActivity($leadId, 'Customer Reassigned', 'Lead customer changed', $currentUserId, $lead['lead_status'], $lead['lead_status']);
        }
        addLeadActivity($leadId, 'Lead Updated', 'Lead updated by ' . ($currentUser['name'] ?? 'system'), $currentUserId, $lead['lead_status'], $input['lead_status'] ?? $lead['lead_status']);
    }

    jsonResponse(true, 'Lead updated successfully');
}

if ($method === 'GET' && $action === 'export') {
    if (!hasPermission('export')) {
        jsonResponse(false, 'Permission denied. Sales Executives cannot export lead datasets.', null, 403);
    }
    $rows = [];
    $params = ['is_deleted' => 'eq.false', 'select' => '*', 'order' => 'enquiry_date.desc'];
    $assignedTo = normalizeUuidValue($_GET['assigned_to'] ?? '');
    $sport = sanitize($_GET['sport'] ?? '');
    $leadSource = sanitize($_GET['lead_source'] ?? '');
    $dateFrom = sanitize($_GET['date_from'] ?? '');
    $dateTo = sanitize($_GET['date_to'] ?? '');

    $userRole = normalizeRole($currentUser['role'] ?? 'staff');
    if ($userRole === 'sales_executive') {
        $params['assigned_to'] = 'eq.' . $currentUserId;
    } elseif (!empty($assignedTo)) {
        $params['assigned_to'] = 'eq.' . $assignedTo;
    }

    if (!empty($sport)) $params['sport'] = 'eq.' . $sport;
    if (!empty($leadSource)) $params['lead_source'] = 'eq.' . $leadSource;

    if (!empty($dateFrom) && !empty($dateTo)) {
        $params['and'] = '(enquiry_date.gte.' . $dateFrom . ',enquiry_date.lte.' . $dateTo . ')';
    } elseif (!empty($dateFrom)) {
        $params['enquiry_date'] = 'gte.' . $dateFrom;
    } elseif (!empty($dateTo)) {
        $params['enquiry_date'] = 'lte.' . $dateTo;
    }

    $data = $db->select('leads', $params);
    if ($data['status'] === 200) {
        $rows = $data['data'] ?? [];
        if (!empty($dateFrom)) {
            $rows = array_values(array_filter($rows, fn($r) => !empty($r['enquiry_date']) && $r['enquiry_date'] >= $dateFrom));
        }
        if (!empty($dateTo)) {
            $rows = array_values(array_filter($rows, fn($r) => !empty($r['enquiry_date']) && $r['enquiry_date'] <= $dateTo));
        }
    }

    $csv = fopen('php://temp', 'w+');
    fputcsv($csv, ['id','customer_id','lead_source','enquiry_date','sport','priority','lead_status','assigned_to','created_at']);
    foreach ($rows as $lead) {
        fputcsv($csv, [$lead['id'] ?? '', $lead['customer_id'] ?? '', $lead['lead_source'] ?? '', $lead['enquiry_date'] ?? '', $lead['sport'] ?? '', $lead['priority'] ?? 'medium', $lead['lead_status'] ?? '', $lead['assigned_to'] ?? '', $lead['created_at'] ?? '']);
    }
    rewind($csv);
    $content = stream_get_contents($csv);
    fclose($csv);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="ira_sportswear_leads_' . date('Y-m-d') . '.csv"');
    echo $content;
    exit;
}

if ($method === 'DELETE') {
    $leadId = normalizeUuidValue($_GET['id'] ?? '');
    if (empty($leadId)) jsonResponse(false, 'Lead ID required', null, 400);

    $existing = $db->select('leads', ['id' => 'eq.' . $leadId, 'select' => '*']);
    if ($existing['status'] !== 200 || empty($existing['data'])) {
        jsonResponse(false, 'Lead not found', null, 404);
    }

    $lead = $existing['data'][0];
    if (!canDeleteLead($currentUser)) {
        jsonResponse(false, 'Permission denied. Only administrators can delete leads.', null, 403);
    }

    $result = $db->update('leads', ['is_deleted' => true, 'updated_at' => date('Y-m-d H:i:s')], ['id' => 'eq.' . $leadId]);
    if (!$db->isSuccess($result)) {
        jsonResponse(false, $result['message'] ?? 'Failed to delete lead', null, 500);
    }

    addLeadActivity($leadId, 'Lead Deleted', 'Lead deleted by ' . ($currentUser['name'] ?? 'system'), $currentUserId, $lead['lead_status'], null);
    jsonResponse(true, 'Lead deleted successfully');
}

jsonResponse(false, 'Invalid request', null, 405);
