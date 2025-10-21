-- Migration 0005: Create boards and board_widgets tables
CREATE TABLE IF NOT EXISTS boards (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(150) NOT NULL,
    layout JSONB DEFAULT '{}'::jsonb,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS board_widgets (
    id SERIAL PRIMARY KEY,
    board_id INT REFERENCES boards(id) ON DELETE CASCADE,
    widget_id INT,
    position JSONB, -- {x:0, y:0, w:2, h:2}
    config JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_board_user_id ON boards(user_id);
