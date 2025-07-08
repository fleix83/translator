-- Global Settings Schema for Translator Application
-- This file contains the SQL schema for storing global settings

-- Create global_settings table
CREATE TABLE IF NOT EXISTS global_settings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    setting_key TEXT UNIQUE NOT NULL,
    setting_value JSONB NOT NULL,
    description TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    created_by TEXT,
    is_active BOOLEAN DEFAULT TRUE
);

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_global_settings_key ON global_settings(setting_key);
CREATE INDEX IF NOT EXISTS idx_global_settings_active ON global_settings(is_active);
CREATE INDEX IF NOT EXISTS idx_global_settings_created_at ON global_settings(created_at);

-- Create trigger for updating updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_global_settings_updated_at
    BEFORE UPDATE ON global_settings
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Insert default settings
INSERT INTO global_settings (setting_key, setting_value, description, created_by) VALUES
    ('default_api_key', '""', 'Default Claude API key for all sessions', 'system'),
    ('default_summary_prompt', '"Please analyze the customer''s messages in this conversation and provide a concise summary of key information, requests, concerns, and any action items. Include important details like dates, quantities, or specific references. Format your response as a clear, bulleted list in the base language."', 'Default summary prompt for conversation summaries', 'system'),
    ('default_model', '"claude-3-haiku-20240307"', 'Default Claude model to use', 'system'),
    ('default_customer_language', '"fr"', 'Default customer language', 'system'),
    ('default_base_language', '"de"', 'Default base language', 'system'),
    ('app_name', '"Translator"', 'Application name', 'system'),
    ('organization_name', '"GGG Schreibdienst"', 'Organization name', 'system')
ON CONFLICT (setting_key) DO NOTHING;

-- Row Level Security (RLS) policies
ALTER TABLE global_settings ENABLE ROW LEVEL SECURITY;

-- Policy to allow all authenticated users to read global settings
CREATE POLICY "Allow read access to global settings" ON global_settings
    FOR SELECT USING (is_active = TRUE);

-- Policy to allow all authenticated users to update global settings
-- In a production environment, you might want to restrict this to admin users
CREATE POLICY "Allow update access to global settings" ON global_settings
    FOR UPDATE USING (is_active = TRUE);

-- Policy to allow insert of new global settings
CREATE POLICY "Allow insert access to global settings" ON global_settings
    FOR INSERT WITH CHECK (TRUE);

-- Grant necessary permissions
GRANT ALL ON global_settings TO authenticated;
GRANT ALL ON global_settings TO anon;