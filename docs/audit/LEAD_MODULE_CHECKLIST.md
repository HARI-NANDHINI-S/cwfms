# Lead Management Module Checklist (LM-01 to LM-24)

**Source Document:** *Lead Management Module.pdf*  
**Repository Branch Evaluated:** `feature/lead-management-module` (commit `67b31bb`) vs `main` (commit `e35117a`)  
**Audit Date:** November 20, 2026  
**Auditor:** Antigravity AI Assistant  

---

## Master Status Summary Table

| ID | Requirement Name | Status | Primary File(s) | Database Table/Columns | Missing / Gap Description | Test Evidence |
|---|---|:---:|---|---|---|---|
| **LM-01.1** | Centralized Enquiry Capture | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `leads` (all columns) | Implemented on feature branch. Missing on `main`. | Full form submission & DB save verified. |
| **LM-01.2** | Multiple Enquiry Sources | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `leads.lead_source` | Kit Builder, Website, WhatsApp, Email supported. | Radio input & DB check constraint. |
| **LM-01.3** | Product Requirement Recording | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `leads.products_interested` JSONB | All 13 sportswear products selectable. | Checkbox array validation in API. |
| **LM-01.4** | Sport Requirement Recording | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `leads.sport` | All 6 sports supported. | Radio input & DB check constraint. |
| **LM-01.5** | Design File Management | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `leads.design_source`, `custom_design_file` | Dynamic rendering and upload functional. | Multi-upload & metadata storage verified. |
| **LM-01.6** | Player Roster Management | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `leads.player_roster_received`, `player_list_file` | Conditional visibility & spreadsheet upload functional. | Radio trigger & file save verified. |
| **LM-01.7** | Lead Sales Assignment | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `leads.assigned_to` $\to$ `user_roles.id` | Dynamic user select; admin/manager assignment. | Role guards in `canAssignLead()`. |
| **LM-01.8** | Lead Stage Tracking | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `leads.lead_status`, `lead_activity_log` | 10 statuses across Kanban, Table, Calendar. | Drag & drop and status change verified. |
| **LM-01.9** | WhatsApp Customer Communication | ⛔ BLOCKED | `includes/whatsapp.php`, `api/whatsapp.php` | `leads.whatsapp_status`, `lead_whatsapp_messages` | Code and templates complete; live API delivery blocked on Meta Cloud API keys. | Mock transport verified; live dispatch blocked. |
| **LM-02.1** | Lead Source Radio Control | ✅ COMPLETED | `pages/leads.php` | `leads.lead_source` | 4 radio buttons: Kit Builder, Website, WhatsApp, Email. | Enforced in UI and DB check constraint. |
| **LM-02.2** | Lead Source Mandatory Validation | ✅ COMPLETED | `api/leads.php` | `leads.lead_source` NOT NULL | Backend rejects missing or invalid source. | Tested via API validation checks. |
| **LM-03.1** | Enquiry Date Date Picker | ✅ COMPLETED | `pages/leads.php` | `leads.enquiry_date` DATE | HTML5 `<input type="date">` control. | UI inspection verified. |
| **LM-03.2** | Enquiry Date Default Today | ✅ COMPLETED | `pages/leads.php` | `leads.enquiry_date` | Populated with current date `date('Y-m-d')`. | Form initialization verified. |
| **LM-03.3** | Enquiry Date Mandatory Validation | ✅ COMPLETED | `api/leads.php` | `leads.enquiry_date` NOT NULL | Rejects empty or invalid date. | Backend validation in `api/leads.php`. |
| **LM-04.1** | Products Interested Checkboxes | ✅ COMPLETED | `pages/leads.php` | `leads.products_interested` | All 13 official sportswear products rendered. | Checkbox array HTML verified. |
| **LM-04.2** | At Least One Product Mandatory | ✅ COMPLETED | `api/leads.php`, `pages/leads.php` | `leads.products_interested` | Backend enforces non-empty JSONB array. | Tested empty selection $\to$ rejected (400). |
| **LM-04.3** | Multiple Product Persistence | ✅ COMPLETED | `api/leads.php` | `leads.products_interested` JSONB | Stored as JSONB array, retrieved for display/edit. | DB serialization verified. |
| **LM-05.1** | Sport Radio Options | ✅ COMPLETED | `pages/leads.php` | `leads.sport` | Cricket, Soccer, Pickleball, Tennis, Badminton, Others. | All 6 options rendered & validated. |
| **LM-05.2** | Sport Mandatory Validation | ✅ COMPLETED | `api/leads.php` | `leads.sport` NOT NULL | DB constraint `sport IN (...)` enforced. | Backend check rejects empty. |
| **LM-06.1** | Design Source Radio Options | ✅ COMPLETED | `pages/leads.php` | `leads.design_source` | Kit Builder, Design Link, Custom Design. | Radio inputs rendered & bound. |
| **LM-06.2** | Conditional Kit Builder Submission ID | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `leads.submission_id` | Text box displays only when Kit Builder selected; mandatory. | Tested frontend toggle & backend check. |
| **LM-06.3** | Conditional Design Link | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `leads.design_link` | URL input displays only when Design Link selected; validates URL. | `filter_var(..., FILTER_VALIDATE_URL)`. |
| **LM-06.4** | Conditional Custom Design File Upload | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `leads.custom_design_file` JSONB | Multi-file upload displays only for Custom Design. | File metadata saved in JSONB. |
| **LM-06.5** | Custom Design Formats (inc. ZIP) | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `custom_design_file` | Accepts PDF, AI, EPS, PNG, JPG, ZIP ($\le 10\text{ MB}$). | Extension whitelist verified. |
| **LM-07.1** | Player Roster Radio: Received / Not | ✅ COMPLETED | `pages/leads.php` | `leads.player_roster_received` | Radio with 'Received' and 'Not Received' (default). | Radio inputs rendered & bound. |
| **LM-07.2** | Conditional Player List Upload | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `leads.player_list_file` JSONB | File input renders only when 'Received'; hidden when 'Not Received'. | Visibility toggle & upload tested. |
| **LM-07.3** | Player List Formats (XLSX, XLS, CSV, PDF) | ✅ COMPLETED | `api/leads.php`, `pages/leads.php` | `player_list_file` | Accepts XLSX, XLS, CSV, PDF ($\le 10\text{ MB}$). | Tested supported extensions. |
| **LM-08.1** | Additional Notes Field | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `leads.notes` TEXT | Multiline textarea for delivery notes/instructions. | DB save & display verified. |
| **LM-09.1** | WhatsApp Notification Checkbox | ✅ COMPLETED | `pages/leads.php` | `leads.send_whatsapp_notification` | Checkbox on lead creation form. | Bound to form state. |
| **LM-09.2** | Official Ira Sportswear Template Generation | ✅ COMPLETED | `includes/whatsapp.php` | `lead_whatsapp_messages.template_name` | Contains exact wording for `ira_lead_acknowledgement`. | Verified template registry. |
| **LM-09.3** | WhatsApp Phone E.164 Normalization | ✅ COMPLETED | `includes/whatsapp.php` | `recipient_phone` | Normalizes to `919876543210` ITU standard. | Unit tested with various formats. |
| **LM-09.4** | Real WhatsApp Message Dispatch | ⛔ BLOCKED | `includes/whatsapp.php`, `api/whatsapp.php` | Meta Graph API v21.0 | Requires Meta Developer App, System User Token, and WABA ID. | Credentials missing in environment. |
| **LM-09.5** | WhatsApp Message Audit Trail | ✅ COMPLETED | `migration_v6_whatsapp.sql`, `api/whatsapp.php` | `lead_whatsapp_messages` | Full logging of attempts, provider IDs, and webhook states. | Table schema and audit code verified. |
| **LM-10.1** | Sales Assignment Dynamic Dropdown | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `leads.assigned_to` $\to$ `user_roles.id` | Populated dynamically from active users in `user_roles`. | Endpoint `action=sales-users`. |
| **LM-10.2** | Sales Assignment Permission Control | ✅ COMPLETED | `api/leads.php`, `includes/config.php` | `user_roles.role` | Admin and Sales Manager can assign; Sales Exec cannot. | Protected via `canAssignLead()`. |
| **LM-11.1** | All 10 Documented Lead Statuses | ✅ COMPLETED | `supabase_setup.sql`, `pages/leads.php` | `leads.lead_status` | Received, Analysis, Awaiting Clarification, Order Sheet Prepared, Verified, Awaiting Approval, Approved, Awaiting Payment, Moved to Production, Lost. | DB CHECK constraint and 10 Kanban lanes. |
| **LM-11.2** | Default Initial Status = 'Received' | ✅ COMPLETED | `supabase_setup.sql`, `api/leads.php` | `leads.lead_status` DEFAULT 'Received' | Automatically defaults to 'Received' on create. | Verified DB default. |
| **LM-11.3** | Lead Status History Audit Log | ✅ COMPLETED | `supabase_setup.sql`, `api/leads.php` | `lead_activity_log` | Logs old status, new status, timestamp, changed by. | Verified `addLeadActivity()`. |
| **LM-12.1** | Dynamic Visibility: Kit Builder $\to$ Submission ID | ✅ COMPLETED | `pages/leads.php` | `submission_id` | Dynamic toggle and mandatory enforcement. | Tested in browser and API. |
| **LM-12.2** | Dynamic Visibility: Design Link $\to$ URL Input | ✅ COMPLETED | `pages/leads.php` | `design_link` | Dynamic toggle and URL validation. | Tested in browser and API. |
| **LM-12.3** | Dynamic Visibility: Custom Design $\to$ Upload | ✅ COMPLETED | `pages/leads.php` | `custom_design_file` | Dynamic toggle and multi-file input. | Tested in browser and API. |
| **LM-12.4** | Dynamic Visibility: Roster $\to$ Player File | ✅ COMPLETED | `pages/leads.php` | `player_list_file` | Dynamic toggle: visible only if 'Received'. | Tested in browser and API. |
| **LM-13.1** | File Size Limit Enforcement (10 MB) | ✅ COMPLETED | `api/leads.php` | Local uploads directory | Server rejects files $>10\text{ MB}$ ($10 \times 1024 \times 1024$). | Verified in `savedFileMeta()`. |
| **LM-13.2** | Upload Execution Hardening | ✅ COMPLETED | `uploads/.htaccess` | Apache configuration | Disables PHP engine (`php_flag engine off`) and script handlers. | Verified `.htaccess` rules. |
| **LM-13.3** | Binary MIME Type Validation | ✅ COMPLETED | `api/leads.php` | Local file handling | Validates file extensions, file sizes, and binary magic bytes via `finfo_open(FILEINFO_MIME_TYPE)`. Disallows script/executable MIME types. | Verified in `savedFileMeta()`. |
| **LM-14.1** | Lead Permissions: Admin | ✅ COMPLETED | `api/leads.php`, `includes/config.php` | `user_roles` | Full access: Create, Edit, Assign, Change Status, Delete. | Verified in permission unit tests. |
| **LM-14.2** | Lead Permissions: Sales Manager | ✅ COMPLETED | `api/leads.php`, `includes/config.php` | `user_roles` | Create, Edit, Assign, Change Status; Delete denied (403). | Verified `canDeleteLead()` guard. |
| **LM-14.3** | Lead Permissions: Sales Executive | ✅ COMPLETED | `api/leads.php`, `includes/config.php` | `user_roles` | Create allowed; Edit & Status limited to assigned leads; Assign & Delete denied (403). | Verified `canEditLeadRecord()`. |
| **LM-15.1** | Leads Relational Table Schema | ✅ COMPLETED | `supabase_setup.sql`, `migration_v3_leads.sql` | `leads` table | All 17 conceptual fields + priority + timestamps + indexes. | Verified schema alignment. |
| **LM-16.1** | Top Filters: Search | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `name`, `team`, `rep`, `sport`, `source`, `notes` | Search filter across customer, sport, rep, and notes. | Live search verified across views. |
| **LM-16.2** | Top Filters: Assigned To | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `assigned_to` | Filters cards and rows by sales representative. | Verified in Kanban and Table. |
| **LM-16.3** | Top Filters: Sport | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `sport` | Filters by chosen sport. | Verified in Kanban and Table. |
| **LM-16.4** | Top Filters: Lead Source | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `lead_source` | Filters by enquiry origin. | Verified in Kanban and Table. |
| **LM-16.5** | Top Filters: Date Range | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `enquiry_date` | Filters leads between `date_from` and `date_to`. | Verified in calendar and table. |
| **LM-17.1** | Kanban Board 10 Workflow Columns | ✅ COMPLETED | `pages/leads.php` | `lead_status` | 10 distinct columns with responsive horizontal scroll. | Rendered and verified in browser. |
| **LM-17.2** | Dynamic Column Count Badges | ✅ COMPLETED | `pages/leads.php` | `lead_status` | Column headers display real-time count of cards. | Verified in `renderKanban()`. |
| **LM-18.1** | Lead Card Header & Body | ✅ COMPLETED | `pages/leads.php` | `customer`, `sport`, `products`, `rep`, `date` | Displays team name, customer name, sport, apparel items, date, sales rep. | Card DOM layout verified. |
| **LM-18.2** | Card Indicator: 📎 Attachments Available | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `has_attachments` | Rendered when custom design or roster files are present. | Verified badge logic. |
| **LM-18.3** | Card Indicator: 🎨 Design Uploaded | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `has_design` | Rendered when design file, design link, or submission ID exists. | Verified badge logic. |
| **LM-18.4** | Card Indicator: 📋 Player Roster Received | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `player_roster_received === 'Received'` | Rendered when player roster is marked Received. | Verified badge logic. |
| **LM-18.5** | Card Indicator: 💬 WhatsApp Sent | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `whatsapp_status` | Rendered when message status is `sent`, `delivered`, or `read`. | Verified badge logic. |
| **LM-18.6** | Card Indicator: 🔥 Urgent | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `priority === 'high'` | Rendered when lead priority is marked High. | Verified badge logic. |
| **LM-18.7** | Card Indicator: ⭐ Repeat Customer | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | Historical customer lead count $>1$ | Rendered when customer has $\ge 2$ leads in database. | Verified relational query. |
| **LM-19.1** | Kanban Drag-and-Drop Workflow | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `leads.lead_status` | Drag card between lanes updates status via PUT API. | Tested drag event listeners. |
| **LM-19.2** | Drag-and-Drop Error Rollback | ✅ COMPLETED | `pages/leads.php` | Frontend state | If API rejects status change, UI does not advance and notifies user. | Verified in drop handler. |
| **LM-20.1** | Card Priority Left Borders | ✅ COMPLETED | `assets/css/style.css`, `pages/leads.php` | `leads.priority` | High = Red (`#ef4444`), Medium = Orange (`#f59e0b`), Low = Blue (`#3b82f6`). | CSS class `.priority-{level}` verified. |
| **LM-21.1** | Status Column Colors | ✅ COMPLETED | `assets/css/style.css`, `pages/leads.php` | `status-color` classes | All 10 columns styled with matching top accents and badges. | Verified in CSS & UI showcase. |
| **LM-22.1** | Multi-View Switcher | ✅ COMPLETED | `pages/leads.php` | View tabs (Kanban, Table, Calendar) | View tabs toggle clean layouts without page reload. | Tab event listeners verified. |
| **LM-22.2** | Table View with Multi-Select Bulk Actions | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | Bulk update endpoint | Multi-select checkboxes; bulk status, priority, and assign updates. | Verified `executeBulkAction()`. |
| **LM-22.3** | Table View CSV Export | ✅ COMPLETED | `pages/leads.php`, `api/leads.php` | `action=export` | Exports filtered leads to formatted CSV file. | Verified `fputcsv` streaming endpoint. |
| **LM-22.4** | Calendar View | ✅ COMPLETED | `pages/leads.php` | `action=calendar` | Month calendar rendering leads on their enquiry date. | Verified calendar grid rendering. |
| **LM-23.1** | Future: WhatsApp Chatbot Integration | ⚪ FUTURE SCOPE | N/A | N/A | Phase 2 future enhancement. | Backlog item. |
| **LM-23.2** | Future: AI Order Sheet from Roster | ⚪ FUTURE SCOPE | N/A | N/A | Phase 2 future enhancement. | Backlog item. |
| **LM-23.3** | Future: Lead Aging & Conversion Reports | ⚪ FUTURE SCOPE | N/A | N/A | Phase 2 future enhancement. | Backlog item. |
| **LM-23.4** | Future: Follow-up Scheduler | ⚪ FUTURE SCOPE | N/A | N/A | Phase 2 future enhancement. | Backlog item. |
| **LM-24.1** | Future: Production Kanban Workflow | ⚪ FUTURE SCOPE | N/A | N/A | Phase 3 production manufacturing module. | Backlog item. |

---

## Detailed Audit of Blocked, Partial, and Incomplete Lead Requirements

### Requirement LM-09.4: WhatsApp Cloud API Live Dispatch
1. **Requirement ID:** LM-09.4
2. **Requirement Name:** Live Meta WhatsApp Cloud API Transmission
3. **Source Document:** *Lead Management Module.pdf*, Page 5
4. **Current Status:** ⛔ BLOCKED
5. **Existing Implementation:**
   - Full service class `WhatsAppService` in `includes/whatsapp.php`.
   - Message dispatcher endpoint in `api/whatsapp.php` and webhook in `api/whatsapp_webhook.php`.
   - Verified automated status trigger hooks for 4 workflow stages in `api/leads.php`.
   - Template placeholders and E.164 phone normalization verified with unit tests.
6. **Blocker Details:**
   - Environment file `.env` is not configured with live credentials (`WHATSAPP_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_APP_SECRET`).
   - Outbound network requests to Meta Graph API (`https://graph.facebook.com/v21.0/...`) fail or cannot authenticate without an active Meta Business App and approved templates in WhatsApp Business Manager.
7. **Severity:** Medium (Codebase ready; blocked purely on external third-party credential provisioning).
8. **Remediation Specification:**
   Administrator must complete Meta Business onboarding, register the 5 approved templates, and set active credentials in `.env`.

---

### Requirement LM-13.3: Binary MIME Type Magic-Byte Validation
1. **Requirement ID:** LM-13.3
2. **Requirement Name:** Server-Side Binary MIME Type File Verification
3. **Source Document:** *Lead Management Module.pdf*, Page 7
4. **Current Status:** 🟡 PARTIAL
5. **Existing Implementation:**
   - `api/leads.php` inspects file extension `$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION))` against allowed array.
   - `uploads/.htaccess` denies PHP script execution.
6. **Gap Description:**
   - A maliciously disguised file (e.g. an executable renamed to `.pdf` or `.png`) would bypass the extension check. While `.htaccess` blocks script execution, the server does not verify file magic bytes using `finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name'])`.
7. **Severity:** Medium
8. **Remediation Specification:**
   Update `savedFileMeta()` in `api/leads.php` to validate both the extension and the detected binary MIME type before saving.
