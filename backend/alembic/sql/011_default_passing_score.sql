INSERT INTO system_settings (setting_key, setting_value)
VALUES ('default_passing_score', '350.00')
ON CONFLICT (setting_key) DO NOTHING;
