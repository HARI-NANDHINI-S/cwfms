# CWFMS Quality Assurance & Testing Guide

**Application:** Customer & Workflow Management System (CWFMS)  
**Client:** Studio 3 Interactive / Ira Sportswear  
**Document Version:** 1.0.0  
**Date:** November 2026

---

## 1. Overview & Test Strategy

CWFMS features an automated test suite comprising unit tests, integration tests, security harnesses, and visual verification showcases. Testing covers all requirements across:
- **Customer Management Module (CM-01 through CM-11)**
- **Lead Management Module (LM-01 through LM-24)**
- **Cross-Module Integration (INT-01 through INT-12)**
- **Security & Authorization Controls (SEC-01 through SEC-09)**

---

## 2. Test Suite Catalog

The following test suites reside in the `tests/` directory:

| Test Script | Scope & Test Focus | Scenarios Covered |
|:---|:---|:---:|
| `tests/phase1_test.php` | Customer validation, duplicate checks, profile scoring calculation (5% to 100%) | 12 |
| `tests/phase2_test.php` | Customer status toggling, soft deletion, role permission matrix | 10 |
| `tests/phase3_test.php` | Lead creation, 10 workflow status transitions, athletic team sportswear fields | 14 |
| `tests/phase4_test.php` | Kanban pipeline, filter queries, bulk reassignment, bulk status changes | 12 |
| `tests/phase5_test.php` | File upload MIME validation, size enforcement, storage naming | 10 |
| `tests/phase6_test.php` | WhatsApp Meta Cloud API parameters, template formatting, audit logging | 8 |
| `tests/e2e_final_audit_test.php` | Complete end-to-end multi-role workflows (Scenarios A, B, C, D) | 24 |

---

## 3. Running Automated CLI Tests

Run test suites using the PHP CLI runner from the project root:

### 3.1 Run All End-to-End Scenarios
```bash
php tests/e2e_final_audit_test.php
```

### 3.2 Run Individual Phase Suites
```bash
# Customer Module Tests
php tests/phase1_test.php
php tests/phase2_test.php

# Lead Module Tests
php tests/phase3_test.php
php tests/phase4_test.php

# Security & Upload Validation Tests
php tests/phase5_test.php

# WhatsApp Service Tests
php tests/phase6_test.php
```

---

## 4. Multi-Role Workflow Test Scenarios

The `e2e_final_audit_test.php` suite models 4 realistic business personas:

### Scenario A: System Administrator Workflow
- Authenticates using bcrypt password verification.
- Creates new customer profile (e.g., "Apex Cricket Academy").
- Verifies profile score updates from initial score to 100%.
- Creates athletic team lead for Apex Cricket Academy.
- Reassigns lead to Sales Executive.
- Verifies admin-exclusive deletion rights.

### Scenario B: Sales Manager Workflow
- Logs in as Sales Manager.
- Filters Kanban pipeline by Sport ("Cricket") and Priority ("High").
- Performs bulk status transition from "Received" to "Analysis".
- Performs bulk reassignment across 3 team leads.
- Exports filtered leads to CSV.
- Verifies deletion attempt is blocked with HTTP 403.

### Scenario C: Sales Executive Workflow
- Logs in as Sales Executive.
- Views assigned leads only (unassigned or other rep leads are hidden).
- Updates lead status for assigned lead from "Analysis" to "Order Sheet Prepared".
- Uploads customer custom vector artwork (`.ai` / `.pdf`).
- Attempts status change on another representative's lead; verifies blocked with HTTP 403.
- Attempts customer edit or deletion; verifies blocked with HTTP 403.

### Scenario D: WhatsApp Notification & Audit Trail
- Triggers status change on lead to "Approved".
- Validates automated WhatsApp message parameter extraction (Customer Name, Ref ID).
- Logs audit entry to `lead_whatsapp_messages` table.
- Simulates Meta webhook status delivery update (`sent` -> `delivered` -> `read`).
- Verifies message appears in lead's WhatsApp communication history card.

---

## 5. Security & Vulnerability Test Procedures

### 5.1 CSRF Attack Simulation
1. Issue a `POST` request to `api/customers.php` or `api/leads.php` with a valid session cookie but without the `X-CSRF-Token` header.
2. **Expected Result:** HTTP 403 Forbidden with message `"CSRF token validation failed. Request denied."`.

### 5.2 Brute-Force Rate Limiting Test
1. Send 6 consecutive failed login requests with an invalid password from the same IP address.
2. **Expected Result:**
   - Attempts 1–5 return HTTP 401 with generic error: `"Invalid email or password."`.
   - Attempt 6 returns HTTP 429 with message: `"Too many failed login attempts. Please try again after 15 minutes."`.

### 5.3 Malicious File Upload Test
1. Create a PHP script named `shell.php.png` containing `<?php phpinfo(); ?>`.
2. Upload via `api/leads.php`.
3. **Expected Result:** HTTP 422 Unprocessable Entity with message `"Security violation: uploaded file contains executable or script content."`.

---

## 6. Visual Preview Showcases

For visual inspection of Kanban layouts, responsiveness, and dark/light themes, open the following static previews in any browser:

- `tests/system_audit_visual_showcase.html` - Comprehensive audit dashboard showcasing all components.
- `tests/kanban_visual_preview.html` - Interactive 10-column Kanban board with sample athletic leads.
- `tests/phase3_visual_preview.html` - Lead detail modal, sports cards, and priority badges.
- `tests/phase5_branded_showcase.html` - Ira Sportswear branded typography and layout elements.
