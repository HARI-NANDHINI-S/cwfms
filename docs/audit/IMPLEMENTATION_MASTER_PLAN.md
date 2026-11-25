# CWFMS Implementation Master Plan & Execution Roadmap

**Project:** Ira Sportswear / Studio 3 Interactive — CWFMS  
**Audit Date:** November 20, 2026  
**Auditor:** Antigravity AI Assistant  
**Target Architecture:** Fully unified Customer & Lead Management System aligned with official PDF specifications  

---

## 1. Implementation Phasing & Dependency Graph

```
┌────────────────────────────────────────────────────────┐
│     Priority 1: Critical Foundation & Security         │
│  ├── GAP-01: Production Hashed Authentication          │
│  ├── GAP-02: Supabase Secret Key Isolation & RLS       │
│  └── GAP-06: Git Branch Harmonization (Feature -> Main)│
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
┌────────────────────────────────────────────────────────┐
│     Priority 2: Cross-Module Integration               │
│  ├── GAP-03: Linked Leads in Customer Profile          │
│  ├── GAP-04: Clickable Customer Links in Lead Views    │
│  └── GAP-07: Bidirectional Customer Activity Logging   │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
┌────────────────────────────────────────────────────────┐
│     Priority 3: Security & Upload Hardening            │
│  ├── GAP-05: Binary Magic-Byte File Validation         │
│  └── Anti-CSRF Token Support on Mutating Endpoints     │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
┌────────────────────────────────────────────────────────┐
│     Priority 4: External Communications Onboarding     │
│  └── GAP-08: Meta WhatsApp Cloud API Live Activation   │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
┌────────────────────────────────────────────────────────┐
│     Priority 5: End-to-End Verification & Sign-off     │
│  ├── Automated PHP CLI Regression Suites (Phases 1–6)  │
│  ├── Cross-Device Responsive Layout Verification       │
│  └── Final Deployment Checklist Verification           │
└────────────────────────────────────────────────────────┘
```

---

## 2. Detailed Implementation Phases

### Phase 1: Critical Security & Foundation (Priority 1)
- **Objective:** Eliminate vulnerabilities that permit administrative account takeover or database compromise.
- **Tasks:**
  1. Create `migration_v5_auth.sql` adding `password_hash VARCHAR(255)` to `user_roles`.
  2. Update `login.php` to verify passwords using `password_verify()` and remove demo credentials (`password123` and `$demoUsers` array).
  3. Update `includes/config.php` to load credentials strictly from `.env`, removing plaintext fallback service keys.
  4. Create `migration_v7_rls_hardening.sql` to restrict anonymous PostgREST access.
  5. Merge `feature/lead-management-module` into `main` cleanly via fast-forward.
- **Affected Files:** `login.php`, `includes/config.php`, `migration_v5_auth.sql`, `migration_v7_rls_hardening.sql`, `api/auth.php`.
- **Dependencies:** None. Must be executed first.

---

### Phase 2: Cross-Module CRM Integration (Priority 2)
- **Objective:** Unify Customer Management and Lead Management into a connected application.
- **Tasks:**
  1. Update `pages/customer-detail.php` to query `leads` table and render active and past leads in a dedicated "Linked Leads" data table.
  2. Enable the left sidebar navigation link in `pages/customer-detail.php` pointing to `pages/leads.php`.
  3. In `pages/leads.php`, wrap customer names in lead cards and the detail modal with direct links to `customer-detail.php?id={customer_id}`.
  4. In `api/leads.php`, log customer activity to `customer_activity_log` upon lead creation or major status changes.
- **Affected Files:** `pages/customer-detail.php`, `pages/leads.php`, `api/leads.php`.
- **Dependencies:** Phase 1 (branch harmonization).

---

### Phase 3: File Upload Security Hardening (Priority 3)
- **Objective:** Prevent malicious file disguised uploads.
- **Tasks:**
  1. In `api/leads.php` inside `savedFileMeta()`, implement binary magic-byte detection via `finfo_open(FILEINFO_MIME_TYPE)`.
  2. Enforce strict MIME whitelist matching corresponding extensions.
  3. Verify rejection of executable scripts renamed with image or PDF extensions.
- **Affected Files:** `api/leads.php`.
- **Dependencies:** Phase 1.

---

### Phase 4: External Communications Onboarding (Priority 4)
- **Objective:** Activate live WhatsApp customer notifications.
- **Tasks:**
  1. Client / Administrator completes Meta Business onboarding and generates permanent System User access token.
  2. Register 5 approved templates in WhatsApp Business Manager.
  3. Populate `.env` with `WHATSAPP_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_BUSINESS_ACCOUNT_ID`, and `WHATSAPP_APP_SECRET`.
  4. Configure Meta Webhook URL to point to `https://your-domain.com/cwfms/api/whatsapp_webhook.php`.
- **Dependencies:** Client external credentials.

---

### Phase 5: Verification & Production Release (Priority 5)
- **Objective:** Final functional, security, and responsive sign-off.
- **Tasks:**
  1. Run automated test suites: `tests/phase1_test.php` through `tests/phase6_test.php` and `tests/e2e_final_audit_test.php`.
  2. Execute linting (`php -l`) across all PHP files.
  3. Verify cross-device viewports (Desktop 1440px, Tablet 768px, Mobile 390px).
  4. Complete `DEPLOYMENT_CHECKLIST.md`.

---

## 3. Future Scope Backlog (Phases 2 & 3)

These items are documented as future expansions and are explicitly excluded from Phase 1:
1. **Customer Payment History** (*Customer PDF* p. 5) — Payment gateways, invoices, balance ledgers.
2. **Order Management Module** (*Customer PDF* p. 5) — Formal order processing, line items, deposits.
3. **Production Kanban Pipeline** (*Lead PDF* p. 15) — Pending Production $\to$ Printing $\to$ Stitching $\to$ Quality Check $\to$ Packing $\to$ Dispatched.
4. **WhatsApp Interactive Chatbot** (*Lead PDF* p. 14) — Automated customer self-service and status queries.
5. **AI Order Sheet Generation** (*Lead PDF* p. 14) — OCR parsing of spreadsheet/PDF player rosters into apparel sizing matrices.
6. **Lead Aging & Conversion Analytics** (*Lead PDF* p. 14) — Sales performance dashboards and funnel tracking.
