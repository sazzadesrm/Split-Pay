<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TeamMember;
use App\Services\ActivityLogService;
use App\Services\InvitationService;

final class TeamController extends Controller
{
    public function index(Request $request): void
    {
        $teams = new Team();
        Response::view('teams/index', ['title' => 'Your Teams', 'teams' => $teams->teamsForUser($this->userId())]);
    }

    public function showCreate(Request $request): void
    {
        Response::view('teams/create', ['title' => 'Create a Team']);
    }

    public function create(Request $request): void
    {
        $data = $request->all();
        $validator = new Validator($data);
        $validator->required('name', 'Team name')->string('name', 'Team name', 2, 150)
            ->string('description', 'Description', 0, 2000)
            ->required('default_currency', 'Currency')->string('default_currency', 'Currency', 3, 3);

        if ($validator->fails()) {
            $this->respond($request, false, 'Please correct the errors below.', ['errors' => $validator->errors()], '/teams/create');
            return;
        }

        $teams = new Team();
        $teamId = $teams->insert([
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'default_currency' => strtoupper($data['default_currency']),
            'owner_id' => $this->userId(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $members = new TeamMember();
        $members->insert([
            'team_id' => $teamId,
            'user_id' => $this->userId(),
            'role' => 'owner',
            'is_active' => 1,
            'joined_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        (new ActivityLogService())->log($teamId, $this->userId(), 'team.created', 'team', $teamId, 'Team created.');

        Auth::setActiveTeamId($teamId);
        Response::redirect(url('/dashboard'));
    }

    public function switchTeam(Request $request, string $id): void
    {
        $teams = new Team();
        $members = new TeamMember();
        $membership = $members->membership((int) $id, $this->userId());
        if ($membership === null || (int) $membership['is_active'] !== 1) {
            Response::abort(403, 'You do not belong to that team.');
        }
        Auth::setActiveTeamId((int) $id);
        $members->touchLastActive((int) $id, $this->userId());
        Response::redirect(url('/dashboard'));
    }

    public function settings(Request $request, string $id): void
    {
        $teams = new Team();
        $team = $teams->find((int) $id);
        if ($team === null || (int) $id !== $this->teamId()) {
            Response::abort(404);
        }
        Response::view('teams/settings', ['title' => 'Team Settings', 'team' => $team, 'role' => $this->role()]);
    }

    public function update(Request $request, string $id): void
    {
        if (!$this->can('team.edit_settings')) {
            Response::abort(403);
        }
        $teams = new Team();
        $data = $request->all();
        $update = ['name' => trim($data['name'] ?? ''), 'description' => $data['description'] ?? null];
        if ($this->role() === 'owner' && !empty($data['default_currency'])) {
            $update['default_currency'] = strtoupper($data['default_currency']);
        }
        $teams->update((int) $id, $update);
        (new ActivityLogService())->log((int) $id, $this->userId(), 'team.updated', 'team', (int) $id, 'Team settings updated.');
        $this->respond($request, true, 'Team settings updated.', null, '/teams/' . $id . '/settings');
    }

    public function delete(Request $request, string $id): void
    {
        if ($this->role() !== 'owner') {
            Response::abort(403);
        }
        $teams = new Team();
        $confirmName = (string) $request->input('confirm_name', '');
        $team = $teams->find((int) $id);
        if ($team === null || $confirmName !== $team['name']) {
            $this->respond($request, false, 'Team name confirmation did not match.', null, '/teams/' . $id . '/settings');
            return;
        }
        $teams->softDelete((int) $id);
        (new ActivityLogService())->log((int) $id, $this->userId(), 'team.deleted', 'team', (int) $id, 'Team deleted.');
        Response::redirect(url('/teams'));
    }

    public function members(Request $request, string $id): void
    {
        $members = new TeamMember();
        Response::view('teams/members', [
            'title' => 'Team Members',
            'members' => $members->listForTeam((int) $id),
            'invitations' => (new TeamInvitation())->listForTeam((int) $id),
            'role' => $this->role(),
        ]);
    }

    public function invite(Request $request, string $id): void
    {
        if (!$this->can('member.invite')) {
            Response::abort(403);
        }
        $email = (string) $request->input('email', '');
        $role = (string) $request->input('role', 'member');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['admin', 'member', 'viewer'], true)) {
            $this->respond($request, false, 'Please provide a valid email and role.', null, '/teams/' . $id . '/members');
            return;
        }
        $config = config('app');
        (new InvitationService())->invite((int) $id, $email, $role, $this->userId(), $config['invitation_expiry_days']);
        $this->respond($request, true, 'Invitation sent.', null, '/teams/' . $id . '/members');
    }

    public function updateRole(Request $request, string $id, string $memberId): void
    {
        if ($this->role() !== 'owner') {
            Response::abort(403);
        }
        $members = new TeamMember();
        $membership = $members->find((int) $memberId);
        if ($membership === null || (int) $membership['team_id'] !== (int) $id || $membership['role'] === 'owner') {
            $this->respond($request, false, 'Cannot change this member\'s role.', null, '/teams/' . $id . '/members');
            return;
        }
        $newRole = (string) $request->input('role', 'member');
        if (!in_array($newRole, ['admin', 'member', 'viewer'], true)) {
            $this->respond($request, false, 'Invalid role.', null, '/teams/' . $id . '/members');
            return;
        }
        $members->update((int) $memberId, ['role' => $newRole]);
        (new ActivityLogService())->log((int) $id, $this->userId(), 'member.role_updated', 'team_member', (int) $memberId, "Role changed to $newRole.");
        (new \App\Services\NotificationService())->notify((int) $membership['user_id'], (int) $id, 'role_changed', 'Your team role changed', "Your role is now $newRole.", '/teams/' . $id . '/members');
        $this->respond($request, true, 'Role updated.', null, '/teams/' . $id . '/members');
    }

    public function removeMember(Request $request, string $id, string $memberId): void
    {
        if (!$this->can('member.remove')) {
            Response::abort(403);
        }
        $members = new TeamMember();
        $membership = $members->find((int) $memberId);
        if ($membership === null || (int) $membership['team_id'] !== (int) $id || $membership['role'] === 'owner') {
            $this->respond($request, false, 'Cannot remove this member.', null, '/teams/' . $id . '/members');
            return;
        }
        $members->update((int) $memberId, ['is_active' => 0]);
        (new ActivityLogService())->log((int) $id, $this->userId(), 'member.removed', 'team_member', (int) $memberId, 'Member removed from team.');
        $this->respond($request, true, 'Member removed.', null, '/teams/' . $id . '/members');
    }

    public function showInvitation(Request $request, string $token): void
    {
        $invitations = new TeamInvitation();
        $invitation = $invitations->findByToken($token);
        Response::view('teams/invitation', ['title' => 'Team Invitation', 'invitation' => $invitation, 'token' => $token]);
    }

    public function acceptInvitation(Request $request, string $token): void
    {
        try {
            $invitation = (new InvitationService())->accept($token, $this->userId());
            Auth::setActiveTeamId((int) $invitation['team_id']);
            $this->respond($request, true, 'You have joined the team.', null, '/dashboard');
        } catch (\RuntimeException $e) {
            $this->respond($request, false, $e->getMessage(), null, '/invitations/' . $token);
        }
    }

    public function declineInvitation(Request $request, string $token): void
    {
        (new InvitationService())->decline($token);
        $this->respond($request, true, 'Invitation declined.', null, '/dashboard');
    }
}
