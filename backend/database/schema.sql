-- V7-Shield MVP schema (MySQL 8 / MariaDB 10.6+)
-- mysql -u root -p v7shield < backend/database/schema.sql

CREATE TABLE IF NOT EXISTS scan_jobs (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    target_id        VARCHAR(64)     NOT NULL,
    target_label     VARCHAR(120)    NOT NULL,
    status           ENUM('queued','running','completed','failed','cancelled') NOT NULL DEFAULT 'queued',
    requested_by     VARCHAR(64)     NOT NULL,
    requested_ip     VARCHAR(45)     NULL,
    created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    started_at       DATETIME        NULL,
    finished_at      DATETIME        NULL,
    heartbeat_at     DATETIME        NULL,
    files_seen       INT UNSIGNED    NOT NULL DEFAULT 0,
    files_scanned    INT UNSIGNED    NOT NULL DEFAULT 0,
    files_skipped    INT UNSIGNED    NOT NULL DEFAULT 0,
    findings_count   INT UNSIGNED    NOT NULL DEFAULT 0,
    critical_count   INT UNSIGNED    NOT NULL DEFAULT 0,
    high_count       INT UNSIGNED    NOT NULL DEFAULT 0,
    medium_count     INT UNSIGNED    NOT NULL DEFAULT 0,
    low_count        INT UNSIGNED    NOT NULL DEFAULT 0,
    errors_count     INT UNSIGNED    NOT NULL DEFAULT 0,
    bytes_scanned    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    limit_reached    VARCHAR(32)     NULL,
    error_message    VARCHAR(500)    NULL,
    rules_version    VARCHAR(32)     NOT NULL,
    scope            JSON            NULL COMMENT 'Limits and exclusions used for this scan',
    PRIMARY KEY (id),
    KEY idx_jobs_status (status),
    KEY idx_jobs_target_status (target_id, status),
    KEY idx_jobs_created (created_at),
    KEY idx_jobs_user_created (requested_by, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS scan_findings (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    scan_id          BIGINT UNSIGNED NOT NULL,
    rule_id          VARCHAR(32)     NOT NULL,
    rule_name        VARCHAR(120)    NOT NULL,
    category         VARCHAR(32)     NOT NULL,
    severity         ENUM('critical','high','medium','low') NOT NULL,
    severity_rank    TINYINT UNSIGNED NOT NULL,
    confidence       TINYINT UNSIGNED NOT NULL,
    classification   ENUM('suspicious') NOT NULL DEFAULT 'suspicious' COMMENT 'Heuristic match; never "confirmed"',
    file_path        VARCHAR(1024)   NOT NULL COMMENT 'Relative to the scan target root',
    file_size        BIGINT UNSIGNED NOT NULL,
    file_sha256      CHAR(64)        NOT NULL,
    line_no          INT UNSIGNED    NOT NULL,
    match_count      INT UNSIGNED    NOT NULL DEFAULT 1,
    evidence         VARCHAR(500)    NOT NULL,
    description      VARCHAR(500)    NOT NULL,
    false_positives  VARCHAR(500)    NOT NULL,
    created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_findings_scan_sev (scan_id, severity_rank),
    KEY idx_findings_rule (rule_id),
    KEY idx_findings_sha (file_sha256),
    CONSTRAINT fk_findings_scan FOREIGN KEY (scan_id) REFERENCES scan_jobs (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS scan_skips (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    scan_id          BIGINT UNSIGNED NOT NULL,
    file_path        VARCHAR(1024)   NOT NULL,
    reason           VARCHAR(32)     NOT NULL,
    detail           VARCHAR(255)    NOT NULL,
    PRIMARY KEY (id),
    KEY idx_skips_scan_reason (scan_id, reason),
    CONSTRAINT fk_skips_scan FOREIGN KEY (scan_id) REFERENCES scan_jobs (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Console users. Passwords are password_hash() values (bcrypt).
CREATE TABLE IF NOT EXISTS users (
    id                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    username             VARCHAR(64)   NOT NULL,
    password_hash        VARCHAR(255)  NOT NULL,
    must_change_password TINYINT(1)    NOT NULL DEFAULT 0,
    created_at           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login_at        DATETIME      NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default administrator: admin / Admin@12345
-- The password MUST be changed at first login (must_change_password = 1).
-- INSERT IGNORE keeps an existing admin untouched when the schema is re-run.
INSERT IGNORE INTO users (username, password_hash, must_change_password)
VALUES ('admin', '$2y$10$bGxqFhkJ6TQ6IiEX2UJwjOxR4OHgC/ZjK3qBUDt5tj8UzcHC04mlq', 1);

-- Failed sign-ins, used for server-side brute-force throttling.
CREATE TABLE IF NOT EXISTS login_attempts (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username     VARCHAR(64)     NOT NULL,
    ip           VARCHAR(45)     NOT NULL,
    attempted_at DATETIME        NOT NULL,
    PRIMARY KEY (id),
    KEY idx_attempts_time (attempted_at),
    KEY idx_attempts_user (username, attempted_at),
    KEY idx_attempts_ip (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
