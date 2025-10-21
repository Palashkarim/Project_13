-- Migration 0008: Map user to specific MQTT + DB servers
CREATE TABLE IF NOT EXISTS server_bindings (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
    mqtt_server VARCHAR(100),
    db_server VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
