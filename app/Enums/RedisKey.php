<?php

namespace App\Enums;

enum RedisKey: string
{
    case S3 = 's3-cache';
    case FamilyInvitation = 'family-invitation';
}
