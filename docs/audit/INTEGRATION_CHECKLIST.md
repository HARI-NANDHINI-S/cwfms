# Customer & Lead Module Integration Checklist

**Repository:** `https://github.com/HARI-NANDHINI-S/cwfms`  
**Audit Date:** November 20, 2026  
**Auditor:** Antigravity AI Assistant  

---

## 1. Integration Scope & Architecture

The Customer Management Module and Lead Management Module represent two interconnected stages of the Ira Sportswear order lifecycle:
1. **Customer Management**: Stores foundational institutional relationships (sports academies, school teams, corporate clubs, shipping addresses, primary contacts).
2. **Lead Management**: Manages customized athletic apparel enquiries, sport specifications, team roster files, custom art designs, and sales representative follow-ups.

They must operate as a unified, tightly coupled system. This checklist audits every point of contact between both modules.

---

## 2. Integration Requirements Matrix

| ID | Integration Requirement | Status | Primary Files | Schema References | Description & Verification Evidence | Missing Functionality / Defect |
|---|---|:---:|---|---|---|---|
| **INT-01** | Lead Creation Requires Customer Association | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `leads.customer_id` $\to$ `customers.id` | Lead form provides a customer search/dropdown. Backend enforces non-empty `customer_id` and verifies customer existence in DB before saving. | None. Unassociated leads are rejected. |
| **INT-02** | Foreign Key Integrity & Cascade Rules | ✅ COMPLETED | `supabase_setup.sql` | `FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL` | Safe relational reference. If a customer is deleted, historical leads are preserved without database corruption. | None. |
| **INT-03** | Retrieve Customer Info from Lead Views | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `customers.name`, `customers.team_academy_name`, `customers.mobile_number` | Lead cards display Academy/Team name and Contact name. Lead details modal displays full customer card with phone and address. | None. Relational join works in `api/leads.php`. |
| **INT-04** | One-to-Many Customer to Leads Relationship | ✅ COMPLETED | `supabase_setup.sql`, `api/leads.php` | 1 `customers` record to $N$ `leads` records | System allows and tracks multiple leads per customer across different sports and seasons. | None. |
| **INT-05** | View Customer's Linked Leads in Customer Profile | ✅ COMPLETED | `pages/customer-detail.php`, `api/customers.php` | `leads.customer_id = customers.id` | Dedicated Active Pipeline card, real count indicator, and "+ New Enquiry" action implemented. | Verified in `customer-detail.php`. |
| **INT-06** | Cross-Module Navigation: Customer $\to$ Leads | ✅ COMPLETED | `pages/customer-detail.php`, `pages/import.php` | UI Navigation Links | Sidebar nav item active with direct link to `leads.php`. Deep links for customer pre-selection supported. | Verified in `customer-detail.php` & `import.php`. |
| **INT-07** | Cross-Module Navigation: Lead $\to$ Customer Profile | ✅ COMPLETED | `pages/leads.php` | UI Navigation Links | Customer names on Kanban cards, Table rows, and Lead Detail modal wrap in direct links to `customer-detail.php?id={id}`. | Verified in `leads.php`. |
| **INT-08** | Repeat Customer Identification (⭐ Badge) | ✅ COMPLETED | `api/leads.php`, `pages/leads.php` | Relational query on `leads.customer_id` | `api/leads.php` calculates whether customer has $>1$ leads. If true, Kanban card renders the ⭐ Repeat Customer indicator. | None. Verified in API response and card renderer. |
| **INT-09** | Customer Updates Reflect in Lead Views | ✅ COMPLETED | `api/leads.php`, `pages/leads.php` | Dynamic relational join | Since `api/leads.php` performs a live join/lookup on `customers` table, editing a customer's phone or team name immediately updates all lead cards. | None. Zero stale denormalized data. |
| **INT-10** | WhatsApp Recipient Resolution | ✅ COMPLETED | `includes/whatsapp.php`, `api/leads.php` | `customers.mobile_number`, `customers.country_code` | When dispatching WhatsApp notifications for a lead, recipient phone is dynamically pulled from the linked customer record. | None. Validated in `WhatsAppService`. |
| **INT-11** | Customer Activity Log on Lead Creation | ✅ COMPLETED | `api/leads.php`, `customer_activity_log` | `customer_activity_log.action` | Lead creation, status updates, and deletions automatically dispatch cross-reference audit entries to `activity_logs` via `addLeadActivity()`. | Verified in `api/leads.php` & `customer-detail.php`. |
| **INT-12** | Linking Customers with Orders | ❌ NOT IMPLEMENTED | N/A | None | Orders module not yet developed. | Downstream expansion. |

---

## 3. In-Depth Root Cause Analysis of Cross-Module Gaps

### Gap 1: Missing Linked Leads in `pages/customer-detail.php` (INT-05 & INT-06)
- **Current State:**
  When an administrator or sales manager opens `http://localhost/cwfms/pages/customer-detail.php?id=...`, they see:
  - Customer Name & Initials
  - Contact details (Email, Full Mobile with country code)
  - Shipping Address Card
  - Customer Profile Completeness Score Card
  - Recent Activity Log (`customer_activity_log`)
  - A left sidebar that contains:
    ```html
    <div class="nav-section-label" style="margin-top:8px">Coming Soon</div>
    <span class="nav-item" style="opacity:0.4;cursor:not-allowed">
        <svg class="nav-icon" ...></svg>
        Leads
    </span>
    ```
- **Why this is a defect:**
  Requirement CM-06 explicitly specifies: *"Verify that the complete customer information is displayed. Check linked sections: Leads, Orders, Payment history (future scope)."*
  Furthermore, the customer profile is the primary CRM asset for Ira Sportswear. When sales personnel look up a school academy, they must immediately see all current and past uniforms/gear enquiries submitted by that academy.
- **Remediation Plan:**
  1. In `pages/customer-detail.php`, query `leads` table where `customer_id = eq.$id` and `is_deleted = eq.false`.
  2. Add a new card or section titled **"Active & Historical Enquiries (Leads)"**.
  3. Render a data table with columns: `Ref / ID`, `Sport`, `Products`, `Priority`, `Status`, `Assigned Rep`, `Enquiry Date`, and `Actions` (View Lead).
  4. Update the sidebar navigation link from `Coming Soon` to `<a href="leads.php" class="nav-item">`.

---

### Gap 2: Missing Navigation Link from Lead Card to Customer Profile (INT-07)
- **Current State:**
  In `pages/leads.php`, when viewing the lead details modal or inspecting a Kanban card:
  - Customer name is rendered as plain text: `<div>${custDisplay}</div>`.
  - There is no hyperlink to open the customer profile.
- **Remediation Plan:**
  Wrap customer name in a link pointing to `customer-detail.php?id=${encodeURIComponent(lead.customer_id)}` with a tooltip *"View Full Customer Profile"*.

---

### Gap 3: Bidirectional Activity Audit Logging (INT-11)
- **Current State:**
  `addLeadActivity()` logs to `lead_activity_log`. However, `customer_activity_log` receives no notification.
- **Remediation Plan:**
  When a lead is created, call `$db->logActivity($customerId, 'lead_created', null, $userName, "Enquiry {$leadRef} submitted for {$sport}")`.
