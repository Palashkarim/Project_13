-- =============================================================================
-- IoT Platform – Full Schema (PostgreSQL + TimescaleDB)
-- Safe to run once on a fresh DB. Idempotency is limited; drop first if needed.
-- =============================================================================

CREATE SCHEMA IF NOT EXISTS iot;

-- Common extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS pgcrypto;
CREATE EXTENSION IF NOT EXISTS timescaledb;

-- =========================
-- Users / Roles / Auth
-- =========================
CREATE TABLE IF NOT EXISTS iot.roles (
  id           SERIAL PRIMARY KEY,
  key          TEXT UNIQUE NOT NULL,      -- super_admin, admin, technician, sales, super_user, sub_user
  name         TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS iot.users (
  id           BIGSERIAL PRIMARY KEY,
  email        CITEXT UNIQUE NOT NULL,
  display_name TEXT,
  role_id      INT REFERENCES iot.roles(id) ON DELETE RESTRICT,
  password_hash TEXT NOT NULL,            -- bcrypt via pgcrypto: crypt('pwd', gen_salt('bf'))
  avatar_url   TEXT,
  is_active    BOOLEAN NOT NULL DEFAULT TRUE,
  created_at   TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- =========================
-- MQTT / DB Servers registry
-- =========================
CREATE TABLE IF NOT EXISTS iot.mqtt_servers (
  id           BIGSERIAL PRIMARY KEY,
  host         TEXT NOT NULL,
  ws_port      INT NOT NULL,
  tcp_port     INT NOT NULL,
  tls_port     INT,
  created_at   TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS iot.db_servers (
  id           BIGSERIAL PRIMARY KEY,
  host         TEXT NOT NULL,
  port         INT NOT NULL DEFAULT 5432,
  database     TEXT NOT NULL,
  username     TEXT NOT NULL,
  enc_password TEXT NOT NULL,             -- store encrypted at-rest (app decrypts)
  db_role      TEXT NOT NULL DEFAULT 'primary', -- primary/replica
  created_at   TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS iot.server_bindings (
  user_id      BIGINT REFERENCES iot.users(id) ON DELETE CASCADE,
  mqtt_server_id BIGINT REFERENCES iot.mqtt_servers(id) ON DELETE SET NULL,
  db_server_id   BIGINT REFERENCES iot.db_servers(id) ON DELETE SET NULL,
  PRIMARY KEY (user_id)
);

-- =========================
-- Devices
-- =========================
CREATE TABLE IF NOT EXISTS iot.devices (
  id           BIGSERIAL PRIMARY KEY,
  user_id      BIGINT REFERENCES iot.users(id) ON DELETE CASCADE,
  device_id    TEXT NOT NULL,                           -- human id, unique per user
  hw_type      TEXT NOT NULL DEFAULT 'esp32',
  status       TEXT NOT NULL DEFAULT 'offline',
  last_seen    TIMESTAMPTZ,
  UNIQUE (user_id, device_id)
);

-- =========================
-- Widgets / Boards
-- =========================
CREATE TABLE IF NOT EXISTS iot.widgets_catalog (
  id           SERIAL PRIMARY KEY,
  key          TEXT UNIQUE NOT NULL,                    -- switch, gauge, chart, ...
  default_title TEXT
);

CREATE TABLE IF NOT EXISTS iot.boards (
  id           BIGSERIAL PRIMARY KEY,
  user_id      BIGINT REFERENCES iot.users(id) ON DELETE CASCADE,
  name         TEXT NOT NULL,
  color        TEXT,
  created_at   TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS iot.board_widgets (
  id           BIGSERIAL PRIMARY KEY,
  board_id     BIGINT REFERENCES iot.boards(id) ON DELETE CASCADE,
  type_key     TEXT REFERENCES iot.widgets_catalog(key) ON DELETE RESTRICT,
  cfg_json     JSONB NOT NULL DEFAULT '{}'::jsonb,      -- widget config
  position     INT NOT NULL DEFAULT 0
);

-- Per-user allow list (which widget types they can use)
CREATE TABLE IF NOT EXISTS iot.user_widget_allow (
  user_id      BIGINT REFERENCES iot.users(id) ON DELETE CASCADE,
  widget_key   TEXT REFERENCES iot.widgets_catalog(key) ON DELETE CASCADE,
  PRIMARY KEY (user_id, widget_key)
);

-- =========================
-- Subscriptions / Plans
-- =========================
CREATE TABLE IF NOT EXISTS iot.billing_plans (
  key          TEXT PRIMARY KEY,                        -- e.g., PRO
  name         TEXT NOT NULL,
  price        TEXT,
  limits       JSONB NOT NULL DEFAULT '{}'::jsonb       -- {max_boards, max_widgets, retention_days, export_window_days, max_export_rows...}
);

CREATE TABLE IF NOT EXISTS iot.subscriptions (
  id           BIGSERIAL PRIMARY KEY,
  user_id      BIGINT REFERENCES iot.users(id) ON DELETE CASCADE,
  plan_key     TEXT REFERENCES iot.billing_plans(key) ON DELETE RESTRICT,
  starts_at    TIMESTAMPTZ NOT NULL DEFAULT now(),
  expires_at   TIMESTAMPTZ NOT NULL,
  status       TEXT NOT NULL DEFAULT 'active'
);

-- =========================
-- Telemetry (Timescale Hypertable)
-- =========================
CREATE TABLE IF NOT EXISTS iot.telemetry (
  time         TIMESTAMPTZ NOT NULL,
  user_id      BIGINT NOT NULL REFERENCES iot.users(id) ON DELETE CASCADE,
  device_id    TEXT NOT NULL,
  metric       TEXT NOT NULL,                            -- rssi, temp, power, etc.
  value        DOUBLE PRECISION,
  tags         JSONB NOT NULL DEFAULT '{}'::jsonb
);

-- Create Hypertable (safe if already a hypertable)
SELECT create_hypertable('iot.telemetry', 'time', if_not_exists => TRUE);

-- Helpful index
CREATE INDEX IF NOT EXISTS idx_telemetry_user_time ON iot.telemetry (user_id, time DESC);
CREATE INDEX IF NOT EXISTS idx_telemetry_device_time ON iot.telemetry (device_id, time DESC);
CREATE INDEX IF NOT EXISTS idx_telemetry_metric ON iot.telemetry (metric);

-- =========================
-- Firmware / OTA
-- =========================
CREATE TABLE IF NOT EXISTS iot.firmwares (
  id           BIGSERIAL PRIMARY KEY,
  name         TEXT,
  file_path    TEXT NOT NULL,
  size_bytes   BIGINT,
  version      TEXT,
  created_at   TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- =========================
-- Notifications / Audit / Exports
-- =========================
CREATE TABLE IF NOT EXISTS iot.notifications (
  id           BIGSERIAL PRIMARY KEY,
  user_id      BIGINT REFERENCES iot.users(id) ON DELETE CASCADE,
  channel      TEXT NOT NULL,                             -- email, sms, push
  title        TEXT,
  body         TEXT,
  status       TEXT NOT NULL DEFAULT 'queued',
  created_at   TIMESTAMPTZ NOT NULL DEFAULT now(),
  sent_at      TIMESTAMPTZ
);

CREATE TABLE IF NOT EXISTS iot.audit_logs (
  id           BIGSERIAL PRIMARY KEY,
  user_id      BIGINT,
  type         TEXT NOT NULL,                             -- login, device_cmd, acl, export...
  message      TEXT NOT NULL,
  ts           TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS iot.data_export_jobs (
  id           BIGSERIAL PRIMARY KEY,
  user_id      BIGINT REFERENCES iot.users(id) ON DELETE CASCADE,
  format       TEXT NOT NULL,                             -- csv/json
  from_ts      TIMESTAMPTZ NOT NULL,
  to_ts        TIMESTAMPTZ NOT NULL,
  status       TEXT NOT NULL DEFAULT 'queued',
  file_path    TEXT,
  created_at   TIMESTAMPTZ NOT NULL DEFAULT now(),
  completed_at TIMESTAMPTZ
);

-- =========================
-- Se-- =============================================================================
-- IoT Platform – Full Schema (PostgreSQL + TimescaleDB)
-- Safe to run once on a fresh DB. Idempotency is limited; drop first if needed.
-- =============================================================================

CREATE SCHEMA IF NOT EXISTS iot;

-- Common extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS pgcrypto;
CREATE EXTENSION IF NOT EXISTS timescaledb;

-- =========================
-- Users / Roles / Auth
-- =========================
CREATE TABLE IF NOT EXISTS iot.roles (
  id           SERIAL PRIMARY KEY,
  key          TEXT UNIQUE NOT NULL,      -- super_admin, admin, technician, sales, super_user, sub_user
  name         TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS iot.users (
  id           BIGSERIAL PRIMARY KEY,
  email        CITEXT UNIQUE NOT NULL,
  display_name TEXT,
  role_id      INT REFERENCES iot.roles(id) ON DELETE RESTRICT,
  password_hash TEXT NOT NULL,            -- bcrypt via pgcrypto: crypt('pwd', gen_salt('bf'))
  avatar_url   TEXT,
  is_active    BOOLEAN NOT NULL DEFAULT TRUE,
  created_at   TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- =========================
-- MQTT / DB Servers registry
-- =========================
CREATE TABLE IF NOT EXISTS iot.mqtt_servers (
  id           BIGSERIAL PRIMARY KEY,
  host         TEXT NOT NULL,
  ws_port      INT NOT NULL,
  tcp_port     INT NOT NULL,
  tls_port     INT,
  created_at   TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS iot.db_servers (
  id           BIGSERIAL PRIMARY KEY,
  host         TEXT NOT NULL,
  port         INT NOT NULL DEFAULT 5432,
  database     TEXT NOT NULL,
  username     TEXT NOT NULL,
  enc_password TEXT NOT NULL,             -- store encrypted at-rest (app decrypts)
  db_role      TEXT NOT NULL DEFAULT 'primary', -- primary/replica
  created_at   TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS iot.server_bindings (
  user_id      BIGINT REFERENCES iot.users(id) ON DELETE CASCADE,
  mqtt_server_id BIGINT REFERENCES iot.mqtt_servers(id) ON DELETE SET NULL,
  db_server_id   BIGINT REFERENCES iot.db_servers(id) ON DELETE SET NULL,
  PRIMARY KEY (user_id)
);

-- =========================
-- Devices
-- =========================
CREATE TABLE IF NOT EXISTS iot.devices (
  id           BIGSERIAL PRIMARY KEY,
  user_id      BIGINT REFERENCES iot.users(id) ON DELETE CASCADE,
  device_id    TEXT NOT NULL,                           -- human id, unique per user
  hw_type      TEXT NOT NULL DEFAULT 'esp32',
  status       TEXT NOT NULL DEFAULT 'offline',
  last_seen    TIMESTAMPTZ,
  UNIQUE (user_id, device_id)
);

-- =========================
-- Widgets / Boards
-- =========================
CREATE TABLE IF NOT EXISTS iot.widgets_catalog (
  id           SERIAL PRIMARY KEY,
  key          TEXT UNIQUE NOT NULL,                    -- switch, gauge, chart, ...
  default_title TEXT
);

CREATE TABLE IF NOT EXISTS iot.boards (
  id           BIGSERIAL PRIMARY KEY,
  user_id      BIGINT REFERENCES iot.users(id) ON DELETE CASCADE,
  name         TEXT NOT NULL,
  color        TEXT,
  created_at   TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS iot.board_widgets (
  id           BIGSERIAL PRIMARY KEY,
  board_id     BIGINT REFERENCES iot.boards(id) ON DELETE CASCADE,
  type_key     TEXT REFERENCES iot.widgets_catalog(key) ON DELETE RESTRICT,
  cfg_json     JSONB NOT NULL DEFAULT '{}'::jsonb,      -- widget config
  position     INT NOT NULL DEFAULT 0
);

-- Per-user allow list (which widget types they can use)
CREATE TABLE IF NOT EXISTS iot.user_widget_allow (
  user_id      BIGINT REFERENCES iot.users(id) ON DELETE CASCADE,
  widget_key   TEXT REFERENCES iot.widgets_catalog(key) ON DELETE CASCADE,
  PRIMARY KEY (user_id, widget_key)
);

-- =========================
-- Subscriptions / Plans
-- =========================
CREATE TABLE IF NOT EXISTS iot.billing_plans (
  key          TEXT PRIMARY KEY,                        -- e.g., PRO
  name         TEXT NOT NULL,
  price        TEXT,
  limits       JSONB NOT NULL DEFAULT '{}'::jsonb       -- {max_boards, max_widgets, retention_days, export_window_days, max_export_rows...}
);

CREATE TABLE IF NOT EXISTS iot.subscriptions (
  id           BIGSERIAL PRIMARY KEY,
  user_id      BIGINT REFERENCES iot.users(id) ON DELETE CASCADE,
  plan_key     TEXT REFERENCES iot.billing_plans(key) ON DELETE RESTRICT,
  starts_at    TIMESTAMPTZ NOT NULL DEFAULT now(),
  expires_at   TIMESTAMPTZ NOT NULL,
  status       TEXT NOT NULL DEFAULT 'active'
);

-- =========================
-- Telemetry (Timescale Hypertable)
-- =========================
CREATE TABLE IF NOT EXISTS iot.telemetry (
  time         TIMESTAMPTZ NOT NULL,
  user_id      BIGINT NOT NULL REFERENCES iot.users(id) ON DELETE CASCADE,
  device_id    TEXT NOT NULL,
  metric       TEXT NOT NULL,                            -- rssi, temp, power, etc.
  value        DOUBLE PRECISION,
  tags         JSONB NOT NULL DEFAULT '{}'::jsonb
);

-- Create Hypertable (safe if already a hypertable)
SELECT create_hypertable('iot.telemetry', 'time', if_not_exists => TRUE);

-- Helpful index
CREATE INDEX IF NOT EXISTS idx_telemetry_user_time ON iot.telemetry (user_id, time DESC);
CREATE INDEX IF NOT EXISTS idx_telemetry_device_time ON iot.telemetry (device_id, time DESC);
CREATE INDEX IF NOT EXISTS idx_telemetry_metric ON iot.telemetry (metric);

-- =========================
-- Firmware / OTA
-- =========================
CREATE TABLE IF NOT EXISTS iot.firmwares (
  id           BIGSERIAL PRIMARY KEY,
  name         TEXT,
  file_path    TEXT NOT NULL,
  size_bytes   BIGINT,
  version      TEXT,
  created_at   TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- =========================
-- Notifications / Audit / Exports
-- =========================
CREATE TABLE IF NOT EXISTS iot.notifications (
  id           BIGSERIAL PRIMARY KEY,
  user_id      BIGINT REFERENCES iot.users(id) ON DELETE CASCADE,
  channel      TEXT NOT NULL,                             -- email, sms, push
  title        TEXT,
  body         TEXT,
  status       TEXT NOT NULL DEFAULT 'queued',
  created_at   TIMESTAMPTZ NOT NULL DEFAULT now(),
  sent_at      TIMESTAMPTZ
);

CREATE TABLE IF NOT EXISTS iot.audit_logs (
  id           BIGSERIAL PRIMARY KEY,
  user_id      BIGINT,
  type         TEXT NOT NULL,                             -- login, device_cmd, acl, export...
  message      TEXT NOT NULL,
  ts           TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS iot.data_export_jobs (
  id           BIGSERIAL PRIMARY KEY,
  user_id      BIGINT REFERENCES iot.users(id) ON DELETE CASCADE,
  format       TEXT NOT NULL,                             -- csv/json
  from_ts      TIMESTAMPTZ NOT NULL,
  to_ts        TIMESTAMPTZ NOT NULL,
  status       TEXT NOT NULL DEFAULT 'queued',
  file_path    TEXT,
  created_at   TIMESTAMPTZ NOT NULL DEFAULT now(),
  completed_at TIMESTAMPTZ
);

curity / ACL (cache)
-- =========================
CREATE TABLE IF NOT EXISTS iot.acl_rules (
  id           BIGSERIAL PRIMARY KEY,
  username     TEXT NOT NULL,
  access       TEXT NOT NULL CHECK (access IN ('read','write')),
  topic        TEXT NOT NULL
);

-- =========================
-- Indices for scaling
-- =========================
CREATE INDEX IF NOT EXISTS idx_users_role ON iot.users(role_id);
CREATE INDEX IF NOT EXISTS idx_board_user  ON iot.boards(user_id);
CREATE INDEX IF NOT EXISTS idx_dev_user    ON iot.devices(user_id);
CREATE INDEX IF NOT EXISTS idx_sub_user    ON iot.subscriptions(user_id);

-- Done