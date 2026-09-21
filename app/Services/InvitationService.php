<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\TeamInvitation;
use App\Models\TeamMember;
use App\Models\User;
use RuntimeException;

final class InvitationService
{
    private TeamInvitation $invitations;
    private TeamMember $members;
    private User $users;
    private ActivityLogService $activity;
    private NotificationService $notifications;
    private MailerInterface $mailer;

    public function __construct()
    {
        $this->invitations = new TeamInvitation();
        $this->members = new TeamMember();
        $this->users = new User();
        $this->activity = new ActivityLogService();
        $this->notifications = new NotificationService();
        $this->mailer = new LogMailer();
    }

    public function invite(int $teamId, string $email, string $role, int $invitedBy, int $expiryDays): int
    {
        $email = mb_strtolower(trim($email));
        $token = bin2hex(random_bytes(32));

        $id = $this->invitations->insert([
            'team_id' => $teamId,
            'email' => $email,
            'role' => $role,
            'token' => $token,
            'status' => 'pending',
            'invited_by' => $invitedBy,
            'expires_at' => date('Y-m-d H:i:s', time() + $expiryDays * 86400),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $link = url('/invitations/' . $token);
        $this->mailer->send($email, 'You were invited to a Split Pay team', "You have been invited to join a team on Split Pay. Accept your invitation: $link");

        $invitedUser = $this->users->findByEmail($email);
        if ($invitedUser !== null) {
            $this->notifications->notify((int) $invitedUser['id'], $teamId, 'team_invitation', 'Team invitation', 'You were invited to join a team.', '/invitations/' . $token);
        }

        $this->activity->log($teamId, $invitedBy, 'invitation.created', 'invitation', $id, "Invited $email as $role.");
        return $id;
    }

    public function accept(string $token, int $userId): array
    {
        $invitation = $this->invitations->findByToken($token);
        if ($invitation === null || $invitation['status'] !== 'pending') {
            throw new RuntimeException('This invitation is no longer valid.');
        }
        if (strtotime($invitation['expires_at']) < time()) {
            $this->invitations->update((int) $invitation['id'], ['status' => 'expired']);
            throw new RuntimeException('This invitation has expired.');
        }

        Database::beginTransaction();
        try {
            $existing = $this->members->membership((int) $invitation['team_id'], $userId);
            if ($existing === null) {
                $this->members->insert([
                    'team_id' => $invitation['team_id'],
                    'user_id' => $userId,
                    'role' => $invitation['role'],
                    'is_active' => 1,
                    'joined_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            } else {
                $this->members->update((int) $existing['id'], ['is_active' => 1]);
            }
            $this->invitations->update((int) $invitation['id'], ['status' => 'accepted']);
            $this->activity->log((int) $invitation['team_id'], $userId, 'invitation.accepted', 'invitation', (int) $invitation['id'], 'Invitation accepted.');
            $this->notifications->notify((int) $invitation['invited_by'], (int) $invitation['team_id'], 'team_invitation', 'Member joined', 'A member joined the team.', '/teams/' . $invitation['team_id'] . '/members');
            Database::commit();
            return $invitation;
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public function decline(string $token): void
    {
        $invitation = $this->invitations->findByToken($token);
        if ($invitation !== null && $invitation['status'] === 'pending') {
            $this->invitations->update((int) $invitation['id'], ['status' => 'declined']);
        }
    }

    public function cancel(int $invitationId, int $teamId, int $actorId): void
    {
        $this->invitations->update($invitationId, ['status' => 'cancelled']);
        $this->activity->log($teamId, $actorId, 'invitation.cancelled', 'invitation', $invitationId, 'Invitation cancelled.');
    }

    public function resend(int $invitationId, int $teamId, int $actorId, int $expiryDays): void
    {
        $invitation = $this->invitations->find($invitationId);
        if ($invitation === null) {
            throw new RuntimeException('Invitation not found.');
        }
        $this->invitations->update($invitationId, [
            'expires_at' => date('Y-m-d H:i:s', time() + $expiryDays * 86400),
            'status' => 'pending',
        ]);
        $link = url('/invitations/' . $invitation['token']);
        $this->mailer->send($invitation['email'], 'Reminder: You were invited to a Split Pay team', "Accept your invitation: $link");
        $this->activity->log($teamId, $actorId, 'invitation.resent', 'invitation', $invitationId, 'Invitation resent.');
    }
}
