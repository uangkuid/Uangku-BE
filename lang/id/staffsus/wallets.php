<?php

return [
    'sections' => [
        'wallet_metadata' => 'Metadata Wallet',
    ],
    'fields' => [
        'wallet_id' => 'ID Wallet',
        'type' => 'Tipe',
        'status' => 'Status',
        'family_id' => 'ID Family',
        'personal' => 'Personal',
        'created_by_user_id' => 'Dibuat Oleh (ID Pengguna)',
        'created_by' => 'Dibuat Oleh',
        'member_count' => 'Jumlah Member',
        'members' => 'Member',
        'created_at' => 'Dibuat Pada',
        'updated_at' => 'Terakhir Diubah',
        'name' => 'Nama',
        'amount' => 'Jumlah',
        'encrypted_zero_knowledge' => '🔒 Terenkripsi (zero-knowledge)',
    ],
    'filters' => [
        'type_personal' => 'Personal',
        'type_family' => 'Family',
        'status_active' => 'Aktif',
        'status_inactive' => 'Nonaktif',
    ],
    'actions' => [
        'freeze' => 'Bekukan',
        'unfreeze' => 'Batalkan Bekukan',
    ],
    'modals' => [
        'freeze_description' => 'Wallet dibekukan sementara (bukan aksi finansial). Investigasi penyalahgunaan.',
    ],
    'notifications' => [
        'frozen' => 'Wallet dibekukan',
        'unfrozen' => 'Wallet diaktifkan kembali',
    ],
];
