-- Migration 0010: Create export job tracking table
CREATE TABLE IF NOT EXISTS data_exports (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
    job_type VARCHAR(50) DEFAULT 'telemetry',
    file_path TEXT,
    status VARCHAR(20) DEFAULT 'queued', -- queued, processing, done, failed
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP
);

CREATE INDEX idx_exports_user_status ON data_exports(user_id, status);
