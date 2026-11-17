<?php
/**
 * WhatsApp Cloud API Integration Service
 * Ira Sportswear Lead Management Module (Official PDF Page 5, 6, 12, 13)
 * Handles Meta Cloud API message templates, phone number formatting,
 * status tracking, delivery event parsing, and audit logging.
 */

require_once __DIR__ . '/config.php';

class WhatsAppService {
    private array $config;
    private $db;

    public function __construct(?array $customConfig = null) {
        $this->config = $customConfig ?? getWhatsAppConfig();
        $this->db = new Supabase(true);
    }

    /**
     * Checks if Meta Cloud API credentials are configured.
     */
    public function isConfigured(): bool {
        return !empty($this->config['token']) &&
               !empty($this->config['phone_number_id']) &&
               !str_contains($this->config['token'], 'your_permanent_or_system_user_token_here');
    }

    /**
     * Validates and normalizes phone number to E.164 digits format (e.g. 919876543210).
     */
    public function validatePhoneNumber(?string $phone, string $countryCode = '+91'): ?string {
        if (empty($phone)) return null;

        // Strip whitespace, hyphens, parentheses, and dots
        $cleaned = preg_replace('/[\s\-\(\)\.]/', '', trim($phone));

        // If phone starts with +, remove +
        if (str_starts_with($cleaned, '+')) {
            $cleaned = substr($cleaned, 1);
        }

        // Clean default country code
        $cleanCountryCode = ltrim(preg_replace('/[\s\-\(\)\+]/', '', $countryCode), '0');

        // If 10 digits (e.g. Indian mobile starting with 6, 7, 8, or 9), prepend country code
        if (strlen($cleaned) === 10 && preg_match('/^[6-9]\d{9}$/', $cleaned)) {
            $cleaned = $cleanCountryCode . $cleaned;
        }

        // Must be between 10 and 15 digits per ITU E.164 standard
        if (!preg_match('/^\d{10,15}$/', $cleaned)) {
            return null;
        }

        return $cleaned;
    }

    /**
     * Official Ira Sportswear approved templates from specification document.
     */
    public function getApprovedTemplates(): array {
        return [
            'ira_lead_acknowledgement' => [
                'name'            => 'ira_lead_acknowledgement',
                'label'           => 'Enquiry Acknowledgement (Official PDF Page 6)',
                'trigger_stage'   => 'Received',
                'description'     => 'Sent automatically when a customer enquiry is received.',
                'parameters'      => ['Customer Name', 'Enquiry Ref', 'Assigned Sales Rep'],
                'format'          => "Hello {{1}}, Thank you for contacting Ira Sportswear. We have received your enquiry (Ref: {{2}}) and our team is currently reviewing your requirements. Your assigned sales representative {{3}} will contact you shortly. Thank you. Team Ira Sportswear",
            ],
            'ira_clarification_request' => [
                'name'            => 'ira_clarification_request',
                'label'           => 'Clarification Request (Awaiting Clarification)',
                'trigger_stage'   => 'Awaiting Clarification',
                'description'     => 'Sent when details regarding custom designs or player rosters are required.',
                'parameters'      => ['Customer Name', 'Enquiry Ref', 'Details Required'],
                'format'          => "Hello {{1}}, regarding your Ira Sportswear order (Ref: {{2}}), our design team needs additional clarification: {{3}}. Please reply with the details so we can proceed.",
            ],
            'ira_order_sheet_update' => [
                'name'            => 'ira_order_sheet_update',
                'label'           => 'Order Sheet Prepared',
                'trigger_stage'   => 'Order Sheet Prepared',
                'description'     => 'Sent when order sheet and mockups have been generated.',
                'parameters'      => ['Customer Name', 'Sport', 'Stage Status'],
                'format'          => "Hello {{1}}, your {{2}} apparel order sheet has been {{3}}. Our team will follow up for final approval. Team Ira Sportswear",
            ],
            'ira_order_approved' => [
                'name'            => 'ira_order_approved',
                'label'           => 'Order Approved',
                'trigger_stage'   => 'Approved',
                'description'     => 'Sent when customer or management approves order specifications.',
                'parameters'      => ['Customer Name', 'Enquiry Ref'],
                'format'          => "Hello {{1}}, your custom sportswear order (Ref: {{2}}) has been approved! We are scheduling production.",
            ],
            'ira_moved_to_production' => [
                'name'            => 'ira_moved_to_production',
                'label'           => 'Handover to Production (Moved to Production)',
                'trigger_stage'   => 'Moved to Production',
                'description'     => 'Sent when order moves to printing, stitching, and manufacturing.',
                'parameters'      => ['Customer Name', 'Sport', 'Team / Academy Name'],
                'format'          => "Hello {{1}}, great news! The custom {{2}} uniforms for {{3}} have been moved to production. Thank you for choosing Ira Sportswear.",
            ],
        ];
    }

    /**
     * Builds Meta WhatsApp Cloud API template payload.
     */
    public function buildTemplatePayload(string $recipientPhone, string $templateName, array $parameters = [], string $language = 'en'): array {
        $components = [];

        if (!empty($parameters)) {
            $paramComponents = [];
            foreach ($parameters as $val) {
                $paramComponents[] = [
                    'type' => 'text',
                    'text' => (string)$val
                ];
            }
            $components[] = [
                'type' => 'body',
                'parameters' => $paramComponents
            ];
        }

        return [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $recipientPhone,
            'type'              => 'template',
            'template'          => [
                'name'     => $templateName,
                'language' => [
                    'code' => $language
                ],
                'components' => $components
            ]
        ];
    }

    /**
     * Sends template message via Meta Cloud API or injected transport.
     */
    public function sendTemplate(
        string $phone,
        string $templateName,
        array $parameters = [],
        string $language = 'en',
        string $countryCode = '+91',
        ?callable $transport = null
    ): array {
        // 1. Validate phone number
        $validPhone = $this->validatePhoneNumber($phone, $countryCode);
        if (!$validPhone) {
            return [
                'success' => false,
                'status'  => 'failed',
                'error'   => 'Invalid recipient phone number. Must be valid 10-15 digit mobile number.',
                'code'    => 'INVALID_PHONE'
            ];
        }

        // 2. Validate template name
        $approvedTemplates = $this->getApprovedTemplates();
        if (!isset($approvedTemplates[$templateName])) {
            return [
                'success' => false,
                'status'  => 'failed',
                'error'   => "Template '{$templateName}' is not an approved Ira Sportswear template.",
                'code'    => 'INVALID_TEMPLATE'
            ];
        }

        // 3. Build payload
        $payload = $this->buildTemplatePayload($validPhone, $templateName, $parameters, $language);

        // 4. Use custom/mock transport if supplied (e.g. for unit testing)
        if ($transport !== null) {
            return $transport($payload, $validPhone, $templateName);
        }

        // 5. Check API credentials
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status'  => 'failed',
                'error'   => 'Meta WhatsApp Cloud API credentials are not configured. Please set WHATSAPP_TOKEN and WHATSAPP_PHONE_NUMBER_ID.',
                'code'    => 'MISSING_CREDENTIALS'
            ];
        }

        // 6. Execute Meta Cloud API HTTP Request
        $version = $this->config['api_version'] ?? 'v21.0';
        $phoneId = $this->config['phone_number_id'];
        $url = "https://graph.facebook.com/{$version}/{$phoneId}/messages";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->config['token'],
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'success' => false,
                'status'  => 'failed',
                'error'   => 'Connection to WhatsApp API failed: ' . $curlError,
                'code'    => 'NETWORK_TIMEOUT'
            ];
        }

        $decoded = json_decode($rawResponse, true) ?: [];

        // Meta returns 200 with messages[0].id on success
        if ($httpCode >= 200 && $httpCode < 300 && !empty($decoded['messages'][0]['id'])) {
            return [
                'success'             => true,
                'status'              => 'sent',
                'provider_message_id' => $decoded['messages'][0]['id'],
                'recipient_id'        => $decoded['contacts'][0]['wa_id'] ?? $validPhone,
                'payload'             => $payload
            ];
        }

        // Meta error handling
        $metaError = $decoded['error']['message'] ?? ('Meta API HTTP ' . $httpCode);
        return [
            'success' => false,
            'status'  => 'failed',
            'error'   => 'WhatsApp Cloud API Error: ' . $metaError,
            'code'    => $decoded['error']['code'] ?? $httpCode
        ];
    }

    /**
     * Records a WhatsApp message send attempt in database and updates lead.
     */
    public function logMessageAttempt(
        string $leadId,
        ?string $customerId,
        string $recipientPhone,
        string $templateName,
        array $parameters,
        array $apiResult,
        ?string $sentByUserId = null,
        string $triggerType = 'manual'
    ): array {
        $status = $apiResult['status'] ?? 'failed';
        $providerId = $apiResult['provider_message_id'] ?? null;
        $errorMessage = $apiResult['error'] ?? null;

        $insertPayload = [
            'lead_id'             => $leadId,
            'customer_id'         => $customerId ?: null,
            'recipient_phone'     => $recipientPhone,
            'template_name'       => $templateName,
            'template_language'   => 'en',
            'parameters'          => json_encode($parameters),
            'provider_message_id' => $providerId,
            'status'              => $status,
            'error_message'       => $errorMessage,
            'trigger_type'        => $triggerType,
            'sent_by'             => $sentByUserId ?: null,
            'created_at'          => date('c'),
            'updated_at'          => date('c'),
        ];

        // 1. Insert into lead_whatsapp_messages
        $logResult = $this->db->insert('lead_whatsapp_messages', $insertPayload);

        // 2. Update leads table with latest status
        $leadUpdate = [
            'whatsapp_status' => $status
        ];
        if ($providerId) {
            $leadUpdate['whatsapp_last_message_id'] = $providerId;
            $leadUpdate['whatsapp_last_sent_at'] = date('c');
            $leadUpdate['send_whatsapp_notification'] = true;
        }

        $this->db->update('leads', $leadUpdate, ['id' => 'eq.' . $leadId]);

        // 3. Record in lead activity log
        $actionDesc = ($triggerType === 'automated') ? 'Automated WhatsApp Sent' : 'WhatsApp Notification Sent';
        if ($status === 'failed') {
            $actionDesc = 'WhatsApp Notification Failed';
        }
        $details = "Template '{$templateName}' to {$recipientPhone} - Status: {$status}" . ($errorMessage ? " ({$errorMessage})" : "");
        addLeadActivity($leadId, $actionDesc, $details, $sentByUserId);

        return [
            'logged'              => $this->db->isSuccess($logResult),
            'status'              => $status,
            'provider_message_id' => $providerId
        ];
    }

    /**
     * Updates message delivery status from webhook event.
     */
    public function updateMessageStatus(string $providerMessageId, string $newStatus, ?string $errorMessage = null): array {
        $allowedStatuses = ['requested', 'sent', 'delivered', 'read', 'failed'];
        if (!in_array($newStatus, $allowedStatuses, true)) {
            return ['updated' => false, 'error' => 'Invalid status'];
        }

        // Status precedence hierarchy to prevent race condition regressions (e.g. delivered after read)
        $rank = [
            'requested' => 1,
            'sent'      => 2,
            'delivered' => 3,
            'read'      => 4,
            'failed'    => 5
        ];

        // Find existing record
        $find = $this->db->select('lead_whatsapp_messages', ['provider_message_id' => 'eq.' . $providerMessageId]);
        if (!$this->db->isSuccess($find) || empty($find['data'])) {
            return ['updated' => false, 'error' => 'Unknown provider message ID'];
        }

        $msgRecord = $find['data'][0];
        $currentStatus = $msgRecord['status'] ?? 'requested';

        // Do not downgrade a read status to delivered if webhook arrives out of order
        if (($rank[$currentStatus] ?? 0) >= ($rank[$newStatus] ?? 0) && $newStatus !== 'failed') {
            return ['updated' => true, 'idempotent' => true, 'current' => $currentStatus];
        }

        $updateData = [
            'status'     => $newStatus,
            'updated_at' => date('c')
        ];
        if ($errorMessage) {
            $updateData['error_message'] = $errorMessage;
        }

        $this->db->update('lead_whatsapp_messages', $updateData, ['id' => 'eq.' . $msgRecord['id']]);

        // Synchronize status back to lead
        $leadId = $msgRecord['lead_id'];
        if ($leadId) {
            $this->db->update('leads', ['whatsapp_status' => $newStatus], ['id' => 'eq.' . $leadId]);
            addLeadActivity($leadId, 'WhatsApp Delivery Update', "Message {$providerMessageId} status updated to: {$newStatus}");
        }

        return ['updated' => true, 'status' => $newStatus, 'lead_id' => $leadId];
    }

    /**
     * Fetches message history for a lead.
     */
    public function getMessageHistory(string $leadId): array {
        $result = $this->db->select('lead_whatsapp_messages', [
            'lead_id' => 'eq.' . $leadId,
            'order'   => 'created_at.desc'
        ]);

        if (!$this->db->isSuccess($result) || empty($result['data'])) {
            return [];
        }

        return $result['data'];
    }
}
