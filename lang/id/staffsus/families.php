<?php

return [
    'sections' => [
        'family_metadata' => 'Metadata Family',
    ],
    'fields' => [
        'family_id' => 'ID Family',
        'created_by_user_id' => 'Dibuat Oleh (ID Pengguna)',
        'created_by' => 'Dibuat Oleh',
        'member_count' => 'Jumlah Member',
        'members' => 'Member',
        'created_at' => 'Dibuat Pada',
        'updated_at' => 'Terakhir Diubah',
        'name' => 'Nama',
        'encrypted_zero_knowledge' => '🔒 Terenkripsi (zero-knowledge)',
    ],
    'members' => [
        'fields' => [
            'user_id' => 'ID Pengguna',
            'role' => 'Role',
            'status' => 'Status',
        ],
        'roles' => [
            'owner' => 'Owner',
            'admin' => 'Admin',
            'member' => 'Member',
        ],
        'statuses' => [
            'active' => 'Aktif',
            'revoked' => 'Dicabut',
            'left' => 'Keluar',
        ],
    ],
];
