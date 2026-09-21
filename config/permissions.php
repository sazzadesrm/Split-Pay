<?php
declare(strict_types=1);

// Central role/permission matrix. Keys are permission names, values are the
// roles allowed to perform them. Role hierarchy: owner > admin > member > viewer.
return [
    'roles' => ['owner', 'admin', 'member', 'viewer'],

    'permissions' => [
        'expense.create' => ['owner', 'admin', 'member'],
        'expense.edit_draft_own' => ['owner', 'admin', 'member'],
        'expense.edit_any_draft' => ['owner', 'admin'],
        'expense.edit_approved' => ['owner', 'admin'],
        'expense.delete_draft_own' => ['owner', 'admin', 'member'],
        'expense.delete_approved' => ['owner', 'admin'],
        'expense.submit' => ['owner', 'admin', 'member'],
        'expense.approve_reject' => ['owner', 'admin'],
        'expense.reimburse' => ['owner', 'admin'],
        'receipt.upload' => ['owner', 'admin', 'member'],
        'receipt.delete_any' => ['owner', 'admin'],
        'settlement.record' => ['owner', 'admin', 'member'],
        'settlement.manage_any' => ['owner', 'admin'],
        'project.manage' => ['owner', 'admin'],
        'category.manage' => ['owner', 'admin'],
        'tag.manage' => ['owner', 'admin'],
        'member.invite' => ['owner', 'admin'],
        'member.change_role' => ['owner'],
        'member.remove' => ['owner', 'admin'],
        'team.edit_settings' => ['owner', 'admin'],
        'team.delete' => ['owner'],
        'activity.view' => ['owner', 'admin'],
        'report.export' => ['owner', 'admin', 'member'],
    ],

    'self_approval_allowed' => [
        'owner' => false,
        'admin' => false,
    ],
];
