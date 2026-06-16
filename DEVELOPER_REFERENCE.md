# 👨‍💻 Developer Quick Reference Guide

## 🌐 API Endpoints

### Authentication API
**File:** `api/auth.php`

```php
// Login User
POST /api/auth.php?action=login
{
    "email": "राज@example.com",
    "password": "Demo@123"
}
Response: { "success": true, "user": {...} }

// Logout User
GET /api/auth.php?action=logout
Response: { "success": true, "message": "Logged out" }

// Get Current User
GET /api/auth.php?action=current
Response: { "user": {...}, "role": "Admin" }
```

### Customer API - Single Record
**File:** `api/customer.php`

```php
// Get Single Customer
GET /api/customer.php?action=get&id=123
Response: { "customer": {...} }

// Create Customer
POST /api/customer.php?action=create
{
    "name": "राज कुमार",
    "email": "raj@example.com",
    "mobile_number": "9876543210",
    "city": "नई दिल्ली",
    "address_line_1": "123 Main Street"
}
Response: { "success": true, "customer": {...} }

// Update Customer
PATCH /api/customer.php?action=update&id=123
{
    "name": "राज कुमार Singh",
    "city": "मुंबई"
}
Response: { "success": true, "customer": {...} }

// Delete Customer (Soft Delete)
DELETE /api/customer.php?action=delete&id=123
Response: { "success": true, "message": "Customer deleted" }
```

### Customers API - List & Search
**File:** `api/customers.php`

```php
// List All Customers
GET /api/customers.php
Response: { "customers": [...], "total": 10 }

// Search Customers
GET /api/customers.php?search=प्रिया
Response: { "customers": [...], "total": 1 }

// Filter by City
GET /api/customers.php?city=नई%20दिल्ली
Response: { "customers": [...], "total": 3 }

// Pagination
GET /api/customers.php?page=1&limit=10
Response: { "customers": [...], "total": 10, "pages": 1 }

// Combined Filter
GET /api/customers.php?city=मुंबई&state=महाराष्ट्र&limit=20
```

### Statistics API
**File:** `api/stats.php`

```php
// Get Dashboard Stats
GET /api/stats.php
Response: {
    "total_customers": 10,
    "customers_this_month": 2,
    "recent_activity": [...],
    "users_online": 3
}

// Get Sales Stats
GET /api/stats.php?range=month
Response: { "stats": {...}, "chart_data": [...] }
```

### Activity Log API
**File:** `api/activity.php`

```php
// Get Activity Logs
GET /api/activity.php
Response: { 
    "logs": [
        { "user": "राज", "customer": "प्रिया", "action": "viewed", "timestamp": "..." },
        ...
    ]
}

// Get Customer Activity
GET /api/activity.php?customer_id=123
Response: { "logs": [...] }
```

---

## 🛠️ Helper Functions (from config.php)

### Sanitization & Validation

```php
// Sanitize input to prevent XSS
$safe_text = sanitize($_POST['name']);

// Validate email
if (validateEmail('user@example.com')) { ... }

// Validate phone number
if (validatePhone('+919876543210')) { ... }

// Validate Indian pincode (6 digits)
if (validatePincode('110001')) { ... }

// Validate address
if (validateAddress('123 Main Street')) { ... }
```

### User & Permission Functions

```php
// Get current logged-in user
$user = getCurrentUser();
echo $user['name'];  // राज

// Check if user has permission
if (hasPermission('customer.edit')) { ... }

// Get user initials for avatar
$initials = getInitials('राज कुमार');  // RK
```

### Activity Logging

```php
// Log customer action
logActivity(
    $customer_id,
    'viewed_profile',
    'Customer profile viewed',
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
);
```

### Formatting Functions

```php
// Format date
echo formatDate('2024-01-15');  // Jan 15, 2024

// Format datetime
echo formatDateTime('2024-01-15 14:30:00');  // Jan 15, 2024 2:30 PM

// Format currency
echo formatCurrency(1500);  // $1,500.00

// Get user initials
echo getInitials('राज कुमार');  // RK

// Generate unique ID
$uuid = generateId();  // a1b2c3d4-e5f6-7g8h-9i0j-k1l2m3n4o5p6
```

### Supabase Database Operations

```php
// Create instance
$db = new Supabase();

// Get single record
$customer = $db->select('customers', ['id' => 123]);

// Get all records
$customers = $db->select('customers');

// Get with WHERE clause
$customers = $db->select('customers', ['city' => 'नई दिल्ली']);

// Insert record
$result = $db->insert('customers', [
    'name' => 'अमित पटेल',
    'email' => 'amit@example.com',
    'city' => 'बेंगलुरु'
]);

// Update record
$result = $db->update('customers', 123, [
    'name' => 'अमित कुमार पटेल'
]);

// Delete record
$result = $db->delete('customers', 123);
```

---

## 📝 JavaScript Functions (from app.js)

### Loading Data

```javascript
// Load customer list
loadCustomers();

// Load statistics
loadStats();

// Load activity logs
loadActivityLogs();

// Search customers
searchCustomers('प्रिया');

// Apply filters
applyFilters({
    city: 'नई दिल्ली',
    state: 'दिल्ली'
});
```

### Forms & Events

```javascript
// Submit customer form
document.getElementById('customerForm').addEventListener('submit', (e) => {
    e.preventDefault();
    handleFormSubmit(e);
});

// Handle form submission
function handleFormSubmit(e) {
    const formData = new FormData(e.target);
    // Send to API
}

// Bind all events
bindEvents();
```

### Export & Import

```javascript
// Export customers to CSV
exportCSV();

// Import from CSV
document.getElementById('csvFile').addEventListener('change', handleCSVImport);
```

### Theme Toggle

```javascript
// Toggle theme
toggleTheme();

// Check current theme
const currentTheme = localStorage.getItem('theme') || 'light';

// Set theme
setTheme('dark');
```

---

## 🔐 Supabase Configuration

**File:** `includes/config.php`

```php
// Set your credentials here:
define('SUPABASE_URL', 'https://your-project.supabase.co');
define('SUPABASE_ANON_KEY', 'your-anon-key');
define('SUPABASE_SERVICE_KEY', 'your-service-key');

// These settings are already configured:
SESSION_NAME = 'cm_session'
SESSION_LIFETIME = 3600 (1 hour)
COOKIE_HTTPONLY = true
```

---

## 🎨 Theme System

### CSS Variables (Light Theme)
```css
:root {
    --bg-primary: #f4f6fb;
    --bg-secondary: #ffffff;
    --text-primary: #1a1f36;
    --text-secondary: #6b7280;
    --accent-color: #e53935;
    --border-color: #e5e7eb;
    --success-color: #00897b;
    --warning-color: #ff9800;
    --danger-color: #e53935;
}
```

### CSS Variables (Dark Theme)
```css
[data-theme="dark"] {
    --bg-primary: #0f1219;
    --bg-secondary: #1a1f36;
    --text-primary: #f4f6fb;
    --text-secondary: #9ca3af;
    --accent-color: #ff6b6b;
    --border-color: #374151;
    /* ... more variables */
}
```

### Using Variables in CSS
```css
.button {
    background-color: var(--accent-color);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
}
```

---

## 📊 Database Schema

### customers Table
```sql
-- key columns:
- id (UUID Primary Key)
- name (VARCHAR) -- Indian names
- email (VARCHAR, UNIQUE)
- mobile_number (VARCHAR)
- country_code (VARCHAR) - Default: +91
- address_line_1, address_line_2 (VARCHAR)
- city, state, pincode, country (VARCHAR)
- team_academy_name (VARCHAR) - Optional field
- created_by, created_at (timestamps)
- updated_by, updated_at (timestamps)
- deleted_by, deleted_at, is_deleted (soft delete)
```

### user_roles Table
```sql
-- key columns:
- id (UUID Primary Key)
- user_name (VARCHAR)
- user_email (VARCHAR, UNIQUE)
- role (VARCHAR) - Values: Admin, Manager, Staff
- is_active (BOOLEAN) - Default: true
- last_login (TIMESTAMP)
- created_at, updated_at (TIMESTAMP)
```

### activity_logs Table
```sql
-- key columns:
- id (UUID Primary Key)
- user_id (FK to user_roles)
- customer_id (FK to customers)
- action (VARCHAR)
- description (TEXT)
- ip_address (VARCHAR)
- user_agent (VARCHAR)
- created_at (TIMESTAMP)
```

### import_logs Table
```sql
-- key columns:
- id (UUID Primary Key)
- user_id (FK to user_roles)
- filename (VARCHAR)
- total_rows (INTEGER)
- successful_rows (INTEGER)
- failed_rows (INTEGER)
- errors (JSON)
- created_at (TIMESTAMP)
```

---

## 🐍 Python Utilities

### Usage Examples

```bash
# Initialize environment
export SUPABASE_URL="https://your-project.supabase.co"
export SUPABASE_KEY="your-key"

# Export customers to CSV
python3 utils.py export --file customers_export.csv

# Import customers from CSV
python3 utils.py import --file customers_import.csv --user-id राज

# Validate data
python3 utils.py validate --file customers.csv
```

### Python Code Examples

```python
from utils import SupabaseClient, import_customers_from_csv

# Initialize client
client = SupabaseClient(url, key)

# Get customer
customer = client.get('customers', 123)

# Create customer
new_customer = client.insert('customers', {
    'name': 'नेहा सिंह',
    'email': 'neha@example.com',
    'city': 'दिल्ली'
})

# Import from CSV
result = import_customers_from_csv('customers.csv', 'admin_user')
print(f"Imported {result['successful']} customers")
```

---

## 🔧 Common Development Tasks

### Add New API Endpoint
```php
// In api/customers.php
if ($_GET['action'] === 'export') {
    $customers = $db->select('customers');
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=customers.csv');
    // Generate CSV...
}
```

### Add New Form Field
1. Add to HTML form in page
2. Add validation function in config.php if needed
3. Add CSS styling in modern-theme.css
4. Update API endpoint to handle new field
5. Test with all user roles

### Add New User Role
```php
// In user_roles table
INSERT INTO user_roles (user_name, user_email, role)
VALUES ('विक्रम', 'vikram@example.com', 'NewRole');

// Add permission check in config.php
if ($user['role'] === 'NewRole') { ... }
```

### Add New Filter
```javascript
// In api/customers.php
if (isset($_GET['new_filter'])) {
    $query->where('field', $_GET['new_filter']);
}

// In app.js
applyFilters({
    new_filter: 'value'
});
```

---

## 🧪 Testing API Endpoints

### Using cURL (Command Line)
```bash
# Get customer list
curl -X GET "http://localhost/customer-management/api/customers.php" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Search customers
curl -X GET "http://localhost/customer-management/api/customers.php?search=प्रिया"

# Create customer
curl -X POST "http://localhost/customer-management/api/customer.php?action=create" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "संजय गुप्ता",
    "email": "sanjay@example.com",
    "city": "मुंबई"
  }'
```

### Using PHP (Local Testing)
```php
<?php
include 'includes/config.php';

// Test Supabase connection
$db = new Supabase();
$customers = $db->select('customers');
echo "Found " . count($customers) . " customers";
echo json_encode($customers[0]);
?>
```

### Using JavaScript (Browser Console)
```javascript
// Test API call
fetch('/customer-management/api/customers.php')
    .then(r => r.json())
    .then(data => console.log(data));

// Search
fetch('/customer-management/api/customers.php?search=प्रिया')
    .then(r => r.json())
    .then(data => console.log(data));
```

---

## 📈 Performance Optimization Tips

1. **Database Queries**
   - Use indexes (already set up)
   - Limit result sets with LIMIT
   - Use pagination for large datasets

2. **Front-End**
   - Lazy load images
   - Cache API responses in localStorage
   - Minify CSS/JS for production
   - Use CSS variables for theming

3. **Backend**
   - Add API rate limiting
   - Cache frequent queries
   - Optimize CSV import with batch processing
   - Add query optimization for search

4. **Database**
   - Monitor query performance
   - Regular backups
   - Archive old activity logs
   - Index heavily-searched columns

---

## 🐛 Debugging Tips

1. **Enable Error Logging**
```php
// In config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');
```

2. **Check Browser Console**
   - Press F12 while using application
   - Look at Console tab for JavaScript errors
   - Check Network tab for failed API calls

3. **Check Supabase Logs**
   - Open Supabase Dashboard
   - Check Logs section for database errors
   - Monitor Auth logs for login issues

4. **Test API Endpoints Directly**
   - Use browser URL bar
   - Use cURL command
   - Use API testing tool (Postman)

5. **Review Activity Logs**
```sql
SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 10;
```

---

## 🔐 Security Best Practices

1. **Always sanitize user input**
   ```php
   $name = sanitize($_POST['name']);
   ```

2. **Validate data format**
   ```php
   if (!validateEmail($email)) return error(...);
   ```

3. **Check permissions before operations**
   ```php
   if (!hasPermission('customer.delete')) return unauthorized();
   ```

4. **Log important actions**
   ```php
   logActivity($customer_id, 'deleted', 'Customer deleted', ...);
   ```

5. **Never log sensitive data**
   ```php
   // DON'T: logActivity(..., "Password: $password", ...);
   // DO: logActivity(..., "Password changed", ...);
   ```

---

## 💡 Common Patterns

### Try-Catch Error Handling
```php
try {
    $customer = $db->select('customers', ['id' => $id]);
    return json_encode($customer);
} catch (Exception $e) {
    http_response_code(400);
    return json_encode(['error' => $e->getMessage()]);
}
```

### Middleware Pattern for Auth
```php
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        die(json_encode(['error' => 'Unauthorized']));
    }
}

function requirePermission($perm) {
    if (!hasPermission($perm)) {
        http_response_code(403);
        die(json_encode(['error' => 'Forbidden']));
    }
}
```

### Async Pattern in JavaScript
```javascript
async function loadCustomer(id) {
    try {
        const response = await fetch(`/api/customer.php?action=get&id=${id}`);
        const data = await response.json();
        displayCustomer(data.customer);
    } catch (error) {
        showError('Failed to load customer');
    }
}
```

---

## 📱 Mobile Responsive Design

These breakpoints are used in modern-theme.css:

```css
/* Mobile: Under 480px */
@media (max-width: 480px) { ... }

/* Tablet: 480px to 768px */
@media (min-width: 481px) and (max-width: 768px) { ... }

/* Desktop: 768px and above */
@media (min-width: 769px) { ... }

/* Large Desktop: 1200px and above */
@media (min-width: 1200px) { ... }
```

---

## 🚀 Ready to Deploy

All the code is production-ready. Just:

1. Update Supabase credentials
2. Run database setup
3. Test features
4. Deploy to server
5. Monitor logs

**Happy coding! 🎉**

---

*Customer Management System v1.0 - Developer Reference*  
*Last Updated: 2024*
