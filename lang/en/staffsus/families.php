<?php

return [
    'sections' => [
        'family_metadata' => 'Family Metadata',
    ],
    'fields' => [
        'family_id' => 'Family ID',
        'created_by_user_id' => 'Created By (User ID)',
        'created_by' => 'Created By',
        'member_count' => 'Jumlah Member',
        'members' => 'Members',
        'created_at' => 'Created At',
        'updated_at' => 'Last Modified',
        'name' => 'Name',
        'encrypted_zero_knowledge' => '🔒 Encrypted (zero-knowledge)',
    ],
    'members' => [
        'fields' => [
            'user_id' => 'User ID',
            'role' => 'Role',
            'status' => 'Status',
        ],
        'roles' => [
            'owner' => 'Owner',
            'admin' => 'Admin',
            'member' => 'Member',
        ],
        'statuses' => [
            'active' => 'Active',
            'revoked' => 'Revoked',
            'left' => 'Left',
        ],
    ],
];
