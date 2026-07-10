<?php

return [
    'sections' => [
        'category_information' => 'Informasi Kategori',
        'image' => 'Gambar',
        'general_information' => 'Informasi Umum',
    ],
    'fields' => [
        'id' => 'ID',
        'category_id' => 'ID Kategori',
        'name' => 'Nama',
        'transaction_types' => 'Tipe Transaksi',
        'created_at' => 'Dibuat Pada',
        'updated_at' => 'Terakhir Diubah Pada',
    ],
    'filters' => [
        'transaction_type' => 'Tipe Transaksi',
    ],
    'relation_managers' => [
        'sub_categories' => 'Sub Kategori',
    ],
    'sub_categories' => [
        'sections' => [
            'sub_category_information' => 'Informasi Sub Kategori',
            'users_information' => 'Informasi Pengguna',
            'general_information' => 'Informasi Umum',
        ],
        'fields' => [
            'user_id' => 'ID Pengguna',
            'email' => 'Email',
            'user_email' => 'Email Pengguna',
            'users' => 'ID Pengguna',
            'families' => 'ID Family',
            'created_at' => 'Dibuat pada',
            'updated_at' => 'Terakhir diubah pada',
        ],
        'filters' => [
            'type' => 'Tipe',
            'personal_only' => 'Hanya Personal',
            'family_only' => 'Hanya Family',
        ],
        'groups' => [
            'family' => 'Family',
        ],
    ],
];
