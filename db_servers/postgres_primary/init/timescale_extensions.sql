-- Timescale helper jobs (run after schema + Timescale extension)
-- Compression settings & continuous aggregates (optional)

-- Enable compression on telemetry (good for older chunks)
ALTER TABLE iot.telemetry SET (
  timescaledb.compress,
  timescaledb.compress_segmentby = 'user_id,device_id,metric'
);

-- Compress chunks older than 7 days (tune to your retention)
SELECT add_compression_policy('iot.telemetry', INTERVAL '7 days', if_not_exists => TRUE);