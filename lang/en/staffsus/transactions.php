<?php

return [
    'sections' => [
        'transaction_metadata' => 'Transaction Metadata',
    ],
    'fields' => [
        'transaction_id' => 'Transaction ID',
        'time' => 'Time',
        'type' => 'Type',
        'category' => 'Category',
        'sub_category' => 'Sub Category',
        'wallet_id' => 'Wallet ID',
        'family_id' => 'Family ID',
        'personal' => 'Personal',
        'user_id' => 'User ID',
        'deleted_at' => 'Deleted At',
        'amount' => 'Amount',
        'note' => 'Note',
        'status' => 'Status',
        'placeholder_empty' => '—',
        'encrypted_zero_knowledge' => '🔒 Encrypted (zero-knowledge)',
        'encrypted_short' => '🔒 encrypted',
    ],
    'statuses' => [
        'deleted' => 'Deleted',
        'active' => 'Active',
    ],
    'filters' => [
        'type' => 'Type',
        'category' => 'Category',
        'wallet_id' => 'Wallet ID',
        'from' => 'From',
        'until' => 'Until',
    ],
];
