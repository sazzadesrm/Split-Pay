-- Migration: create projects table
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS projects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    client_name VARCHAR(150) NULL,
    project_code VARCHAR(60) NULL,
    description VARCHAR(2000) NULL,
    budget_amount DECIMAL(12,2) NULL,
    currency CHAR(3) NOT NULL DEFAULT 'USD',
    start_date DATE NULL,
    end_date DATE NULL,
    status ENUM('active','paused','completed','archived') NOT NULL DEFAULT 'active',
    last_budget_alert_level ENUM('none','warning_80','critical_100') NOT NULL DEFAULT 'none',
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    KEY idx_projects_team_status (team_id, status),
    CONSTRAINT fk_projects_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_projects_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
