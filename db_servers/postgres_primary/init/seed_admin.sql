-- Seed roles, an initial super admin, some widgets, and a default plan.
-- Run AFTER schema_full.sql

INSERT INTO iot.roles(key, name) VALUES
  ('super_admin','Super Admin'),
  ('admin','Admin'),
  ('technician','Technician'),
  ('sales','Sales'),
  ('super_user','Super User'),
  ('sub_user','Sub User')
ON CONFLICT (key) DO NOTHING;

-- Create admin user if missing (password: "ChangeMe123!" – change immediately)
DO $$
DECLARE
  rid INT;
  uid BIGINT;
BEGIN
  SELECT id INTO rid FROM iot.roles WHERE key='super_admin';
  IF rid IS NULL THEN
    RAISE EXCEPTION 'Role super_admin missing';
  END IF;

  IF NOT EXISTS (SELECT 1 FROM iot.users WHERE email = 'admin@local') THEN
    INSERT INTO iot.users(email, display_name, role_id, password_hash)
    VALUES('admin@local', 'Super Admin', rid, crypt('ChangeMe123!', gen_salt('bf')))
    RETURNING id INTO uid;
  END IF;
END$$;

-- Seed widgets
INSERT INTO iot.widgets_catalog(key, default_title) VALUES
  ('switch','Switch'),
  ('slider','Slider'),
  ('thermostat','Thermostat'),
  ('gauge','Gauge'),
  ('chart','Chart'),
  ('camera','Camera'),
  ('alarm_panel','Alarm Panel'),
  ('env_monitor','Environment'),
  ('power_dashboard','Power Dashboard'),
  ('production_tracker','Production'),
  ('map_tracker','Map'),
  ('ota','Firmware OTA'),
  ('device_health','Device Health'),
  ('scene_builder','Scene Builder'),
  ('rules_engine','Rules Engine'),
  ('billing','Billing'),
  ('retention','Retention'),
  ('device_simulator','Simulator'),
  ('board_builder','Board Builder'),
  ('widget_matrix','Widget Matrix'),
  ('tech_codegen_wizard','Tech Codegen Wizard'),
  ('export_request','Export Request')
ON CONFLICT (key) DO NOTHING;

-- Default plan
INSERT INTO iot.billing_plans(key, name, price, limits) VALUES
('PRO', 'Professional', '$10/mo',
 '{"max_boards":50,"max_widgets":20,"retention_days":90,"export_window_days":30,"max_export_rows":200000}')
ON CONFLICT (key) DO NOTHING;
