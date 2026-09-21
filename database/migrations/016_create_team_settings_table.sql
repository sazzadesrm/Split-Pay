-- Migration: create team_settings table
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS team_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id BIGINT UNSIGNED NOT NULL,
    approval_required TINYINT(1) NOT NULL DEFAULT 1,
    allow_self_approval TINYINT(1) NOT NULL DEFAULT 0,
    allow_member_settlements TINYINT(1) NOT NULL DEFAULT 1,
    allow_member_csv_exports TINYINT(1) NOT NULL DEFAULT 1,
    participant_visibility TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_team_settings_team (team_id),
    CONSTRAINT fk_team_settings_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
