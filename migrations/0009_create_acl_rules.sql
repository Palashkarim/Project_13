-- Migration 0009: Store ACL rules for each user/topic pattern
CREATE TABLE IF NOT EXISTS acl_rules (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
    topic_pattern VARCHAR(255) NOT NULL,
    permission VARCHAR(10) CHECK (permission IN ('read', 'write')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_acl_user_id ON acl_rules(user_id);
