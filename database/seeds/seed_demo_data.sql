-- Split Pay demo seed data. For local development / demo environments only.
-- Password for every seeded user is: password123
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Users
INSERT INTO users (id, name, email, password_hash, default_currency, created_at) VALUES
(1, 'Maria Smith', 'maria@splitpay.test', '$2y$12$vFmaX0br9g6OXDJ/ZB0kruSqBh5npz4uJjMxDXhbRPmLJNAm0w5G.', 'USD', NOW()),
(2, 'Alex Johnson', 'alex@splitpay.test', '$2y$12$vFmaX0br9g6OXDJ/ZB0kruSqBh5npz4uJjMxDXhbRPmLJNAm0w5G.', 'USD', NOW()),
(3, 'Sam Lee', 'sam@splitpay.test', '$2y$12$vFmaX0br9g6OXDJ/ZB0kruSqBh5npz4uJjMxDXhbRPmLJNAm0w5G.', 'USD', NOW()),
(4, 'Jordan Taylor', 'jordan@splitpay.test', '$2y$12$vFmaX0br9g6OXDJ/ZB0kruSqBh5npz4uJjMxDXhbRPmLJNAm0w5G.', 'USD', NOW());

-- Team
INSERT INTO teams (id, name, description, default_currency, owner_id, created_at) VALUES
(1, 'BrightPath Studio', 'Shared expenses for BrightPath Studio operations and client projects.', 'USD', 1, NOW());

INSERT INTO team_settings (team_id, approval_required, allow_self_approval, allow_member_settlements, allow_member_csv_exports, participant_visibility) VALUES
(1, 1, 0, 1, 1, 1);

-- Memberships
INSERT INTO team_members (id, team_id, user_id, role, is_active, joined_at) VALUES
(1, 1, 1, 'owner', 1, NOW()),
(2, 1, 2, 'admin', 1, NOW()),
(3, 1, 3, 'member', 1, NOW()),
(4, 1, 4, 'viewer', 1, NOW());

-- Default global categories
INSERT INTO categories (id, team_id, name, color, sort_order) VALUES
(1, NULL, 'Meals & Entertainment', '#F59E0B', 1),
(2, NULL, 'Travel', '#0EA5E9', 2),
(3, NULL, 'Transport', '#16A34A', 3),
(4, NULL, 'Accommodation', '#4F46E5', 4),
(5, NULL, 'Software & Subscriptions', '#3730A3', 5),
(6, NULL, 'Office Supplies', '#64748B', 6),
(7, NULL, 'Marketing', '#DC2626', 7),
(8, NULL, 'Equipment', '#0F172A', 8),
(9, NULL, 'Professional Services', '#4F46E5', 9),
(10, NULL, 'Other', '#94A3B8', 10);

-- Tags
INSERT INTO tags (id, team_id, name) VALUES
(1, 1, 'reimbursable'),
(2, 1, 'client-a'),
(3, 1, 'urgent'),
(4, 1, 'remote-team'),
(5, 1, 'q4-budget'),
(6, 1, 'subscription');

-- Projects
INSERT INTO projects (id, team_id, name, client_name, project_code, budget_amount, currency, status, created_by, start_date) VALUES
(1, 1, 'Website Redesign', 'Acme Corporation', 'ACME-WEB-2026', 5000.00, 'USD', 'active', 1, '2026-01-15'),
(2, 1, 'Q4 Marketing Campaign', 'Internal', 'MKT-Q4-2026', 2500.00, 'USD', 'active', 1, '2026-09-01');

-- Expenses
INSERT INTO expenses (id, team_id, title, description, amount, currency, expense_date, paid_by_user_id, created_by, category_id, project_id, split_type, status, approved_by, approved_at, created_at) VALUES
(1, 1, 'Figma Team Subscription', 'Monthly design tool subscription for the team.', 45.00, 'USD', '2026-09-01', 1, 1, 5, NULL, 'equal', 'approved', 2, NOW(), NOW()),
(2, 1, 'Airport Taxi - Client Meeting', 'Taxi to client meeting for the Acme website kickoff.', 32.50, 'USD', '2026-09-03', 2, 2, 3, 1, 'equal', 'approved', 1, NOW(), NOW()),
(3, 1, 'Client Lunch at Central Bistro', 'Lunch meeting with the client.', 120.00, 'USD', '2026-09-05', 1, 1, 1, NULL, 'equal', 'approved', 2, NOW(), NOW()),
(4, 1, 'Google Ads Campaign', 'Q4 paid ads spend.', 500.00, 'USD', '2026-09-06', 2, 2, 7, 2, 'equal', 'approved', 1, NOW(), NOW()),
(5, 1, 'Printer Ink and Paper', 'Office supplies restock.', 28.75, 'USD', '2026-09-08', 3, 3, 6, NULL, 'equal', 'submitted', NULL, NULL, NOW()),
(6, 1, 'Hotel for Design Conference', 'Two nights for the design conference.', 340.00, 'USD', '2026-09-10', 1, 1, 4, 1, 'percentage', 'approved', 2, NOW(), NOW()),
(7, 1, 'Zoom Pro Subscription', 'Monthly video conferencing subscription.', 15.99, 'USD', '2026-09-11', 2, 2, 5, NULL, 'equal', 'draft', NULL, NULL, NOW()),
(8, 1, 'Office Team Breakfast', 'Team breakfast during remote week.', 52.40, 'USD', '2026-09-12', 3, 3, 1, NULL, 'equal', 'rejected', NULL, NULL, NOW()),
(9, 1, 'Freelance Copywriting', 'Copywriting for the Q4 campaign.', 120.00, 'USD', '2026-09-13', 1, 1, 9, 2, 'shares', 'approved', 2, NOW(), NOW()),
(10, 1, 'Laptop Stand for Remote Setup', 'Personal ergonomic equipment.', 8.00, 'USD', '2026-09-14', 3, 3, 8, NULL, 'equal', 'draft', NULL, NULL, NOW());

UPDATE expenses SET rejected_by = 2, rejected_at = NOW(), approval_notes = 'Duplicate of Client Lunch at Central Bistro; please combine future team meals into one expense.' WHERE id = 8;

-- Expense splits
INSERT INTO expense_splits (expense_id, user_id, owed_amount) VALUES
(1, 1, 15.00), (1, 2, 15.00), (1, 3, 15.00),
(2, 2, 16.25), (2, 3, 16.25),
(3, 1, 40.00), (3, 2, 40.00), (3, 3, 40.00),
(4, 1, 250.00), (4, 2, 250.00),
(5, 1, 14.38), (5, 3, 14.37),
(7, 1, 5.33), (7, 2, 5.33), (7, 3, 5.33),
(8, 1, 17.47), (8, 2, 17.47), (8, 3, 17.46),
(10, 3, 8.00);

INSERT INTO expense_splits (expense_id, user_id, owed_amount, split_percentage) VALUES
(6, 1, 170.00, 50.00), (6, 2, 170.00, 50.00);

INSERT INTO expense_splits (expense_id, user_id, owed_amount, shares) VALUES
(9, 1, 60.00, 2), (9, 2, 30.00, 1), (9, 3, 30.00, 1);

-- Expense tags
INSERT INTO expense_tags (expense_id, tag_id) VALUES
(1, 6), (2, 2), (4, 5), (6, 2), (7, 6), (8, 4), (9, 5), (10, 4), (3, 1);

-- Receipt metadata (demo only; underlying files are not bundled with seed data)
INSERT INTO expense_receipts (expense_id, original_filename, stored_filename, file_path, mime_type, file_size, uploaded_by, created_at) VALUES
(3, 'central-bistro-receipt.jpg', 'a1b2c3d4e5f60718293a4b5c6d7e8f90.jpg', 'receipts/2026/09/a1b2c3d4e5f60718293a4b5c6d7e8f90.jpg', 'image/jpeg', 245760, 1, NOW()),
(6, 'hotel-invoice.pdf', 'b2c3d4e5f60718293a4b5c6d7e8f90a1.pdf', 'receipts/2026/09/b2c3d4e5f60718293a4b5c6d7e8f90a1.pdf', 'application/pdf', 512000, 1, NOW());

-- Settlements
INSERT INTO settlements (id, team_id, payer_id, receiver_id, amount, currency, payment_method, reference_note, settlement_date, status, completed_at, created_by, created_at) VALUES
(1, 1, 2, 1, 40.00, 'USD', 'bank_transfer', 'Reimbursement for Client Lunch', '2026-09-15', 'completed', NOW(), 2, NOW()),
(2, 1, 3, 1, 25.00, 'USD', 'cash', 'Partial settlement', '2026-09-16', 'pending', NULL, 3, NOW());

-- Activity log samples
INSERT INTO activity_logs (team_id, user_id, action, entity_type, entity_id, description, created_at) VALUES
(1, 1, 'team.created', 'team', 1, 'Maria Smith created the team BrightPath Studio.', NOW()),
(1, 1, 'expense.approved', 'expense', 3, 'Client Lunch at Central Bistro was approved.', NOW()),
(1, 2, 'settlement.completed', 'settlement', 1, 'Alex Johnson completed a $40.00 settlement to Maria Smith.', NOW());

-- Notifications
INSERT INTO notifications (user_id, team_id, type, title, message, link_url, is_read, created_at) VALUES
(1, 1, 'settlement_completed', 'Settlement completed', 'Alex Johnson completed a $40.00 settlement.', '/settlements', 0, NOW()),
(3, 1, 'expense_rejected', 'Expense rejected', 'Office Team Breakfast was rejected.', '/expenses/8', 0, NOW());

SET FOREIGN_KEY_CHECKS = 1;
