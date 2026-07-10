<?php

return [
    'sections' => [
        'category_information' => 'Category Information',
        'image' => 'Image',
        'general_information' => 'General Information',
    ],
    'fields' => [
        'id' => 'ID',
        'category_id' => 'Category ID',
        'name' => 'Name',
        'transaction_types' => 'Transaction Type',
        'created_at' => 'Created At',
        'updated_at' => 'Last Modified At',
    ],
    'filters' => [
        'transaction_type' => 'Transaction Type',
    ],
    'relation_managers' => [
        'sub_categories' => 'Sub Categories',
    ],
    'sub_categories' => [
        'sections' => [
            'sub_category_information' => 'Sub Category Information',
            'users_information' => 'Users Information',
            'general_information' => 'General Information',
        ],
        'fields' => [
            'user_id' => 'User ID',
            'email' => 'Email',
            'user_email' => 'User Email',
            'users' => 'User ID',
            'families' => 'Family ID',
            'created_at' => 'Created at',
            'updated_at' => 'Last modified at',
        ],
        'filters' => [
            'type' => 'Type',
            'personal_only' => 'Personal Only',
            'family_only' => 'Family Only',
        ],
        'groups' => [
            'family' => 'Family',
        ],
    ],
];
