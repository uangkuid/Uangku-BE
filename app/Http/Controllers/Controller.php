<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'REST API for Uangku, a personal finance management app with end-to-end (zero-knowledge) encryption. '
        .'Sensitive fields (secret keys, private keys, transaction amounts/descriptions) are encrypted client-side; '
        .'the server never sees plaintext for those fields.',
    title: 'Uangku API',
    contact: new OA\Contact(name: 'Uangku'),
)]
#[OA\Server(url: L5_SWAGGER_CONST_HOST, description: 'API server')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    description: "JWT access token obtained from /auth/login or /auth/register. Send as 'Authorization: Bearer {token}'.",
    bearerFormat: 'JWT',
    scheme: 'bearer'
)]
#[OA\Schema(
    schema: 'BaseResponse',
    description: 'Standard response envelope used by every endpoint.',
    properties: [
        new OA\Property(property: 'status', type: 'integer', example: 200),
        new OA\Property(property: 'message', type: 'string', example: 'Success'),
        new OA\Property(property: 'data', type: 'object', nullable: true),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'total_data', type: 'integer', example: 42),
        new OA\Property(property: 'total_page', type: 'integer', example: 5),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'PaginationResponse',
    description: 'Response envelope for paginated list endpoints.',
    properties: [
        new OA\Property(property: 'status', type: 'integer', example: 200),
        new OA\Property(property: 'message', type: 'string', example: 'Success'),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'ValidationErrorResponse',
    description: 'Returned when request validation fails (HTTP 400).',
    properties: [
        new OA\Property(property: 'status', type: 'integer', example: 400),
        new OA\Property(property: 'message', type: 'string', example: 'Failed to process request'),
        new OA\Property(
            property: 'data',
            type: 'object',
            example: ['field_name' => ['The field name field is required.']]
        ),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    description: 'Generic error envelope (business errors, 4xx/5xx).',
    properties: [
        new OA\Property(property: 'status', type: 'integer', example: 400),
        new OA\Property(property: 'message', type: 'string', example: 'Something went wrong'),
        new OA\Property(property: 'data', type: 'object', nullable: true),
    ],
    type: 'object'
)]
#[OA\Tag(name: 'Auth', description: 'Registration, login, password/credential rotation')]
#[OA\Tag(name: 'OTP', description: 'One-time-password delivery for auth-sensitive actions')]
#[OA\Tag(name: 'PIN', description: 'Transaction PIN setup, verification and recovery')]
#[OA\Tag(name: 'User', description: 'Authenticated user profile')]
#[OA\Tag(name: 'Family', description: 'Family groups and shared key management')]
#[OA\Tag(name: 'Wallet', description: 'Wallets and wallet membership')]
#[OA\Tag(name: 'Transaction', description: 'Wallet transactions')]
#[OA\Tag(name: 'Category', description: 'Transaction categories')]
#[OA\Tag(name: 'SubCategory', description: 'Transaction sub-categories')]
#[OA\Tag(name: 'TransactionType', description: 'Transaction types (income/expense/etc.)')]
#[OA\Tag(name: 'General', description: 'Public, unauthenticated configuration endpoints')]
abstract class Controller
{
    //
}
