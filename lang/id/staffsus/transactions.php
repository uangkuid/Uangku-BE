<?php

return [
    'sections' => [
        'transaction_metadata' => 'Metadata Transaksi',
    ],
    'fields' => [
        'transaction_id' => 'ID Transaksi',
        'time' => 'Waktu',
        'type' => 'Tipe',
        'category' => 'Kategori',
        'sub_category' => 'Sub Kategori',
        'wallet_id' => 'ID Wallet',
        'family_id' => 'ID Family',
        'personal' => 'Personal',
        'user_id' => 'ID Pengguna',
        'deleted_at' => 'Dihapus Pada',
        'amount' => 'Jumlah',
        'note' => 'Catatan',
        'status' => 'Status',
        'placeholder_empty' => '—',
        'encrypted_zero_knowledge' => '🔒 Terenkripsi (zero-knowledge)',
        'encrypted_short' => '🔒 terenkripsi',
    ],
    'statuses' => [
        'deleted' => 'Dihapus',
        'active' => 'Aktif',
    ],
    'filters' => [
        'type' => 'Tipe',
        'category' => 'Kategori',
        'wallet_id' => 'ID Wallet',
        'from' => 'Dari',
        'until' => 'Sampai',
    ],
];
