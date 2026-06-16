#!/usr/bin/env python3
"""
Customer Management Module - Python Utilities
Studio 3 Interactive | Order Management Application

Usage:
  python3 utils.py import --file customers.csv
  python3 utils.py export --format csv
  python3 utils.py export --format json
  python3 utils.py stats
  python3 utils.py validate --file customers.csv
"""

import os
import sys
import csv
import json
import re
import argparse
import requests
from datetime import datetime

# ============================================================
# CONFIGURATION
# ============================================================
SUPABASE_URL      = os.environ.get('SUPABASE_URL',      'https://YOUR_PROJECT_ID.supabase.co')
SUPABASE_KEY      = os.environ.get('SUPABASE_SERVICE_KEY', 'YOUR_SUPABASE_SERVICE_ROLE_KEY')
API_BASE_URL      = os.environ.get('APP_URL',           'http://localhost/customer-management')

HEADERS = {
    'Content-Type':  'application/json',
    'apikey':        SUPABASE_KEY,
    'Authorization': f'Bearer {SUPABASE_KEY}',
    'Prefer':        'return=representation',
}

REQUIRED_COLUMNS = [
    'name', 'team_academy_name', 'country_code',
    'mobile_number', 'address_line_1', 'city', 'state', 'pincode', 'country'
]

# ============================================================
# SUPABASE CLIENT
# ============================================================
class SupabaseClient:
    def __init__(self):
        self.base = f"{SUPABASE_URL}/rest/v1"

    def select(self, table, params=None):
        url = f"{self.base}/{table}"
        r = requests.get(url, headers=HEADERS, params=params or {})
        r.raise_for_status()
        return r.json()

    def insert(self, table, data):
        url = f"{self.base}/{table}"
        r = requests.post(url, headers=HEADERS, json=data)
        r.raise_for_status()
        return r.json()

    def update(self, table, data, match):
        url = f"{self.base}/{table}"
        r = requests.patch(url, headers=HEADERS, json=data, params=match)
        r.raise_for_status()
        return r.json()

db = SupabaseClient()

# ============================================================
# VALIDATION
# ============================================================
def validate_customer(row, row_num):
    errors = []

    # Required fields
    for col in REQUIRED_COLUMNS:
        if not row.get(col, '').strip():
            errors.append(f"Row {row_num}: '{col}' is required")

    # Name length
    if row.get('name', '').strip() and len(row['name'].strip()) < 3:
        errors.append(f"Row {row_num}: Name must be at least 3 characters")

    # Email format
    email = row.get('email', '').strip()
    if email and not re.match(r'^[^@\s]+@[^@\s]+\.[^@\s]+$', email):
        errors.append(f"Row {row_num}: Invalid email format '{email}'")

    # Mobile number
    mobile = row.get('mobile_number', '').strip()
    if mobile and not re.match(r'^[0-9]{7,15}$', mobile):
        errors.append(f"Row {row_num}: Invalid mobile number '{mobile}' (must be 7-15 digits)")

    # Pincode
    pincode = row.get('pincode', '').strip()
    if pincode and not re.match(r'^[0-9]+$', pincode):
        errors.append(f"Row {row_num}: Pincode must be numeric, got '{pincode}'")

    return errors

# ============================================================
# IMPORT FROM CSV
# ============================================================
def import_csv(filepath, dry_run=False):
    print(f"\n{'[DRY RUN] ' if dry_run else ''}Importing from: {filepath}")
    print("=" * 60)

    if not os.path.exists(filepath):
        print(f"ERROR: File not found: {filepath}")
        sys.exit(1)

    all_errors = []
    valid_rows = []

    with open(filepath, newline='', encoding='utf-8-sig') as f:
        reader = csv.DictReader(f)

        # Check required columns
        missing = [c for c in REQUIRED_COLUMNS if c not in reader.fieldnames]
        if missing:
            print(f"ERROR: Missing required columns: {', '.join(missing)}")
            print(f"Required: {', '.join(REQUIRED_COLUMNS)}")
            sys.exit(1)

        for i, row in enumerate(reader, start=2):
            errors = validate_customer(row, i)
            if errors:
                all_errors.extend(errors)
            else:
                valid_rows.append({
                    'name':              row['name'].strip(),
                    'team_academy_name': row['team_academy_name'].strip(),
                    'email':             row.get('email', '').strip() or None,
                    'country_code':      row['country_code'].strip(),
                    'mobile_number':     row['mobile_number'].strip(),
                    'address_line_1':    row['address_line_1'].strip(),
                    'address_line_2':    row.get('address_line_2', '').strip() or None,
                    'city':              row['city'].strip(),
                    'state':             row['state'].strip(),
                    'pincode':           row['pincode'].strip(),
                    'country':           row['country'].strip(),
                })

    print(f"Total rows:   {i - 1}")
    print(f"Valid:        {len(valid_rows)}")
    print(f"Invalid:      {len(all_errors)}")

    if all_errors:
        print("\nValidation Errors:")
        for e in all_errors[:20]:
            print(f"  ✗ {e}")
        if len(all_errors) > 20:
            print(f"  ... and {len(all_errors) - 20} more errors")

    if not valid_rows:
        print("\nNo valid rows to import.")
        return

    if dry_run:
        print("\n[DRY RUN] No data was written. Remove --dry-run to import.")
        return

    # Batch insert in chunks of 50
    success_count = 0
    fail_count    = 0
    chunk_size    = 50

    for i in range(0, len(valid_rows), chunk_size):
        chunk = valid_rows[i:i + chunk_size]
        try:
            result = db.insert('customers', chunk)
            success_count += len(chunk)
            print(f"  ✓ Inserted rows {i+1}–{i+len(chunk)}")
        except requests.HTTPError as e:
            fail_count += len(chunk)
            print(f"  ✗ Failed rows {i+1}–{i+len(chunk)}: {e.response.text}")

    print(f"\nImport complete: {success_count} inserted, {fail_count} failed")

# ============================================================
# EXPORT
# ============================================================
def export_data(fmt='csv', output_dir='.'):
    print(f"\nExporting all customers as {fmt.upper()}...")
    print("=" * 60)

    try:
        customers = db.select('customers', {'is_deleted': 'eq.false', 'order': 'created_at.desc'})
    except Exception as e:
        print(f"ERROR: Failed to fetch customers: {e}")
        sys.exit(1)

    print(f"Fetched {len(customers)} customers")

    timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
    filename  = os.path.join(output_dir, f"customers_export_{timestamp}.{fmt}")

    if fmt == 'csv':
        columns = [
            'id', 'name', 'team_academy_name', 'email', 'country_code',
            'mobile_number', 'address_line_1', 'address_line_2',
            'city', 'state', 'pincode', 'country', 'created_at', 'updated_at'
        ]
        with open(filename, 'w', newline='', encoding='utf-8') as f:
            writer = csv.DictWriter(f, fieldnames=columns, extrasaction='ignore')
            writer.writeheader()
            writer.writerows(customers)

    elif fmt == 'json':
        with open(filename, 'w', encoding='utf-8') as f:
            json.dump({
                'exported_at': datetime.now().isoformat(),
                'total':       len(customers),
                'customers':   customers
            }, f, indent=2, default=str)

    print(f"Export saved to: {filename}")
    return filename

# ============================================================
# STATS
# ============================================================
def show_stats():
    print("\nCustomer Statistics")
    print("=" * 60)

    try:
        customers = db.select('customers', {'is_deleted': 'eq.false', 'select': '*'})
    except Exception as e:
        print(f"ERROR: {e}")
        sys.exit(1)

    total = len(customers)
    print(f"Total customers: {total}")

    if not total:
        print("No customers found.")
        return

    # By country
    countries = {}
    for c in customers:
        country = c.get('country', 'Unknown')
        countries[country] = countries.get(country, 0) + 1

    print("\nBy Country:")
    for country, count in sorted(countries.items(), key=lambda x: -x[1]):
        bar = '█' * min(count, 30)
        print(f"  {country:<20} {bar} {count}")

    # By state (top 10)
    states = {}
    for c in customers:
        state = c.get('state', 'Unknown')
        states[state] = states.get(state, 0) + 1

    print("\nTop States:")
    for state, count in sorted(states.items(), key=lambda x: -x[1])[:10]:
        bar = '█' * min(count, 30)
        print(f"  {state:<20} {bar} {count}")

    # Recent additions (last 7 days)
    from datetime import timedelta, timezone
    cutoff = (datetime.now(timezone.utc) - timedelta(days=7)).isoformat()
    recent = [c for c in customers if c.get('created_at', '') >= cutoff]
    print(f"\nAdded in last 7 days: {len(recent)}")

    # Email coverage
    with_email = sum(1 for c in customers if c.get('email'))
    print(f"With email address:   {with_email} ({with_email/total*100:.1f}%)")

# ============================================================
# VALIDATE ONLY
# ============================================================
def validate_file(filepath):
    print(f"\nValidating: {filepath}")
    print("=" * 60)

    if not os.path.exists(filepath):
        print(f"ERROR: File not found: {filepath}")
        sys.exit(1)

    all_errors = []
    total = 0

    with open(filepath, newline='', encoding='utf-8-sig') as f:
        reader = csv.DictReader(f)
        for i, row in enumerate(reader, start=2):
            total += 1
            errors = validate_customer(row, i)
            all_errors.extend(errors)

    print(f"Rows checked: {total}")
    print(f"Errors found: {len(all_errors)}")

    if all_errors:
        print("\nValidation Errors:")
        for e in all_errors:
            print(f"  ✗ {e}")
    else:
        print("\n✓ All rows are valid!")

# ============================================================
# MAIN
# ============================================================
def main():
    parser = argparse.ArgumentParser(
        description='Customer Management Module - Python Utilities',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  python3 utils.py import --file customers.csv
  python3 utils.py import --file customers.csv --dry-run
  python3 utils.py export --format csv
  python3 utils.py export --format json --output ./exports
  python3 utils.py stats
  python3 utils.py validate --file customers.csv
        """
    )

    subparsers = parser.add_subparsers(dest='command', help='Command to run')

    # Import command
    p_import = subparsers.add_parser('import', help='Import customers from CSV')
    p_import.add_argument('--file', required=True, help='Path to CSV file')
    p_import.add_argument('--dry-run', action='store_true', help='Validate only, do not insert')

    # Export command
    p_export = subparsers.add_parser('export', help='Export customers to CSV or JSON')
    p_export.add_argument('--format', choices=['csv', 'json'], default='csv', help='Output format')
    p_export.add_argument('--output', default='.', help='Output directory')

    # Stats command
    subparsers.add_parser('stats', help='Show customer statistics')

    # Validate command
    p_validate = subparsers.add_parser('validate', help='Validate a CSV file without importing')
    p_validate.add_argument('--file', required=True, help='Path to CSV file')

    args = parser.parse_args()

    if not args.command:
        parser.print_help()
        sys.exit(0)

    print("=" * 60)
    print("  Customer Management Module - Studio 3 Interactive")
    print("=" * 60)

    if args.command == 'import':
        import_csv(args.file, dry_run=args.dry_run)
    elif args.command == 'export':
        export_data(fmt=args.format, output_dir=args.output)
    elif args.command == 'stats':
        show_stats()
    elif args.command == 'validate':
        validate_file(args.file)

if __name__ == '__main__':
    main()
