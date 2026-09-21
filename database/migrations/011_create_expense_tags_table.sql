-- Migration: create expense_tags table
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS expense_tags (
    expense_id BIGINT UNSIGNED NOT NULL,
    tag_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (expense_id, tag_id),
    KEY idx_expense_tags_tag (tag_id),
    CONSTRAINT fk_expense_tags_expense FOREIGN KEY (expense_id) REFERENCES expenses(id) ON DELETE CASCADE,
    CONSTRAINT fk_expense_tags_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
