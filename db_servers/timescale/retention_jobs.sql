-- =============================================================================
-- Retention & maintenance policies for telemetry
-- =============================================================================

-- Retain telemetry only for N days (overridden per plan in app logic)
-- Set a safe default (e.g., 180 days). Your app can also delete by tenant.
SELECT add_retention_policy('iot.telemetry', INTERVAL '180 days', if_not_exists => TRUE);

-- Reorder for better compression (by common query path)
SELECT add_reorder_policy('iot.telemetry',
                          index_name => 'idx_telemetry_user_time',
                          if_not_exists => TRUE);

-- Example continuous aggregate: hourly rollup (optional)
DROP MATERIALIZED VIEW IF EXISTS iot.telemetry_hourly CASCADE;
CREATE MATERIALIZED VIEW iot.telemetry_hourly
WITH (timescaledb.continuous) AS
SELECT
  time_bucket('1 hour', time) AS bucket,
  user_id, device_id, metric,
  AVG(value) AS avg_value,
  MIN(value) AS min_value,
  MAX(value) AS max_value
FROM iot.telemetry
GROUP BY 1,2,3,4;

-- Refresh policy for continuous aggregate
SELECT add_continuous_aggregate_policy('iot.telemetry_hourly',
  start_offset => INTERVAL '30 days',
  end_offset   => INTERVAL '1 hour',
  schedule_interval => INTERVAL '30 minutes');
