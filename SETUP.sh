#!/bin/bash
# Customer Management System - Complete Setup Checklist
# Follow these steps in order to get your system running

echo "=========================================="
echo "Customer Management System - Setup Guide"
echo "=========================================="
echo ""

# COLOR CODES
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Step 1: Verify Requirements
echo -e "${YELLOW}STEP 1: Verify Requirements${NC}"
echo "Required:"
echo "  ✓ PHP 7.4 or higher"
echo "  ✓ XAMPP/Local server running"
echo "  ✓ Supabase account (FREE at supabase.com)"
echo "  ✓ Modern web browser"
echo ""
echo "System Status:"
php -v | head -1
echo ""

# Step 2: Project Location
echo -e "${YELLOW}STEP 2: Project Location${NC}"
echo "Place project in: /xampp/htdocs/customer-management/"
echo "Current location:"
pwd
echo ""

# Step 3: Supabase Setup
echo -e "${YELLOW}STEP 3: Supabase Configuration${NC}"
echo "Steps:"
echo "  1. Go to https://supabase.com"
echo "  2. Create new project (or use existing)"
echo "  3. Go to Project Settings → API"
echo "  4. Copy your credentials:"
echo "     - Project URL"
echo "     - Anon Key"
echo "     - Service Role Key"
echo ""
echo "Edit: includes/config.php"
echo "Update these lines:"
echo "  define('SUPABASE_URL', 'YOUR_PROJECT_URL');"
echo "  define('SUPABASE_ANON_KEY', 'YOUR_ANON_KEY');"
echo "  define('SUPABASE_SERVICE_KEY', 'YOUR_SERVICE_KEY');"
echo ""

# Step 4: Database Setup
echo -e "${YELLOW}STEP 4: Database Setup${NC}"
echo "Steps:"
echo "  1. Open Supabase Dashboard"
echo "  2. Go to SQL Editor"
echo "  3. Create new query"
echo "  4. Copy entire contents of: supabase_setup.sql"
echo "  5. Paste into SQL Editor"
echo "  6. Click 'Run'"
echo "  7. Wait for completion (creates tables, indexes, demo data)"
echo ""

# Step 5: Verify Files
echo -e "${YELLOW}STEP 5: Verify Project Files${NC}"
echo "Check these files exist:"
files=(
    "includes/config.php"
    "login.php"
    "logout.php"
    "index.php"
    "utils.py"
    "assets/css/modern-theme.css"
    "assets/js/app.js"
    "api/auth.php"
    "api/customer.php"
    "api/customers.php"
    "README.md"
    "IMPLEMENTATION_GUIDE.md"
    "supabase_setup.sql"
)

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓${NC} $file"
    else
        echo -e "${RED}✗${NC} $file (MISSING)"
    fi
done
echo ""

# Step 6: Start Application
echo -e "${YELLOW}STEP 6: Start Application${NC}"
echo "Steps:"
echo "  1. Start XAMPP (Apache + MySQL)"
echo "  2. Open browser"
echo "  3. Go to: http://localhost/customer-management/login.php"
echo ""

# Step 7: Demo Login
echo -e "${YELLOW}STEP 7: Login with Demo Account${NC}"
echo "Demo Credentials:"
echo "  Email: राज@example.com (Admin)"
echo "  OR: priya@example.com (Manager)"
echo "  OR: amit@example.com (Staff)"
echo "  Password: Demo@123 (same for all)"
echo ""

# Step 8: Test Features
echo -e "${YELLOW}STEP 8: Test Core Features${NC}"
echo "Test items:"
echo "  ✓ Login with each user role"
echo "  ✓ Toggle dark/light theme (top right)"
echo "  ✓ View customer list"
echo "  ✓ Search for customers"
echo "  ✓ Filter by state/country"
echo "  ✓ Verify permission restrictions"
echo ""

# Step 9: CSV Operations
echo -e "${YELLOW}STEP 9: CSV Import/Export${NC}"
echo "Export customers:"
echo "  python3 utils.py export --file customers.csv"
echo ""
echo "Import customers:"
echo "  python3 utils.py import --file customers.csv --user-id admin_id"
echo ""

# Step 10: Production Deployment
echo -e "${YELLOW}STEP 10: Production Deployment${NC}"
echo "When ready to deploy:"
echo "  1. Update includes/config.php with production credentials"
echo "  2. Run supabase_setup.sql on production database"
echo "  3. Set PHP error_reporting to 0"
echo "  4. Enable HTTPS"
echo "  5. Configure backups"
echo "  6. Test all features"
echo ""

echo -e "${GREEN}=========================================="
echo "Setup Complete! 🎉"
echo "==========================================${NC}"
echo ""
echo "Next steps:"
echo "  1. Configure SUPABASE credentials in config.php"
echo "  2. Run supabase_setup.sql to create database"
echo "  3. Open http://localhost/customer-management/login.php"
echo "  4. Login with demo account (password: Demo@123)"
echo "  5. Explore the system!"
echo ""
echo "Documentation:"
echo "  - README.md - Complete guide"
echo "  - IMPLEMENTATION_GUIDE.md - Detailed features"
echo ""
echo "Support:"
echo "  - Check error logs in browser console (F12)"
echo "  - Review Supabase dashboard for any issues"
echo "  - Verify all tables created in Supabase"
echo ""
