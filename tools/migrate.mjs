/**
 * Run migration_v2.sql against Supabase PostgreSQL
 * Usage: SUPABASE_DB_PASSWORD=your_password node tools/migrate.mjs
 */
import { readFileSync } from 'fs';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';
import pg from 'pg';

const { Client } = pg;
const __dirname = dirname(fileURLToPath(import.meta.url));
const projectRef = 'fjahjldhflcmnargcood';
const password = process.env.SUPABASE_DB_PASSWORD || process.argv[2];

if (!password) {
    console.error('ERROR: Set SUPABASE_DB_PASSWORD env var or pass password as argument');
    process.exit(1);
}

const connectionString = `postgresql://postgres.${projectRef}:${encodeURIComponent(password)}@aws-0-ap-south-1.pooler.supabase.com:6543/postgres`;

const sql = readFileSync(join(__dirname, '..', 'migration_v2.sql'), 'utf8');

const client = new Client({ connectionString, ssl: { rejectUnauthorized: false } });

try {
    await client.connect();
    console.log('Connected to Supabase database');
    await client.query(sql);
    console.log('Migration completed successfully');
    process.exit(0);
} catch (err) {
    // Retry with direct connection if pooler fails
    if (err.message.includes('ENOTFOUND') || err.message.includes('timeout')) {
        const direct = `postgresql://postgres:${encodeURIComponent(password)}@db.${projectRef}.supabase.co:5432/postgres`;
        const client2 = new Client({ connectionString: direct, ssl: { rejectUnauthorized: false } });
        try {
            await client2.connect();
            console.log('Connected via direct connection');
            await client2.query(sql);
            console.log('Migration completed successfully');
            process.exit(0);
        } catch (err2) {
            console.error('Migration failed:', err2.message);
            process.exit(1);
        } finally {
            await client2.end();
        }
    }
    console.error('Migration failed:', err.message);
    process.exit(1);
} finally {
    await client.end().catch(() => {});
}
