-- Migration 0004: Create telemetry table
CREATE TABLE IF NOT EXISTS telemetry (
    id BIGSERIAL PRIMARY KEY,
    device_id VARCHAR(100) REFERENCES devices(device_id) ON DELETE CASCADE,
    topic VARCHAR(255),
    payload JSONB,
    received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Optimize time-based queries
CREATE INDEX idx_telemetry_device_time ON telemetry(device_id, received_at DESC);
CREATE INDEX idx_telemetry_topic ON telemetry(topic);
