-- Migration 0011: Performance indexes & scaling partitions
-- Add some useful indexes for larger datasets

CREATE INDEX IF NOT EXISTS idx_telemetry_received_at ON telemetry(received_at DESC);
CREATE INDEX IF NOT EXISTS idx_devices_name ON devices(name);
CREATE INDEX IF NOT EXISTS idx_subscriptions_plan ON subscriptions(plan_id);

-- Example TimescaleDB hypertable creation (optional)
-- SELECT create_hypertable('telemetry', 'received_at', if_not_exists => TRUE);
