#!/usr/bin/env python3
"""
CWFMS Isolated Local Test Database Service (Mock PostgREST Engine)
Provides an in-process HTTP server implementing Supabase's PostgREST protocol
(/rest/v1/*) backed by a local SQLite database for safe, offline, authenticated testing.
"""

import os
import sys
import json
import sqlite3
import uuid
import datetime
import urllib.parse
from http.server import HTTPServer, BaseHTTPRequestHandler
import threading

DB_FILE = os.path.abspath(os.path.join(os.path.dirname(__file__), "test_cwfms.db"))
PORT = 8099

# Standard bcrypt hash for 'Password123!' compatible with PHP password_verify()
BCRYPT_HASH = "$2y$10$qrdR9V7cYDw.XcC7lODkUO.RLSNwx/D1kLZpsZAHAZoWA4H.aMDLW"

BOOLEAN_COLUMNS = {
    'customers': {'is_active', 'is_deleted'},
    'user_roles': {'is_active'},
    'leads': {'is_deleted', 'send_whatsapp_notification'},
}

def get_db_connection():
    conn = sqlite3.connect(DB_FILE, timeout=10)
    conn.row_factory = sqlite3.Row
    return conn

def init_db():
    if os.path.exists(DB_FILE):
        try:
            os.remove(DB_FILE)
        except Exception:
            pass

    conn = get_db_connection()
    c = conn.cursor()

    # 1. user_roles
    c.execute("""
    CREATE TABLE IF NOT EXISTS user_roles (
        id TEXT PRIMARY KEY,
        user_email TEXT UNIQUE,
        user_name TEXT,
        role TEXT,
        is_active INTEGER DEFAULT 1,
        password_hash TEXT,
        created_at TEXT
    )
    """)

    # 2. customers
    c.execute("""
    CREATE TABLE IF NOT EXISTS customers (
        id TEXT PRIMARY KEY,
        name TEXT,
        team_academy_name TEXT,
        email TEXT,
        country_code TEXT DEFAULT '+91',
        mobile_number TEXT,
        address_line_1 TEXT,
        address_line_2 TEXT,
        city TEXT,
        state TEXT,
        pincode TEXT,
        country TEXT,
        is_active INTEGER DEFAULT 1,
        profile_score INTEGER DEFAULT 0,
        created_at TEXT,
        updated_at TEXT,
        created_by TEXT,
        is_deleted INTEGER DEFAULT 0,
        UNIQUE(mobile_number, country_code)
    )
    """)

    # 3. customer_activity_log / activity_logs
    c.execute("""
    CREATE TABLE IF NOT EXISTS customer_activity_log (
        id TEXT PRIMARY KEY,
        customer_id TEXT,
        action TEXT,
        changed_fields TEXT,
        performed_by TEXT,
        performed_at TEXT,
        notes TEXT
    )
    """)

    c.execute("""
    CREATE TABLE IF NOT EXISTS activity_logs (
        id TEXT PRIMARY KEY,
        customer_id TEXT,
        action TEXT,
        description TEXT,
        user_id TEXT,
        created_at TEXT
    )
    """)

    # 4. leads
    c.execute("""
    CREATE TABLE IF NOT EXISTS leads (
        id TEXT PRIMARY KEY,
        customer_id TEXT,
        lead_source TEXT,
        enquiry_date TEXT,
        products_interested TEXT,
        sport TEXT,
        design_source TEXT,
        submission_id TEXT,
        design_link TEXT,
        custom_design_file TEXT,
        player_roster_received TEXT,
        player_list_file TEXT,
        notes TEXT,
        send_whatsapp_notification INTEGER DEFAULT 0,
        whatsapp_status TEXT DEFAULT 'not_requested',
        assigned_to TEXT,
        priority TEXT DEFAULT 'medium',
        lead_status TEXT DEFAULT 'Received',
        is_deleted INTEGER DEFAULT 0,
        created_at TEXT,
        updated_at TEXT
    )
    """)

    # 5. lead_activity_log
    c.execute("""
    CREATE TABLE IF NOT EXISTS lead_activity_log (
        id TEXT PRIMARY KEY,
        lead_id TEXT,
        action TEXT,
        details TEXT,
        old_status TEXT,
        new_status TEXT,
        changed_by TEXT,
        created_at TEXT
    )
    """)

    # 6. lead_whatsapp_messages
    c.execute("""
    CREATE TABLE IF NOT EXISTS lead_whatsapp_messages (
        id TEXT PRIMARY KEY,
        lead_id TEXT,
        customer_id TEXT,
        recipient_phone TEXT,
        template_name TEXT,
        template_language TEXT DEFAULT 'en',
        parameters TEXT,
        provider_message_id TEXT,
        status TEXT DEFAULT 'requested',
        error_message TEXT,
        trigger_type TEXT DEFAULT 'manual',
        sent_by TEXT,
        created_at TEXT,
        updated_at TEXT
    )
    """)

    # Seed Default Users
    now = datetime.datetime.now(datetime.timezone.utc).isoformat()
    users = [
        ("00000000-0000-0000-0000-000000000001", "admin@irasportswear.com", "System Admin", "admin", 1, BCRYPT_HASH, now),
        ("00000000-0000-0000-0000-000000000002", "manager@irasportswear.com", "Sales Manager", "manager", 1, BCRYPT_HASH, now),
        ("00000000-0000-0000-0000-000000000003", "priya@irasportswear.com", "Sales Exec Priya", "staff", 1, BCRYPT_HASH, now),
        ("00000000-0000-0000-0000-000000000004", "rahul@irasportswear.com", "Sales Exec Rahul", "staff", 1, BCRYPT_HASH, now),
        ("00000000-0000-0000-0000-000000000005", "inactive@irasportswear.com", "Inactive User", "staff", 0, BCRYPT_HASH, now)
    ]
    c.executemany("INSERT INTO user_roles VALUES (?, ?, ?, ?, ?, ?, ?)", users)

    # Seed Sample Customers
    customers = [
        ("cust-0000-0000-0000-000000000001", "Rajesh Sharma", "Apex Cricket Academy", "rajesh@apexcricket.in", "+91", "9876543210", "12 Stadium Road", "", "Bengaluru", "Karnataka", "560001", "India", 1, 95, now, now, "00000000-0000-0000-0000-000000000001", 0),
        ("cust-0000-0000-0000-000000000002", "Anand Verma", "Strikers FC Club", "anand@strikers.in", "+91", "9812345678", "45 Marine Drive", "", "Mumbai", "Maharashtra", "400020", "India", 1, 90, now, now, "00000000-0000-0000-0000-000000000001", 0),
        ("cust-0000-0000-0000-000000000003", "Vijay Kumar", "Spartan Sports Academy", "vijay@spartans.in", "+91", "9123456780", "88 MG Road", "", "Chennai", "Tamil Nadu", "600001", "India", 0, 75, now, now, "00000000-0000-0000-0000-000000000001", 0)
    ]
    c.executemany("INSERT INTO customers VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", customers)

    conn.commit()
    conn.close()

class PostgrestHandler(BaseHTTPRequestHandler):
    def log_message(self, format, *args):
        # Silence default request logging to keep output clean
        pass

    def send_json(self, status_code, data, extra_headers=None):
        body = json.dumps(data).encode('utf-8')
        self.send_response(status_code)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Content-Length', str(len(body)))
        if extra_headers:
            for k, v in extra_headers.items():
                self.send_header(k, v)
        self.end_headers()
        self.wfile.write(body)

    def parse_path(self):
        parsed = urllib.parse.urlparse(self.path)
        path_parts = parsed.path.strip('/').split('/')
        # Expecting rest/v1/<table_name>
        table = path_parts[2] if len(path_parts) >= 3 and path_parts[0] == 'rest' and path_parts[1] == 'v1' else ''
        query_params = urllib.parse.parse_qs(parsed.query, keep_blank_values=True)
        return table, query_params

    def parse_filter_val(self, val_str):
        if val_str == 'eq.true':
            return '=', 1
        if val_str == 'eq.false':
            return '=', 0
        if val_str.startswith('eq.'):
            return '=', val_str[3:]
        if val_str.startswith('neq.'):
            return '!=', val_str[4:]
        if val_str.startswith('gte.'):
            return '>=', val_str[4:]
        if val_str.startswith('lte.'):
            return '<=', val_str[4:]
        if val_str.startswith('like.'):
            return 'LIKE', val_str[5:]
        if val_str.startswith('ilike.'):
            return 'LIKE', val_str[6:]
        if val_str.startswith('in.(') and val_str.endswith(')'):
            items = [x.strip() for x in val_str[4:-1].split(',')]
            return 'IN', items
        return '=', val_str

    def build_where_clause(self, table, query_params):
        clauses = []
        args = []
        for k, v_list in query_params.items():
            if k in ['select', 'order', 'limit', 'offset']:
                continue
            for v_str in v_list:
                if k == 'and' and v_str.startswith('(') and v_str.endswith(')'):
                    # composite condition e.g. (enquiry_date.gte.2026-11-01,enquiry_date.lte.2026-11-30)
                    inner_conds = v_str[1:-1].split(',')
                    for cond in inner_conds:
                        parts = cond.split('.', 2)
                        if len(parts) >= 3:
                            ck, op, cval = parts[0], parts[1], parts[2]
                            sql_op = '>=' if op == 'gte' else ('<=' if op == 'lte' else '=')
                            clauses.append(f"{ck} {sql_op} ?")
                            args.append(cval)
                    continue

                op, val = self.parse_filter_val(v_str)
                if op == 'IN':
                    placeholders = ', '.join(['?'] * len(val))
                    clauses.append(f"{k} IN ({placeholders})")
                    args.extend(val)
                else:
                    clauses.append(f"{k} {op} ?")
                    args.append(val)

        where_sql = (" WHERE " + " AND ".join(clauses)) if clauses else ""
        return where_sql, args

    def format_row(self, table, row_dict):
        bool_cols = BOOLEAN_COLUMNS.get(table, set())
        formatted = {}
        for k, v in row_dict.items():
            if k in bool_cols:
                formatted[k] = bool(v)
            else:
                formatted[k] = v
        return formatted

    def do_GET(self):
        table, query_params = self.parse_path()
        if not table:
            self.send_json(404, {"error": "Endpoint not found"})
            return

        conn = get_db_connection()
        c = conn.cursor()

        # Check table
        c.execute("SELECT name FROM sqlite_master WHERE type='table' AND name=?", (table,))
        if not c.fetchone():
            conn.close()
            self.send_json(404, {"message": f"Table '{table}' not found", "code": "42P01"})
            return

        where_sql, args = self.build_where_clause(table, query_params)

        # Count if requested via Prefer header
        prefer_header = self.headers.get('Prefer', '')
        extra_headers = {}
        if 'count=exact' in prefer_header:
            c.execute(f"SELECT COUNT(*) FROM {table}{where_sql}", args)
            total_count = c.fetchone()[0]
            extra_headers['Content-Range'] = f"0-0/{total_count}"

        # Projections
        select_cols = query_params.get('select', ['*'])[0]
        if select_cols == '*':
            col_sql = "*"
        else:
            cols = [col.strip() for col in select_cols.split(',')]
            col_sql = ", ".join(cols)

        # Order & Limit
        order_by = query_params.get('order', [''])[0]
        order_sql = ""
        if order_by:
            parts = order_by.split('.')
            col = parts[0]
            direction = "DESC" if len(parts) > 1 and parts[1].lower() == 'desc' else "ASC"
            order_sql = f" ORDER BY {col} {direction}"

        limit_val = query_params.get('limit', [''])[0]
        limit_sql = f" LIMIT {int(limit_val)}" if limit_val.isdigit() else ""

        query = f"SELECT {col_sql} FROM {table}{where_sql}{order_sql}{limit_sql}"
        try:
            c.execute(query, args)
            rows = c.fetchall()
            data = [self.format_row(table, dict(row)) for row in rows]
            self.send_json(200, data, extra_headers)
        except Exception as e:
            self.send_json(500, {"error": str(e), "query": query})
        finally:
            conn.close()

    def do_POST(self):
        table, _ = self.parse_path()
        if not table:
            self.send_json(404, {"error": "Endpoint not found"})
            return

        content_len = int(self.headers.get('Content-Length', 0))
        raw_body = self.rfile.read(content_len).decode('utf-8')
        try:
            payload = json.loads(raw_body)
        except Exception:
            payload = {}

        records = payload if isinstance(payload, list) else [payload]
        conn = get_db_connection()
        c = conn.cursor()

        inserted_records = []
        now = datetime.datetime.now(datetime.timezone.utc).isoformat()
        bool_cols = BOOLEAN_COLUMNS.get(table, set())

        try:
            for rec in records:
                if 'id' not in rec or not rec['id']:
                    rec['id'] = str(uuid.uuid4())
                if 'created_at' not in rec:
                    rec['created_at'] = now
                if 'updated_at' not in rec and table in ['customers', 'leads', 'lead_whatsapp_messages']:
                    rec['updated_at'] = now

                # Transform boolean to integer for sqlite
                clean_rec = {}
                for k, v in rec.items():
                    if k in bool_cols:
                        clean_rec[k] = 1 if v else 0
                    elif isinstance(v, (dict, list)):
                        clean_rec[k] = json.dumps(v)
                    else:
                        clean_rec[k] = v

                cols = list(clean_rec.keys())
                placeholders = ', '.join(['?'] * len(cols))
                col_names = ', '.join(cols)
                c.execute(f"INSERT INTO {table} ({col_names}) VALUES ({placeholders})", list(clean_rec.values()))

                # Fetch back inserted record
                c.execute(f"SELECT * FROM {table} WHERE id=?", (rec['id'],))
                inserted_records.append(self.format_row(table, dict(c.fetchone())))

            conn.commit()
            self.send_json(201, inserted_records)
        except sqlite3.IntegrityError as ie:
            conn.rollback()
            self.send_json(409, {"message": str(ie), "code": "23505", "hint": "Duplicate record exists"})
        except Exception as e:
            conn.rollback()
            self.send_json(500, {"error": str(e)})
        finally:
            conn.close()

    def do_PATCH(self):
        table, query_params = self.parse_path()
        if not table:
            self.send_json(404, {"error": "Endpoint not found"})
            return

        content_len = int(self.headers.get('Content-Length', 0))
        raw_body = self.rfile.read(content_len).decode('utf-8')
        try:
            payload = json.loads(raw_body)
        except Exception:
            payload = {}

        conn = get_db_connection()
        c = conn.cursor()

        where_sql, args = self.build_where_clause(table, query_params)
        if not where_sql:
            self.send_json(400, {"error": "PATCH requires filter conditions"})
            conn.close()
            return

        bool_cols = BOOLEAN_COLUMNS.get(table, set())
        clean_update = {}
        for k, v in payload.items():
            if k in bool_cols:
                clean_update[k] = 1 if v else 0
            elif isinstance(v, (dict, list)):
                clean_update[k] = json.dumps(v)
            else:
                clean_update[k] = v

        set_clauses = [f"{k} = ?" for k in clean_update.keys()]
        set_sql = ", ".join(set_clauses)
        update_args = list(clean_update.values()) + args

        try:
            # First find IDs to update
            c.execute(f"SELECT id FROM {table}{where_sql}", args)
            target_ids = [r[0] for r in c.fetchall()]

            c.execute(f"UPDATE {table} SET {set_sql}{where_sql}", update_args)
            conn.commit()

            # Return updated rows
            updated_rows = []
            for tid in target_ids:
                c.execute(f"SELECT * FROM {table} WHERE id=?", (tid,))
                r = c.fetchone()
                if r:
                    updated_rows.append(self.format_row(table, dict(r)))

            self.send_json(200, updated_rows)
        except Exception as e:
            conn.rollback()
            self.send_json(500, {"error": str(e)})
        finally:
            conn.close()

    def do_DELETE(self):
        table, query_params = self.parse_path()
        if not table:
            self.send_json(404, {"error": "Endpoint not found"})
            return

        conn = get_db_connection()
        c = conn.cursor()

        where_sql, args = self.build_where_clause(table, query_params)
        try:
            c.execute(f"DELETE FROM {table}{where_sql}", args)
            conn.commit()
            self.send_json(200, {"success": True, "deleted": c.rowcount})
        except Exception as e:
            conn.rollback()
            self.send_json(500, {"error": str(e)})
        finally:
            conn.close()

def run_server():
    init_db()
    server_address = ('127.0.0.1', PORT)
    httpd = HTTPServer(server_address, PostgrestHandler)
    print(f"[Mock Supabase Engine] Listening on http://127.0.0.1:{PORT}/rest/v1/")
    httpd.serve_forever()

if __name__ == '__main__':
    run_server()
