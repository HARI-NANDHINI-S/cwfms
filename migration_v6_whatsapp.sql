-- ============================================================
-- CWFMS Migration v6: WhatsApp Integration & Message Tracking
-- Ira Sportswear Lead Management Module
-- Safe and idempotent migration for WhatsApp message tracking
-- ============================================================

-- 1. Create table for detailed WhatsApp message history
CREATE TABLE IF NOT EXISTS lead_whatsapp_messages (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    lead_id UUID NOT NULL REFERENCES leads(id) ON DELETE CASCADE,
    customer_id UUID REFERENCES customers(id) ON DELETE SET NULL,
    recipient_phone VARCHAR(30) NOT NULL,
    template_name VARCHAR(100) NOT NULL,
    template_language VARCHAR(10) DEFAULT 'en',
    parameters JSONB DEFAULT '[]'::jsonb,
    provider_message_id VARCHAR(150),
    status VARCHAR(30) NOT NULL DEFAULT 'requested' CHECK (
        status IN ('not_requested', 'requested', 'sent', 'delivered', 'read', 'failed')
    ),
    error_message TEXT,
    trigger_type VARCHAR(30) NOT NULL DEFAULT 'manual' CHECK (
        trigger_type IN ('manual', 'automated', 'system')
    ),
    sent_by UUID REFERENCES user_roles(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- 2. Indexes for fast lookup by lead and webhook message ID
CREATE INDEX IF NOT EXISTS idx_wa_messages_lead_id ON lead_whatsapp_messages(lead_id);
CREATE INDEX IF NOT EXISTS idx_wa_messages_provider_id ON lead_whatsapp_messages(provider_message_id);
CREATE INDEX IF NOT EXISTS idx_wa_messages_status ON lead_whatsapp_messages(status);
CREATE INDEX IF NOT EXISTS idx_wa_messages_created_at ON lead_whatsapp_messages(created_at DESC);

-- 3. Ensure whatsapp_status on leads table supports full lifecycle
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'leads' AND column_name = 'whatsapp_last_sent_at'
    ) THEN
        ALTER TABLE leads ADD COLUMN whatsapp_last_sent_at TIMESTAMPTZ;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'leads' AND column_name = 'whatsapp_last_message_id'
    ) THEN
        ALTER TABLE leads ADD COLUMN whatsapp_last_message_id VARCHAR(150);
    END IF;
END $$;
