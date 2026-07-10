<?php

return [
    'sections' => [
        'wallet_metadata' => 'Wallet Metadata',
    ],
    'fields' => [
        'wallet_id' => 'Wallet ID',
        'type' => 'Type',
        'status' => 'Status',
        'family_id' => 'Family ID',
        'personal' => 'Personal',
        'created_by_user_id' => 'Created By (User ID)',
        'created_by' => 'Created By',
        'member_count' => 'Jumlah Member',
        'members' => 'Members',
        'created_at' => 'Created At',
        'updated_at' => 'Last Modified',
        'name' => 'Name',
        'amount' => 'Amount',
        'encrypted_zero_knowledge' => '🔒 Encrypted (zero-knowledge)',
    ],
    'filters' => [
        'type_personal' => 'Personal',
        'type_family' => 'Family',
        'status_active' => 'Active',
        'status_inactive' => 'Inactive',
    ],
    'actions' => [
        'freeze' => 'Freeze',
        'unfreeze' => 'Unfreeze',
    ],
    'modals' => [
        'freeze_description' => 'Wallet will be temporarily frozen (non-financial action). For abuse investigation.',
    ],
    'notifications' => [
        'frozen' => 'Wallet frozen',
        'unfrozen' => 'Wallet reactivated',
    ],
];
