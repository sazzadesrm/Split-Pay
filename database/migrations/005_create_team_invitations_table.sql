-- Migration: create team_invitations table
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS team_invitations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id BIGINT UNSIGNED NOT NULL,
    email VARCHAR(190) NOT NULL,
    role ENUM('admin','member','viewer') NOT NULL DEFAULT 'member',
    token CHAR(64) NOT NULL,
    status ENUM('pending','accepted','declined','cancelled','expired') NOT NULL DEFAULT 'pending',
    invited_by BIGINT UNSIGNED NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_team_invitations_token (token),
    KEY idx_team_invitations_team (team_id),
    KEY idx_team_invitations_email (email),
    CONSTRAINT fk_team_invitations_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_team_invitations_inviter FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
