# 🚀 Customer Management System - Deployment Checklist

## 📋 Pre-Deployment Verification (Do This First)

- [ ] PHP 7.4+ installed and running
- [ ] XAMPP/Local server is active
- [ ] Supabase account created (free.supabase.com)
- [ ] All project files in place
- [ ] Modern web browser available

## 🔧 Configuration Setup (15 minutes)

### Step 1: Get Supabase Credentials
- [ ] Open https://supabase.com and sign in
- [ ] Create new project or select existing
- [ ] Go to **Project Settings → API**
- [ ] Copy **Project URL**
- [ ] Copy **Anon Key** (public)
- [ ] Copy **Service Role Key** (keep secret)

### Step 2: Update config.php
- [ ] Open `includes/config.php`
- [ ] Find these lines (around line 2-4):
  ```php
  define('SUPABASE_URL', 'YOUR_PROJECT_URL');
  define('SUPABASE_ANON_KEY', 'YOUR_ANON_KEY');
  define('SUPABASE_SERVICE_KEY', 'YOUR_SERVICE_KEY');
  ```
- [ ] Replace with **YOUR actual credentials**:
  - `SUPABASE_URL`: Paste from Project Settings
  - `SUPABASE_ANON_KEY`: Paste from Project Settings
  - `SUPABASE_SERVICE_KEY`: Paste from Project Settings
- [ ] Save file

### Step 3: Create Database Tables
- [ ] Open Supabase Dashboard
- [ ] Go to **SQL Editor**
- [ ] Click **"New Query"**
- [ ] Copy entire contents of `supabase_setup.sql`
- [ ] Paste into SQL Editor
- [ ] Click **"Run"**
- [ ] Wait for completion (~5-10 seconds)
- [ ] Verify no errors in output

**What Gets Created:**
- ✓ `user_roles` table (5 demo users)
- ✓ `customers` table (10 demo customers with Indian names)
- ✓ `activity_logs` table (audit trail)
- ✓ `import_logs` table (CSV import tracking)
- ✓ Indexes for performance
- ✓ Demo data ready to use

## 🌐 Launch Application (5 minutes)

### Step 4: Start Local Server
```
1. Open XAMPP Control Panel
2. Click "Start" for Apache
3. Click "Start" for MySQL (optional for this project)
```

### Step 5: Access Application
- [ ] Open web browser
- [ ] Navigate to: `http://localhost/customer-management/login.php`
- [ ] You should see professional login page with light theme

### Step 6: Test Login
Login with **any of these demo accounts**:

| Email | Password | Role |
|-------|----------|------|
| राज@example.com | Demo@123 | Admin |
| priya@example.com | Demo@123 | Manager |
| amit@example.com | Demo@123 | Staff |

- [ ] Try logging in with **राज@example.com**
- [ ] Verify dashboard displays
- [ ] Check theme toggle (top right) - switch to dark mode
- [ ] Verify theme persists on refresh

## ✅ Feature Testing (10 minutes)

### Customer Management
- [ ] [ ] View customer list
- [ ] [ ] Search for customer (try "प्रिया")
- [ ] [ ] Filter by City dropdown
- [ ] [ ] Filter by Country dropdown
- [ ] [ ] View customer details
- [ ] [ ] Verify permissions: Staff user should have limited access

### User Interface
- [ ] [ ] Light theme works and looks professional
- [ ] [ ] Dark theme works and looks professional
- [ ] [ ] Theme preference persists after logout/login
- [ ] [ ] Responsive design (zoom browser to test)
- [ ] [ ] All buttons and links work

### API Functionality
- [ ] [ ] View live stats on dashboard
- [ ] [ ] Recent activity shows (if available)
- [ ] [ ] CSV export works (on customer list)
- [ ] [ ] Search autocomplete functioning

## 📦 CSV Operations (Optional)

### Export Customers to CSV
```bash
cd c:\xampp\htdocs\customer-management-complete\customer-management
python3 utils.py export --file customers_backup.csv
```

### Import Customers from CSV
```bash
python3 utils.py import --file sample_import.csv --user-id admin_user
```

## 🔐 Security Checklist

- [ ] All credentials in `config.php` are from YOUR Supabase account
- [ ] Service Role Key is **NEVER** shared publicly
- [ ] HTTPOnly cookies enabled (automatic in config.php)
- [ ] Input validation active on all forms
- [ ] Role-based permissions working (test with different users)
- [ ] Activity logging functional (check Supabase)

## 🎨 Customization Options

### Change Branding
Edit `login.php` - line with "CM" logo to your company name

### Change Color Scheme
Edit `assets/css/modern-theme.css`:
- Search for: `--accent-color: #e53935;`
- Replace `#e53935` with your brand color

### Add New Users
- Login as सराज@example.com (Admin)
- Go to User Management page
- Add new user with email and role

### Modify Customer Fields
Edit `supabase_setup.sql`:
- Find `CREATE TABLE customers`
- Add/remove columns as needed
- Run updated SQL in Supabase

## 🚢 Production Deployment

**When deploying to production server:**

1. [ ] Update `includes/config.php` with production Supabase URL
2. [ ] Run `supabase_setup.sql` on production database
3. [ ] Verify SSL/HTTPS is enabled
4. [ ] Set `error_reporting(0);` in config.php
5. [ ] Configure automated backups in Supabase
6. [ ] Test all features on production
7. [ ] Monitor activity logs regularly

**Production Settings in config.php:**
```php
// Change from development to production
if ($_ENV === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
}
```

## 🆘 Troubleshooting

### "Connection refused" Error
- [ ] Verify Supabase URL in config.php is correct
- [ ] Verify Supabase project is running
- [ ] Check your Anon Key is valid
- [ ] Check firewall isn't blocking requests

### "Table not found" Error
- [ ] Verify supabase_setup.sql was executed completely
- [ ] Check Supabase SQL Editor for any error messages
- [ ] Try running supabase_setup.sql again

### Theme Not Persisting
- [ ] Clear browser localStorage: F12 → Application → localStorage
- [ ] Verify modern-theme.css is loaded (F12 → Network tab)
- [ ] Check browser privacy settings allow localStorage

### Login Not Working
- [ ] Verify demo user exists in Supabase (check user_roles table)
- [ ] Verify password is exactly: `Demo@123`
- [ ] Check browser console (F12) for JavaScript errors
- [ ] Verify config.php credentials are correct

### CSV Import Failing
- [ ] Verify Python 3 is installed: `python3 --version`
- [ ] Verify CSV format matches: name, email, mobile_number, etc.
- [ ] Check error messages in import_logs table (Supabase)

## 📚 Documentation References

- **Complete Guide**: See `README.md`
- **Feature Details**: See `IMPLEMENTATION_GUIDE.md`
- **Setup Steps**: This file (DEPLOYMENT_CHECKLIST.md)
- **Quick Start**: See `SETUP.sh` (Linux/Mac) or run commands manually on Windows

## ✨ System Features

**Implemented & Ready:**
- ✅ User authentication with 3 demo users
- ✅ Role-based access control (Admin, Manager, Staff)
- ✅ Customer database with 10 demo records
- ✅ Light/Dark theme toggle with persistence
- ✅ Professional, responsive UI design
- ✅ CSV import/export via Python utilities
- ✅ Activity logging and audit trails
- ✅ Search and filter functionality
- ✅ Mobile-friendly responsive design
- ✅ Indian names throughout (राज, प्रिया, अमित, etc.)

**Ready to Use Features:**
- 📊 Dashboard with stats
- 👥 Customer list with search/filter
- 📋 Customer details view
- 📥 CSV import interface
- 👤 User management (admin only)
- 📈 Activity logs viewer
- 🎨 Theme toggle (light/dark)

## 🎉 Success Indicators

**You'll know it's working when:**
1. Login page loads with "CM" logo
2. Demo login accepted with Supabase user
3. Dashboard shows "Welcome Back, [Name]"
4. Theme toggle works (click top-right)
5. Customer list displays 10 demo customers
6. Search finds "प्रिया" or other names
7. Activity shows recent actions in logs
8. CSV export generates file
9. No browser console errors (F12)
10. All pages load with professional styling

## 📞 Support

**If you encounter issues:**
1. Check browser console (F12 → Console tab)
2. Review Supabase dashboard for errors
3. Verify all credentials in config.php
4. Check file permissions (config.php must be readable)
5. Look at Supabase activity logs
6. Review README.md for detailed troubleshooting

---

**Once this checklist is complete, your Customer Management System is PRODUCTION READY! 🚀**

**Next Steps:**
- Customize branding and colors
- Add your own customer data via CSV
- Set up automated backups
- Train team on system usage
- Deploy to production server

---

*Customer Management System v1.0*
*Built with: PHP | Supabase | HTML5 | CSS3 | JavaScript*
*Last Updated: 2024*
