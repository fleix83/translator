# Global Settings Setup Guide

This guide explains how to set up global settings for the Translator application.

## Overview

Global settings allow you to store API keys and default configurations that will be shared across all sessions and devices. This provides better usability by:

- **Centralized API Key Management**: Store the Claude API key globally so all sessions can use it
- **Default Summary Prompts**: Set a default summary prompt that applies to all new sessions
- **Cross-Session Consistency**: Ensure consistent settings across different devices and sessions

## Database Setup

### Step 1: Create the Global Settings Table

Run the SQL commands from `schema.sql` in your Supabase database:

```sql
-- Execute all commands from schema.sql file
```

Or manually execute the commands in the Supabase SQL Editor:

1. Go to your Supabase project dashboard
2. Navigate to the SQL Editor
3. Copy and paste the contents of `schema.sql`
4. Click "Run" to execute the commands

### Step 2: Verify Table Creation

After running the SQL, verify that the `global_settings` table was created:

1. Go to the "Table Editor" in Supabase
2. Look for the `global_settings` table
3. Verify it has the following columns:
   - `id` (UUID, Primary Key)
   - `setting_key` (TEXT, Unique)
   - `setting_value` (JSONB)
   - `description` (TEXT)
   - `created_at` (TIMESTAMP)
   - `updated_at` (TIMESTAMP)
   - `created_by` (TEXT)
   - `is_active` (BOOLEAN)

### Step 3: Check Row Level Security (RLS)

Ensure RLS policies are active:

1. In Supabase, go to Authentication > Policies
2. Check that policies exist for the `global_settings` table:
   - "Allow read access to global settings"
   - "Allow update access to global settings"
   - "Allow insert access to global settings"

## How to Use Global Settings

### Setting Global API Key

1. Open the Translator application
2. Click the settings (⚙️) button
3. Enter your Claude API key in the "Claude API Key" field
4. Click the "Global" button next to the input field
5. You'll see a confirmation toast: "API key saved globally"

### Setting Global Summary Prompt

1. In the settings panel, find the "Summary Prompt" section
2. Enter or modify the summary prompt text
3. Click "Save Global Default" button
4. You'll see a confirmation toast: "Summary prompt saved globally"

### Resetting to Global Defaults

- Click the "Reset" button next to the summary prompt to restore the global default
- Global API keys are automatically loaded when no local key is available

## Settings Hierarchy

The application uses the following priority order for settings:

1. **URL Parameters** (highest priority)
2. **Local Session Storage** (per-session overrides)
3. **Global Database Settings** (shared defaults)
4. **Application Defaults** (fallback values)

## Default Global Settings

When the application first runs, it automatically creates these default global settings:

- `default_api_key`: "" (empty, must be set by user)
- `default_summary_prompt`: Standard summary prompt template
- `default_model`: "claude-3-haiku-20240307"
- `default_customer_language`: "fr" (French)
- `default_base_language`: "de" (German)

## Troubleshooting

### Global Settings Not Loading

1. Check browser console for error messages
2. Verify Supabase credentials are correctly configured
3. Ensure the global_settings table exists
4. Check RLS policies are properly set

### Database Connection Issues

1. Verify `SUPABASE_URL` and `SUPABASE_ANON_KEY` in the code
2. Check if Supabase project is active
3. Test database connection in Supabase dashboard

### Permissions Errors

1. Verify RLS policies allow read/write access
2. Check that `anon` role has proper permissions
3. Ensure table grants are properly configured

## Security Considerations

- Global settings are shared across all users
- Consider implementing user-specific settings for multi-tenant deployments
- API keys are stored in the database - ensure proper security measures
- RLS policies control access to global settings

## Benefits

✅ **Simplified Setup**: New sessions automatically get the correct API key
✅ **Consistency**: All devices use the same default summary prompts
✅ **Reduced Configuration**: Users don't need to enter settings repeatedly
✅ **Centralized Management**: Update settings once, apply everywhere