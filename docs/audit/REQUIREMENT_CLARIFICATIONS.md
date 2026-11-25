# Requirement Clarifications & Documentation Discrepancies Report

**Project:** Ira Sportswear / Studio 3 Interactive — CWFMS  
**Reference Documents:**
1. *Customer Management Module.pdf*
2. *Lead Management Module.pdf*  
**Audit Date:** November 20, 2026  
**Auditor:** Antigravity AI Assistant  

---

## Overview

During the complete audit of both reference documents against the repository codebase, **9 specific ambiguities, discrepancies, and unspecified business rules** were identified. Per Part 13 of the operating instructions, these issues must be explicitly documented, analyzed against the existing implementation, and accompanied by recommended interpretations requiring stakeholder approval prior to Phase 1 finalization.

---

## Discrepancy Matrix

| ID | Topic | Source PDF & Section | Ambiguity / Discrepancy Description | Current Repository Implementation | Recommended Interpretation | Approval Needed? |
|---|---|---|---|---|---|---|
| **REQ-DISC-01** | Custom Design File Formats | *Lead PDF* p. 3 vs p. 7 | Section "Design Information" lists `PDF, AI, EPS, PNG, JPG`. Later "Business Rules" section additionally lists `ZIP`. | `api/leads.php` accepts `['pdf', 'ai', 'eps', 'png', 'jpg', 'jpeg', 'zip']` | **Permit ZIP files.** Custom sportswear orders frequently package vector art (.ai/.eps) along with fonts and mockups into an archive. | Yes (Confirm ZIP inclusion) |
| **REQ-DISC-02** | Lead Status: "Awaiting Clarification" Flow | *Lead PDF* p. 4 vs p. 5 | "Awaiting Clarification" is present in the 10-status list and Kanban columns, but omitted from the 8-stage linear flow diagram. | Present as a Kanban column (Column 3) and allowed in all transitions; triggers automated WhatsApp clarification request. | **Treat as an Exception State.** Any lead in `Analysis` or `Verified` can transition to `Awaiting Clarification` and back to `Analysis` once clarified. | Yes (Confirm state transitions) |
| **REQ-DISC-03** | Lead Status: "Lost" Flow | *Lead PDF* p. 4 vs p. 5 | "Lost" is in the 10-status list and Kanban column 10, but omitted from the linear pipeline. | Present as Column 10 on Kanban; allowed from any status by Admin/Sales Manager. | **Treat as a Terminal Exception State.** Leads can be marked `Lost` from any preceding status if the customer cancels or goes cold. | Yes (Confirm cancellation policy) |
| **REQ-DISC-04** | Sales Executive "Limited" Status Changes | *Lead PDF* p. 8 | Permissions table defines Sales Executive status changes as "Limited", but does not define which specific status transitions are permitted. | Interpreted as: Sales Executive can only change statuses on leads **assigned to them**, but can transition to any valid status. | **Recommended Refinement:** Sales Executive can transition their assigned leads between `Received`, `Analysis`, and `Order Sheet Prepared`. Moving to `Verified`, `Approved`, or `Moved to Production` requires Sales Manager/Admin sign-off. | Yes (Confirm boundary rules) |
| **REQ-DISC-05** | High / Medium / Low Priority Calculation | *Lead PDF* p. 9 & 10 | Cards show red/orange/blue priority left borders, but no calculation formula or criteria is defined in the PDF. | Column `leads.priority` (`low`, `medium`, `high`, default `medium`). Manually set on lead creation or via bulk action. | **Retain manual assignment for Phase 1**; in Phase 2 introduce automatic priority scoring based on enquiry date proximity, tournament deadline, and repeat customer status. | Yes (Approve Phase 1 manual approach) |
| **REQ-DISC-06** | Urgent (🔥) and Repeat Customer (⭐) Indicators | *Lead PDF* p. 10 | Icons are specified on Kanban cards without defining the underlying trigger criteria. | `api/leads.php` maps 🔥 Urgent to `priority === 'high'`, and ⭐ Repeat Customer to `count(leads where customer_id = X) > 1`. | **Adopt current logic as baseline.** Urgent = High priority; Repeat Customer = Customer has $\ge 2$ historical leads or orders. | Yes (Confirm indicator criteria) |
| **REQ-DISC-07** | Status Column Colors for "Awaiting Clarification" and "Lost" | *Lead PDF* p. 11 | Color table provides hex/name colors for 8 statuses, omitting "Awaiting Clarification" and "Lost". | `pages/leads.php` styles `Awaiting Clarification` as Warning Amber (`#f59e0b` / `status-yellow`) and `Lost` as Muted Gray (`#64748b` / `status-lost`). | **Adopt Amber and Gray.** Amber reflects pending customer response; Muted Gray cleanly communicates a closed/inactive lead. | Yes (Design sign-off) |
| **REQ-DISC-08** | Customer Payment History Scope | *Customer PDF* p. 5 | Payment history appears under customer profile specifications, but with an explicit note: "Payment history — future scope". | Not implemented in `customer-detail.php` or `api/customers.php`. | **Classify strictly as FUTURE SCOPE.** Do not mark Customer Module as defective or incomplete due to missing payments. | No (Explicitly confirmed by PDF) |
| **REQ-DISC-09** | WhatsApp Notification Checkbox vs Phase 2 WhatsApp Features | *Lead PDF* p. 5 vs p. 14 | Page 5 specifies a single checkbox on lead creation to dispatch an enquiry confirmation message. Page 14 lists WhatsApp chatbot, automatic reminders, etc., under Phase 2. | `feature/lead-management-module` implements the Phase 1 confirmation checkbox and template preview, plus a full audit log table `lead_whatsapp_messages`. Real API calls are blocked due to placeholder credentials. | **Phase 1 scope is strictly the enquiry confirmation template.** Automated status triggers and chatbots remain Phase 2 / Future Scope. Real dispatch is classified as ⛔ BLOCKED pending Meta credentials. | Yes (Acknowledge delivery block) |

---

## Detailed Analyses & Recommendations

### REQ-DISC-01: Custom Design Upload Formats (ZIP Inclusion)
- **Source Text:**
  - *Lead Management Module.pdf*, Page 3 ("Design Information"): *"Supported Formats: PDF, AI, EPS, PNG, JPG"*.
  - *Lead Management Module.pdf*, Page 7 ("Business Rules - File Upload"): *"Design File Upload: Supported formats: PDF, AI, EPS, PNG, JPG, ZIP. Maximum size: 10MB."*
- **Repository Implementation:**
  In `api/leads.php` (line 92):
  ```php
  $allowed = ['pdf', 'ai', 'eps', 'png', 'jpg', 'jpeg', 'zip', 'xlsx', 'xls', 'csv'];
  ```
  And in `pages/leads.php` (line 198):
  ```html
  <input type="file" name="custom_design_file[]" multiple accept=".pdf,.ai,.eps,.png,.jpg,.jpeg,.zip" />
  ```
- **Analysis:**
  Vector graphics in athletic apparel (embroidery patterns, screen-print color separations, vector Adobe Illustrator assets with custom typefaces) are predominantly delivered as ZIP bundles. Excluding ZIP would create operational friction for sports teams.
- **Recommendation:** Standardize supported formats for Custom Design uploads as: `PDF, AI, EPS, PNG, JPG, ZIP` with a strict 10 MB per-file limit.

---

### REQ-DISC-02 & REQ-DISC-03: Non-Linear Status Transitions ("Awaiting Clarification" & "Lost")
- **Source Text:**
  - *Lead Management Module.pdf*, Page 4 ("Lead Status List"): Enumerates 10 statuses including (3) Awaiting Clarification and (10) Lost.
  - *Lead Management Module.pdf*, Page 5 ("Lead Stage Tracking"): Illustrates only 8 sequential steps from Received $\to$ Moved to Production.
- **Repository Implementation:**
  All 10 columns are present on the Kanban board in `pages/leads.php`. The database CHECK constraint in `supabase_setup.sql` allows all 10 statuses. Status updates can be initiated via drag-and-drop or modal select.
- **Analysis:**
  Real-world sportswear lead lifecycles are inherently non-linear:
  - If a team submits a design without player measurements or Pantone numbers, the sales rep transitions the lead to *Awaiting Clarification*.
  - If a customer stops responding or chooses a competitor, the lead transitions to *Lost*.
- **Recommendation:** Maintain all 10 columns on the Kanban board. Formally document the state transition graph:
  $$\text{Any Status} \xrightarrow{\text{Incomplete Specs}} \text{Awaiting Clarification} \xrightarrow{\text{Specs Clarified}} \text{Analysis}$$
  $$\text{Any Active Status} \xrightarrow{\text{Cancelled / Cold}} \text{Lost}$$

---

### REQ-DISC-04: Sales Executive "Limited" Status Transitions
- **Source Text:**
  - *Lead Management Module.pdf*, Page 8 ("User Roles and Permissions"):
    | Role | Create Lead | Edit Lead | Assign Lead | Change Status | Delete Lead |
    |---|:---:|:---:|:---:|:---:|:---:|
    | Sales Executive | Yes | Yes | No | **Limited** | No |
- **Repository Implementation:**
  In `includes/config.php`:
  ```php
  function canChangeLeadStatus($lead, $user = null): bool {
      $user = $user ?? getCurrentUser();
      $norm = normalizeRole($user['role'] ?? 'staff');
      if (in_array($norm, ['admin', 'sales_manager'], true)) return true;
      $userId = $user['id'] ?? ($_SESSION['user']['id'] ?? null);
      return !empty($lead['assigned_to']) && $lead['assigned_to'] === $userId;
  }
  ```
- **Analysis:**
  Currently, "Limited" is enforced as an *ownership restriction* (can only modify leads assigned to them), rather than a *state machine gate*. A Sales Executive could technically move an order directly to "Moved to Production" without managerial verification.
- **Recommendation:**
  Implement a two-factor restriction:
  1. **Ownership Gate:** Must be assigned to the current Sales Executive.
  2. **Stage Boundary:** Sales Executives can transition between `Received`, `Analysis`, and `Order Sheet Prepared`. Moving to `Verified`, `Approved`, or `Moved to Production` requires role `sales_manager` or `admin`.

---

### REQ-DISC-05 & REQ-DISC-06: Priority Rules and Visual Indicators (🔥, ⭐)
- **Source Text:**
  - *Lead Management Module.pdf*, Page 9 ("Card Priority Colors"): Red = High, Orange = Medium, Blue = Low. No formula provided.
  - *Lead Management Module.pdf*, Page 10 ("Indicators"): Lists 🔥 Urgent and ⭐ Repeat Customer without business logic rules.
- **Repository Implementation:**
  - Priority is stored as a column (`priority VARCHAR(20) DEFAULT 'medium' CHECK (priority IN ('low', 'medium', 'high'))`).
  - 🔥 Urgent indicator appears whenever `lead.priority === 'high'`.
  - ⭐ Repeat Customer appears whenever `lead.customer_id` has $>1$ leads recorded in the database.
- **Recommendation:** Approve the current heuristic. For Phase 2, provide a configurable rule engine where a lead automatically becomes 🔥 Urgent if `enquiry_date` is $>5$ days old without advancing past `Analysis`, or if delivery target is within 10 days.

---

### REQ-DISC-07: Kanban Column Colors for "Awaiting Clarification" and "Lost"
- **Source Text:**
  - *Lead Management Module.pdf*, Page 11 ("Status Column Colors"):
    Received (Blue), Analysis (Orange), Order Sheet Prepared (Purple), Verified (Cyan), Awaiting Approval (Yellow), Approved (Green), Awaiting Payment (Red), Moved to Production (Dark Green).
- **Repository Implementation:**
  In `pages/leads.php`:
  - `Awaiting Clarification`: Warm Amber (`#f59e0b` / `.status-yellow`).
  - `Lost`: Slate Neutral (`#64748b` / `.status-lost`).
- **Recommendation:** Formally adopt Warm Amber and Slate Neutral to maintain consistent color semantics across the 10 Kanban lanes.

---

### REQ-DISC-09: WhatsApp Scope Boundaries
- **Source Text:**
  - *Lead Management Module.pdf*, Page 5 ("Customer WhatsApp Notification"): Checkbox sends initial enquiry confirmation template.
  - *Lead Management Module.pdf*, Page 14 ("Phase 2 - Future Enhancements"): Lists WhatsApp Chatbot, automated status updates, team alerts.
- **Repository Implementation:**
  - Code contains Phase 1 acknowledgement template and template preview.
  - Additionally implements automated status trigger hooks for 4 stages (`Received`, `Awaiting Clarification`, `Approved`, `Moved to Production`).
  - Meta Cloud API credentials are placeholders in `.env.example`.
- **Recommendation:**
  Classify the Phase 1 WhatsApp confirmation delivery as **⛔ BLOCKED** until the client provisions active Meta Business credentials. Mark all additional WhatsApp automations as **⚪ FUTURE SCOPE / PHASE 2**.
