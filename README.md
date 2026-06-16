# Customer Management Module
**Order Management Application – Phase 1** | Studio 3 Interactive

---

## 🚀 Quick Setup (5 Steps)

### Step 1 — Supabase Setup
1. Go to [supabase.com](https://supabase.com) → Create a new project
2. Open **SQL Editor** → paste contents of `supabase_setup.sql` → Run

### Step 2 — Configure the App
Edit `includes/config.php`:
```php
define('SUPABASE_URL',         'https://YOUR_PROJECT_ID.supabase.co');
define('SUPABASE_ANON_KEY',    'YOUR_ANON_KEY');
define('SUPABASE_SERVICE_KEY', 'YOUR_SERVICE_ROLE_KEY');
```
Find these in Supabase → Settings → API.

### Step 3 — Deploy
Upload to Apache/Nginx with PHP 8.0+. Requires: `curl`, `json` extensions.

### Step 4 — Login
**Default password: `password123`**
| Email | Role |
|---|---|
| admin@studio3.com | Admin (full access) |
| manager@studio3.com | Manager (create, edit, view) |
| staff@studio3.com | Staff (create, view) |

---

## 📁 File Structure

```
customer-management/
├── index.php                  # Dashboard + customer list
├── login.php / logout.php     # Auth
├── .htaccess                  # Security + routing
├── supabase_setup.sql         # DB schema + seed data
├── utils.py                   # Python CLI (import/export/stats)
├── sample_import.csv          # CSV template example
├── includes/config.php        # Supabase config + helpers
├── api/
│   ├── customers.php          # CRUD API
│   ├── customer.php           # Single fetch + search + export
│   ├── stats.php              # Dashboard stats
│   ├── activity.php           # Activity log
│   └── auth.php               # User management API
├── pages/
│   ├── customer-detail.php    # Full profile page
│   ├── import.php             # CSV bulk import UI
│   └── users.php              # User management (admin)
└── assets/css/style.css  assets/js/app.js
```

---

## ✅ Features

- Add / Edit / Delete / View customers (full CRUD)
- Live search + filter by state/country
- Paginated table (10/page)
- Customer detail page with activity log
- CSV bulk import (drag & drop, validation, step UI)
- CSV export of filtered data
- Dashboard stats (animated counters)
- Role-based access: Admin / Manager / Staff
- Session authentication
- Activity logging (created/updated/deleted/viewed)
- User management panel (admin only)
- Python CLI for bulk operations

## 🐍 Python CLI
```bash
export SUPABASE_URL="https://xxx.supabase.co"
export SUPABASE_SERVICE_KEY="your-key"

python3 utils.py validate --file customers.csv
python3 utils.py import --file customers.csv --dry-run
python3 utils.py import --file customers.csv
python3 utils.py export --format csv
python3 utils.py stats
```

## 🔮 Phase 2 Roadmap
WhatsApp integration · Multiple addresses · GST field · Customer tagging · Lead/Order modules

---
*Built by Studio 3 Interactive*
