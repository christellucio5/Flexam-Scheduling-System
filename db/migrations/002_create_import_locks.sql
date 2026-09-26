-- Create import_locks table for race condition handling across concurrent imports
-- Supports simultaneous imports with atomic first-writer-wins locking

CREATE TABLE IF NOT EXISTS import_locks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_hash VARCHAR(64) NOT NULL,
    locked_by INT NOT NULL,
    locked_by_name VARCHAR(255),
    role VARCHAR(50),
    campus VARCHAR(100),
    import_type VARCHAR(50) NOT NULL,
    locked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_permanent TINYINT(1) DEFAULT 0,
    UNIQUE KEY uk_file_import_type (file_hash, import_type),
    INDEX idx_import_type (import_type),
    INDEX idx_is_permanent (is_permanent),
    INDEX idx_locked_at (locked_at)
);
