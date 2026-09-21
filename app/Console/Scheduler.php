<?php
declare(strict_types=1);

/**
 * Scheduled maintenance tasks. Run hourly or daily from cron:
 *   php app/Console/Scheduler.php
 */

require __DIR__ . '/../../vendor_autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Core\Logger;

Env::load(__DIR__ . '/../../.env');
$appConfig = require __DIR__ . '/../../config/app.php';
$pdo = Database::connection();

// Expire team invitations
$stmt = $pdo->prepare("UPDATE team_invitations SET status = 'expired' WHERE status = 'pending' AND expires_at < UTC_TIMESTAMP()");
$stmt->execute();
Logger::scheduler('Expired ' . $stmt->rowCount() . ' invitations.');

// Clean password reset tokens
$stmt = $pdo->prepare("DELETE FROM password_resets WHERE expires_at < (UTC_TIMESTAMP() - INTERVAL 7 DAY)");
$stmt->execute();
Logger::scheduler('Removed ' . $stmt->rowCount() . ' expired password reset tokens.');

// Remove old login attempts (older than 90 days)
$stmt = $pdo->prepare("DELETE FROM login_attempts WHERE attempted_at < (UTC_TIMESTAMP() - INTERVAL 90 DAY)");
$stmt->execute();
Logger::scheduler('Removed ' . $stmt->rowCount() . ' old login attempts.');

// Check project budget alerts (80% warning, 100% critical), notify once per threshold
$projects = $pdo->query(
    "SELECT p.*, COALESCE(SUM(CASE WHEN e.status IN ('approved','reimbursed') THEN e.amount END),0) spent
     FROM projects p LEFT JOIN expenses e ON e.project_id = p.id AND e.deleted_at IS NULL
     WHERE p.deleted_at IS NULL AND p.budget_amount IS NOT NULL AND p.budget_amount > 0
     GROUP BY p.id"
)->fetchAll();

$notificationService = new \App\Services\NotificationService();
foreach ($projects as $project) {
    $pct = ((float) $project['spent'] / (float) $project['budget_amount']) * 100;
    $newLevel = $pct >= 100 ? 'critical_100' : ($pct >= 80 ? 'warning_80' : 'none');
    $currentLevel = $project['last_budget_alert_level'];

    $levelOrder = ['none' => 0, 'warning_80' => 1, 'critical_100' => 2];
    if ($levelOrder[$newLevel] > $levelOrder[$currentLevel]) {
        $recipients = $pdo->prepare(
            "SELECT user_id FROM team_members WHERE team_id = :t AND role IN ('owner','admin') AND is_active = 1"
        );
        $recipients->execute(['t' => $project['team_id']]);
        $userIds = array_column($recipients->fetchAll(), 'user_id');
        $notificationService->notifyMany(
            array_map('intval', $userIds),
            (int) $project['team_id'],
            'project_budget_warning',
            'Project budget warning',
            $project['name'] . ' has reached ' . round($pct) . '% of its budget.',
            '/projects/' . $project['id']
        );
        $update = $pdo->prepare('UPDATE projects SET last_budget_alert_level = :level WHERE id = :id');
        $update->execute(['level' => $newLevel, 'id' => $project['id']]);
    }
}
Logger::scheduler('Checked project budget alerts.');

// Clean temporary uploads older than 24 hours (public/uploads/temporary)
$tempDir = dirname(__DIR__, 2) . '/public/uploads';
if (is_dir($tempDir)) {
    foreach (glob($tempDir . '/*') as $file) {
        if (is_file($file) && filemtime($file) < time() - 86400) {
            @unlink($file);
        }
    }
}
Logger::scheduler('Cleaned temporary uploads.');

echo "Scheduler run complete.\n";
