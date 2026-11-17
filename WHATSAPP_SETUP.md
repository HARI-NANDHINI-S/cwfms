# Ira Sportswear – Meta WhatsApp Cloud API Setup & Integration Guide

This guide details the step-by-step procedure for configuring the official **Meta WhatsApp Cloud API** (Graph API v21.0) with the CWFMS Lead Management Module.

---

## 1. Integration Status & Verification Levels

To ensure transparency, our implementation distinguishes between verification tiers:

| Status Tier | Description | Current State |
| :--- | :--- | :---: |
| **Tier 1: Code Implementation** | Service, webhooks, database migration, automation engine, frontend modal actions | **COMPLETED** |
| **Tier 2: Automated Mock Tests** | 29/29 mock API tests, signature tests, idempotency, role security, regression | **PASSED (100%)** |
| **Tier 3: Meta Credentials Configured** | Valid Meta App credentials provided in `.env` | *Pending User Setup* |
| **Tier 4: Live API Handshake** | Token exchange & phone number validation confirmed with Meta API | *Pending Tier 3* |
| **Tier 5: Real Message Delivery** | Delivery confirmed via customer device and incoming webhook event | *Pending Tier 3* |

---

## 2. Step-by-Step Meta Cloud API Configuration

### Step 1: Create a Meta for Developers Application
1. Go to [developers.facebook.com](https://developers.facebook.com) and log in with your Facebook business account.
2. Click **My Apps** → **Create App**.
3. Select **Other** as the use case, then choose **Business** as the app type.
4. Name the application (e.g., `Ira Sportswear Lead Management`) and link your Meta Business Account.

### Step 2: Add WhatsApp to the App
1. In the App Dashboard, locate **WhatsApp** under "Add products to your app" and click **Set up**.
2. Meta will automatically assign a sandbox test phone number and a temporary 24-hour access token for testing.

### Step 3: Obtain Credentials
Under **WhatsApp** → **API Setup**:
- **Temporary Access Token**: Copy for initial testing. For production, generate a permanent System User Token under Meta Business Manager → Users → System Users.
- **Phone Number ID**: A 15-digit number (e.g., `105948372615204`).
- **WhatsApp Business Account ID (WABA ID)**: (e.g., `102938475610293`).

Under **App Settings** → **Basic**:
- **App Secret**: Click "Show" and copy your App Secret for webhook HMAC-SHA256 signature verification.

### Step 4: Configure Message Templates in WhatsApp Manager
Business-initiated WhatsApp messages require pre-approved templates per Meta policy.
Navigate to **WhatsApp Manager** → **Account Tools** → **Message Templates** and submit the 5 approved Ira Sportswear templates:

#### Template 1: `ira_lead_acknowledgement` (Category: Marketing/Utility)
- **Language**: English (`en`)
- **Body**:
  ```text
  Hello {{1}}, Thank you for contacting Ira Sportswear. We have received your enquiry (Ref: {{2}}) and our team is currently reviewing your requirements. Your assigned sales representative {{3}} will contact you shortly. Thank you. Team Ira Sportswear
  ```
- **Sample Values**: `{{1}}` = Rajesh Kumar, `{{2}}` = LEAD-8812, `{{3}}` = Arun Kumar

#### Template 2: `ira_clarification_request` (Category: Utility)
- **Language**: English (`en`)
- **Body**:
  ```text
  Hello {{1}}, regarding your Ira Sportswear order (Ref: {{2}}), our design team needs additional clarification: {{3}}. Please reply with the details so we can proceed.
  ```
- **Sample Values**: `{{1}}` = Rajesh Kumar, `{{2}}` = LEAD-8812, `{{3}}` = Player shirt numbers and collar type

#### Template 3: `ira_order_sheet_update` (Category: Utility)
- **Language**: English (`en`)
- **Body**:
  ```text
  Hello {{1}}, your {{2}} apparel order sheet has been {{3}}. Our team will follow up for final approval. Team Ira Sportswear
  ```
- **Sample Values**: `{{1}}` = Rajesh Kumar, `{{2}}` = Cricket, `{{3}}` = prepared and verified

#### Template 4: `ira_order_approved` (Category: Utility)
- **Language**: English (`en`)
- **Body**:
  ```text
  Hello {{1}}, your custom sportswear order (Ref: {{2}}) has been approved! We are scheduling production.
  ```
- **Sample Values**: `{{1}}` = Rajesh Kumar, `{{2}}` = LEAD-8812

#### Template 5: `ira_moved_to_production` (Category: Utility)
- **Language**: English (`en`)
- **Body**:
  ```text
  Hello {{1}}, great news! The custom {{2}} uniforms for {{3}} have been moved to production. Thank you for choosing Ira Sportswear.
  ```
- **Sample Values**: `{{1}}` = Rajesh Kumar, `{{2}}` = Soccer, `{{3}}` = Chennai FC Academy

---

### Step 5: Environment Variables Configuration
Copy `cwfms/.env.example` to `cwfms/.env` and update the values:

```ini
WHATSAPP_TOKEN=EAAG...your_permanent_system_user_token
WHATSAPP_PHONE_NUMBER_ID=105948372615204
WHATSAPP_BUSINESS_ACCOUNT_ID=102938475610293
WHATSAPP_WEBHOOK_VERIFY_TOKEN=ira_sportswear_wh_verify_secret_token_2026
WHATSAPP_APP_SECRET=your_app_secret_here
WHATSAPP_API_VERSION=v21.0
WHATSAPP_AUTOMATIONS_ENABLED=1
WHATSAPP_AUTOMATION_STAGES=Received,Awaiting Clarification,Approved,Moved to Production
```

---

### Step 6: Webhook URL & Event Subscription
1. Under **WhatsApp** → **Configuration** in the Meta App Dashboard, click **Edit** under Webhook.
2. Enter your Callback URL:
   ```text
   https://your-domain.com/cwfms/api/whatsapp_webhook.php
   ```
   *(For local testing, use a secure tunnel like ngrok: `https://xxxx.ngrok-free.app/cwfms/api/whatsapp_webhook.php`)*
3. Enter your Verify Token:
   ```text
   ira_sportswear_wh_verify_secret_token_2026
   ```
4. Click **Verify and Save**.
5. Under **Webhook Fields**, subscribe to **`messages`**.
   - This delivers real-time delivery status updates: `sent`, `delivered`, `read`, and `failed`.

---

## 3. Workflow Automations Reference

When `WHATSAPP_AUTOMATIONS_ENABLED=1`:
1. **Lead Created** with `Send WhatsApp Notification` checked:
   - System automatically sends `ira_lead_acknowledgement` to customer's mobile number.
2. **Status changed to `Awaiting Clarification`**:
   - Sends `ira_clarification_request` to customer.
3. **Status changed to `Approved`**:
   - Sends `ira_order_approved` to customer.
4. **Status changed to `Moved to Production`**:
   - Sends `ira_moved_to_production` to customer.
5. **Drag-and-Drop & Table Bulk Status Changes**:
   - Status transitions automatically fire corresponding template notifications without blocking UI operations.

---

## 4. Security Rules
- **Sales Executive Isolation**: Sales Executives can only trigger WhatsApp messages on leads assigned to them. Attempts to message unassigned or other reps' leads return HTTP 403.
- **Lockout Prevention**: API tokens and secrets are stored server-side in `.env` and are never exposed in HTML, JavaScript, or public API responses.
- **Signature Verification**: Webhooks verify the `X-Hub-Signature-256` HMAC signature using `WHATSAPP_APP_SECRET`.
