<?php

namespace App\Enums;

enum RedisKey: string
{
    case Avatar = 'common-avatar';
    case FamilyInvitation = 'family-invitation';
}
