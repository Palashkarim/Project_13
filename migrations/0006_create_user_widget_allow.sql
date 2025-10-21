-- Migration 0006: Track which widgets each user is allowed to use
CREATE TABLE IF NOT EXISTS user_widget_allow (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
    widget_key VARCHAR(100) NOT NULL,
    allowed BOOLEAN DEFAULT TRUE,
    UNIQUE (user_id, widget_key)
);
